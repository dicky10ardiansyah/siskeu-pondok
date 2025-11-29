<?= $this->extend('layouts/template/apps') ?>

<?php $this->setVar('title', 'Pembayaran'); ?>

<?= $this->section('content') ?>
<div class="row">
    <div class="col">
        <div class="card">
            <div class="card-header d-flex align-items-center w-100">
                <h5 class="mb-0">Daftar Pembayaran</h5>

                <div class="ml-auto">
                    <a href="<?= base_url('payments/create') ?>" class="btn btn-primary">
                        <i class="fas fa-plus"></i> Tambah Pembayaran
                    </a>
                    <a href="<?= base_url('payment-categories') ?>" class="btn btn-outline-primary">
                        <i class="fas fa-list"></i> Kategori
                    </a>
                </div>
            </div>

            <div class="card-body">

                <form action="<?= base_url('payments') ?>" method="get" class="form-inline mb-3">
                    <input type="text" name="search" class="form-control mr-2" placeholder="Cari nama siswa / tanggal / metode..." value="<?= esc($search ?? '') ?>">
                    <button type="submit" class="btn btn-outline-primary">Cari</button>
                </form>

                <div class="table-responsive">
                    <table class="table table-bordered table-hover">
                        <thead class="thead-dark">
                            <tr>
                                <th>#</th>
                                <th>Nama Siswa</th>
                                <th>Tanggal</th>
                                <th>Jumlah</th>
                                <th>Metode</th>
                                <th>Referensi</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($payments)) : ?>
                                <?php
                                $no = 1 + (10 * ($pager->getCurrentPage('payments') - 1));
                                foreach ($payments as $payment) :
                                ?>
                                    <tr>
                                        <td><?= $no++ ?></td>
                                        <td><?= esc($payment['student_name']) ?></td>
                                        <td><?= esc($payment['date']) ?></td>
                                        <td><?= number_format($payment['total_amount'], 2) ?></td>
                                        <td><?= esc($payment['method'] ?? '-') ?></td>
                                        <td><?= esc($payment['reference'] ?? '-') ?></td>
                                        <td>
                                            <div class="d-flex justify-content-center">
                                                <a href="<?= base_url('payments/edit/' . $payment['id']) ?>" class="btn btn-sm btn-warning mr-2">
                                                    <i class="fas fa-edit"></i> Edit
                                                </a>
                                                <button type="button" class="btn btn-sm btn-danger" onclick="confirmDelete(<?= $payment['id'] ?>)">
                                                    <i class="fas fa-trash"></i> Hapus
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach ?>
                            <?php else : ?>
                                <tr>
                                    <td colspan="7" class="text-center">Tidak ada data pembayaran.</td>
                                </tr>
                            <?php endif ?>
                        </tbody>
                    </table>
                </div>

                <div class="mt-3">
                    <?= custom_pagination_with_query(
                        $pager,
                        ['search' => $search ?? ''],
                        'payments',
                        'bootstrap_full'
                    ) ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- SweetAlert2 konfirmasi hapus -->
<script>
    function confirmDelete(id) {
        Swal.fire({
            title: 'Yakin ingin menghapus?',
            text: "Pembayaran akan dibatalkan dan tagihan dikembalikan.",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Ya, hapus!',
            cancelButtonText: 'Batal'
        }).then((result) => {
            if (result.isConfirmed) {
                const form = document.createElement('form');
                form.method = 'post';
                form.action = '/payments/delete/' + id;

                const csrf = document.createElement('input');
                csrf.type = 'hidden';
                csrf.name = '<?= csrf_token() ?>';
                csrf.value = '<?= csrf_hash() ?>';

                form.appendChild(csrf);
                document.body.appendChild(form);
                form.submit();
            }
        });
    }
</script>

<!-- SweetAlert2 pesan sukses -->
<?php if (session()->getFlashdata('success')) : ?>
    <script>
        Swal.fire({
            toast: true,
            position: 'top-end',
            icon: 'success',
            title: '<?= session()->getFlashdata('success') ?>',
            showConfirmButton: false,
            timer: 3000,
            timerProgressBar: true,
        });
    </script>
<?php endif ?>

<?= $this->endSection() ?>