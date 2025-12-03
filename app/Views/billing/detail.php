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
    $bulan = [1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April', 5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus', 9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'];
    $d = date('d', strtotime($date));
    $m = (int)date('m', strtotime($date));
    $y = date('Y', strtotime($date));
    return "$d {$bulan[$m]} $y";
}
?>

<div class="row">
    <div class="col-12 mb-4">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0 d-inline-block">Detail Billing - <?= esc($student['name']) ?></h5>
                <div class="float-right">
                    <a href="<?= base_url('billing') ?>" class="btn btn-secondary btn-sm">
                        <i class="fas fa-arrow-left"></i> Kembali
                    </a>
                    <a href="<?= base_url('billing/pdf/' . $student['id']) ?>" class="btn btn-primary btn-sm" target="_blank">
                        <i class="fas fa-file-pdf"></i> Cetak PDF
                    </a>
                </div>
            </div>
            <div class="card-body">

                <!-- Summary Card -->
                <div class="row mb-4">
                    <div class="col-md-4 mb-2">
                        <div class="card bg-info">
                            <div class="card-body text-white text-center">
                                <h6>Total Tagihan</h6>
                                <p class="fs-4"><?= number_format($totalBills, 0, ',', '.') ?></p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4 mb-2">
                        <div class="card bg-success">
                            <div class="card-body text-white text-center">
                                <h6>Total Pembayaran</h6>
                                <p class="fs-4"><?= number_format($totalPayments, 0, ',', '.') ?></p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4 mb-2">
                        <div class="card bg-warning">
                            <div class="card-body text-dark text-center">
                                <h6>Harus Dibayar Sekarang</h6>
                                <p class="fs-4"><?= number_format($amountDueNow, 0, ',', '.') ?></p>
                            </div>
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
                                <th>Jumlah</th>
                                <th>Terbayar</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($monthly): $no = 1;
                                foreach ($monthly as $bill): ?>
                                    <tr>
                                        <td><?= $no++ ?></td>
                                        <td><?= $bill['month'] ? date('F', mktime(0, 0, 0, $bill['month'], 10)) : '-' ?></td>
                                        <td><?= esc($bill['year']) ?></td>
                                        <td><?= esc($bill['category']) ?></td>
                                        <td><?= number_format($bill['amount'], 0, ',', '.') ?></td>
                                        <td><?= number_format($bill['paid_amount'], 0, ',', '.') ?></td>
                                        <td>
                                            <?= statusLabel($bill['paid_amount'], $bill['amount'], $bill['is_partial_payment']) ?>
                                            <?php if (!empty($bill['payment_breakdown'])): ?>
                                                <br><small class="text-muted">
                                                    Dibayar dari:
                                                    <?php foreach ($bill['payment_breakdown'] as $p): ?>
                                                        <?= number_format($p['amount'], 0, ',', '.') ?> (<?= tanggalIndonesia($p['date']) ?>),
                                                    <?php endforeach; ?>
                                                </small>
                                            <?php endif; ?>
                                            <?php if (!empty($bill['partial_reason'])): ?>
                                                <br><small class="text-danger"><?= $bill['partial_reason'] ?></small>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach;
                            else: ?>
                                <tr>
                                    <td colspan="7" class="text-center">Tidak ada billing bulanan.</td>
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
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($one_time): $no = 1;
                                foreach ($one_time as $bill): ?>
                                    <tr>
                                        <td><?= $no++ ?></td>
                                        <td><?= esc($bill['category']) ?></td>
                                        <td><?= number_format($bill['amount'], 0, ',', '.') ?></td>
                                        <td><?= number_format($bill['paid_amount'], 0, ',', '.') ?></td>
                                        <td>
                                            <?= statusLabel($bill['paid_amount'], $bill['amount'], $bill['is_partial_payment']) ?>
                                            <?php if (!empty($bill['payment_breakdown'])): ?>
                                                <br><small class="text-muted">
                                                    Dibayar dari:
                                                    <?php foreach ($bill['payment_breakdown'] as $p): ?>
                                                        <?= number_format($p['amount'], 0, ',', '.') ?> (<?= tanggalIndonesia($p['date']) ?>),
                                                    <?php endforeach; ?>
                                                </small>
                                            <?php endif; ?>
                                            <?php if (!empty($bill['partial_reason'])): ?>
                                                <br><small class="text-danger"><?= $bill['partial_reason'] ?></small>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach;
                            else: ?>
                                <tr>
                                    <td colspan="5" class="text-center">Tidak ada billing one-time.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

            </div>
        </div>
    </div>
</div>

<?= $this->endSection() ?>