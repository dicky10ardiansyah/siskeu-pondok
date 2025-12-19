<?= $this->extend('layouts/template/apps') ?>
<?php $this->setVar('title', 'Detail Billing'); ?>

<?= $this->section('content') ?>

<?php
function statusLabel($paid_amount, $amount, $is_partial = false)
{
    if ($is_partial) return '<span class="badge bg-warning text-dark">Sebagian</span>';
    if ($paid_amount >= $amount) return '<span class="badge bg-success">Lunas</span>';
    if ($paid_amount > 0) return '<span class="badge bg-warning text-dark">Sebagian</span>';
    return '<span class="badge bg-danger">Belum Bayar</span>';
}

function tanggalIndonesia($date)
{
    $bulan = [
        1 => 'Januari',
        'Februari',
        'Maret',
        'April',
        'Mei',
        'Juni',
        'Juli',
        'Agustus',
        'September',
        'Oktober',
        'November',
        'Desember'
    ];
    $d = date('d', strtotime($date));
    $m = (int)date('m', strtotime($date));
    $y = date('Y', strtotime($date));
    return "$d {$bulan[$m]} $y";
}
?>

<?php
function whatsappBillingLink($student, $totalBills, $amountDueNow, $pdfSecureUrl)
{
    if (empty($student['phone'])) return null;

    $phone = preg_replace('/[^0-9]/', '', $student['phone']);
    if (substr($phone, 0, 1) === '0') {
        $phone = '62' . substr($phone, 1);
    }

    $message = "Assalamualaikum wr wb\n" .
        "Yth. Orang Tua/Wali {$student['name']}\n" .
        "Berikut kami sampaikan informasi tagihan siswa:\n" .
        "Nama: {$student['name']}\n" .
        "Total yang harus dibayar: Rp " . number_format($amountDueNow, 0, ',', '.') . "\n\n" .
        "Link Detail tagihan (PDF):\n" .
        "$pdfSecureUrl\n\n" .
        "Berikut ini informasi dan ketentuan pembayaran:\n" .
        "1. Transfer melalui Bank Syariah Indonesia (BSI)\n" .
        "No. Rek: 73-XX-XXX-XX\n" .
        "a.n. Yayasan Ibu Bahagia Batam\n" .
        "2. Bukti transfer dikirim melalui link berikut:\n" .
        "http://localhost:8080/parent-payments/parent\n\n" .
        "Jika ada kendala, silakan hubungi:\n" .
        "• Mbak Siti: 0812-XXXX-XXXX\n" .
        "• Admin Sekolah: 0813-XXXX-XXXX\n\n" .
        "Atas perhatian dan kerja samanya kami syukuri\n" .
        "Alhamdulillah jazakumullohu khoiro\n" .
        "Waalaikumussalam wr wb";

    return 'https://wa.me/' . $phone . '?text=' . urlencode($message);
}
?>

<div class="row">
    <div class="col-12 mb-4">
        <div class="card">
            <div class="card-header d-flex align-items-center w-100">
                <h5 class="mb-0">Detail Billing - <?= esc($student['name']) ?></h5>
                <div class="ml-auto">
                    <a href="<?= base_url('billing') ?>" class="btn btn-primary">
                        <i class="fas fa-arrow-left"></i> Kembali
                    </a>
                    <a href="<?= $pdfSecureUrl ?>"
                        class="btn btn-outline-primary" target="_blank">
                        <i class="fas fa-file-pdf"></i> Cetak PDF
                    </a>
                    <?php $waLink = whatsappBillingLink($student, $totalBills, $amountDueNow, $pdfSecureUrl); ?>
                    <?php if ($waLink): ?>
                        <a href="<?= $waLink ?>"
                            target="_blank"
                            class="btn btn-success">
                            <i class="fab fa-whatsapp"></i> Kirim WhatsApp
                        </a>
                    <?php else: ?>
                        <button class="btn btn-secondary" disabled title="Nomor WhatsApp belum tersedia">
                            <i class="fab fa-whatsapp"></i> WhatsApp
                        </button>
                    <?php endif; ?>
                </div>
            </div>

            <div class="card-body">
                <!-- Summary -->
                <div class="row mb-4">
                    <div class="col-lg-3 col-6">
                        <div class="small-box bg-info">
                            <div class="inner">
                                <h3>Rp <?= number_format($totalBills, 0, ',', '.') ?></h3>
                                <p>Total Tagihan</p>
                            </div>
                            <div class="icon"><i class="fas fa-file-invoice-dollar"></i></div>
                        </div>
                    </div>
                    <div class="col-lg-3 col-6">
                        <div class="small-box bg-success">
                            <div class="inner">
                                <h3>Rp <?= number_format($totalPayments, 0, ',', '.') ?></h3>
                                <p>Total Pembayaran</p>
                            </div>
                            <div class="icon"><i class="fas fa-money-bill-wave"></i></div>
                        </div>
                    </div>
                    <div class="col-lg-3 col-6">
                        <div class="small-box <?= $amountDueNow > 0 ? 'bg-danger' : 'bg-secondary' ?>">
                            <div class="inner">
                                <h3>Rp <?= number_format($amountDueNow, 0, ',', '.') ?></h3>
                                <p>Total yang harus dibayar</p>
                            </div>
                            <div class="icon"><i class="fas fa-exclamation-triangle"></i></div>
                        </div>
                    </div>
                    <div class="col-lg-3 col-6">
                        <div class="small-box bg-primary">
                            <div class="inner">
                                <h3>Rp <?= number_format($overpaid, 0, ',', '.') ?></h3>
                                <p>Saldo / Uang Kelebihan</p>
                            </div>
                            <div class="icon"><i class="fas fa-wallet"></i></div>
                        </div>
                    </div>
                </div>

                <!-- Monthly Billing -->
                <h6>Billing Bulanan</h6>
                <div class="table-responsive mb-4">
                    <table class="table table-bordered table-hover">
                        <thead class="thead-dark">
                            <tr>
                                <th>#</th>
                                <th>Bulan</th>
                                <th>Tahun</th>
                                <th>Kategori</th>
                                <th>Kelas</th>
                                <th>Jumlah</th>
                                <th>Terbayar</th>
                                <th>Status</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($monthly): $no = 1; ?>
                                <?php foreach ($monthly as $bill): ?>
                                    <tr>
                                        <td><?= $no++ ?></td>
                                        <td><?= date('F', mktime(0, 0, 0, $bill['month'], 10)) ?></td>
                                        <td><?= esc($bill['year']) ?></td>
                                        <td><?= esc($bill['category']) ?></td>
                                        <td><?= esc($bill['kelas']) ?></td>
                                        <td><?= number_format($bill['amount'], 0, ',', '.') ?></td>
                                        <td><?= number_format($bill['paid_amount'], 0, ',', '.') ?></td>
                                        <td><?= statusLabel($bill['paid_amount'], $bill['amount'], $bill['is_partial_payment']) ?></td>
                                        <td>
                                            <button class="btn btn-danger btn-sm btn-delete" data-id="<?= $bill['id'] ?>">
                                                <i class="fas fa-trash"></i> Hapus
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="9" class="text-center">Tidak ada billing bulanan.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <!-- One-time Billing -->
                <h6>Billing One-time</h6>
                <div class="table-responsive">
                    <table class="table table-bordered table-hover">
                        <thead class="thead-dark">
                            <tr>
                                <th>#</th>
                                <th>Kategori</th>
                                <th>Jumlah</th>
                                <th>Terbayar</th>
                                <th>Status</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($one_time): $no = 1; ?>
                                <?php foreach ($one_time as $bill): ?>
                                    <tr>
                                        <td><?= $no++ ?></td>
                                        <td><?= esc($bill['category']) ?></td>
                                        <td><?= number_format($bill['amount'], 0, ',', '.') ?></td>
                                        <td><?= number_format($bill['paid_amount'], 0, ',', '.') ?></td>
                                        <td><?= statusLabel($bill['paid_amount'], $bill['amount'], $bill['is_partial_payment']) ?></td>
                                        <td>
                                            <button class="btn btn-danger btn-sm btn-delete" data-id="<?= $bill['id'] ?>">
                                                <i class="fas fa-trash"></i> Hapus
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="6" class="text-center">Tidak ada billing one-time.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.all.min.js"></script>
<script>
    document.addEventListener('click', function(e) {
        const btn = e.target.closest('.btn-delete');
        if (!btn) return;

        const billId = btn.dataset.id;

        Swal.fire({
            title: 'Yakin ingin menghapus?',
            text: "Data tagihan akan hilang permanen!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Ya, hapus!',
            cancelButtonText: 'Batal'
        }).then(result => {
            if (result.isConfirmed) {
                fetch(`<?= base_url('billing/deleteDetail/') ?>${billId}`, {
                        method: 'POST',
                        body: new URLSearchParams({
                            '<?= csrf_token() ?>': '<?= csrf_hash() ?>'
                        }),
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest'
                        }
                    })
                    .then(res => res.json())
                    .then(data => {
                        if (data.success) {
                            Swal.fire({
                                toast: true,
                                position: 'top-end',
                                icon: 'success',
                                title: data.success,
                                showConfirmButton: false,
                                timer: 2000
                            }).then(() => location.reload());
                        } else {
                            Swal.fire('Error', data.error, 'error');
                        }
                    })
                    .catch(() => Swal.fire('Error', 'Gagal menghubungi server.', 'error'));
            }
        });
    });
</script>

<?= $this->endSection() ?>