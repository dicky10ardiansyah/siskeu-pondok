<?= $this->extend('layouts/template/apps') ?>

<?php $this->setVar('title', 'Pembayaran Orang Tua'); ?>

<?= $this->section('content') ?>
<div class="row">
    <div class="col">
        <div class="card">
            <div class="card-header d-flex align-items-center w-100">
                <h5 class="mb-0">Tabel Pembayaran Orang Tua</h5>
                <a href="<?= base_url('parent-payments/create') ?>" class="btn btn-primary ml-auto">
                    <i class="fas fa-plus"></i> Tambah Pembayaran
                </a>
            </div>

            <div class="card-body">

                <!-- SEARCH -->
                <form action="<?= base_url('parent-payments') ?>" method="get" class="form-inline mb-3">
                    <input type="text" name="q" class="form-control mr-2" placeholder="Cari nama siswa / akun..." value="<?= esc($search ?? '') ?>">

                    <select name="class_id" class="form-control mr-2">
                        <option value="">-- Semua Kelas --</option>
                        <?php foreach ($classes as $class) : ?>
                            <option value="<?= $class['id'] ?>"
                                <?= isset($filterClass) && $filterClass == $class['id'] ? 'selected' : '' ?>>
                                <?= esc($class['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>

                    <select name="status" class="form-control mr-2">
                        <option value="">-- Semua Status --</option>
                        <option value="1" <?= isset($filterStatus) && $filterStatus === '1' ? 'selected' : '' ?>>Lulus</option>
                        <option value="0" <?= isset($filterStatus) && $filterStatus === '0' ? 'selected' : '' ?>>Tidak Lulus</option>
                    </select>

                    <button type="submit" class="btn btn-outline-primary mr-2">Cari</button>
                    <a href="<?= base_url('parent-payments') ?>" class="btn btn-secondary">Reset</a>
                </form>

                <div class="table-responsive">
                    <table class="table table-bordered table-hover">
                        <thead class="thead-dark">
                            <tr>
                                <th>#</th>
                                <th>Nama Siswa</th>
                                <th>Kelas</th>
                                <th>Nama Akun</th>
                                <th>Jumlah</th>
                                <th>Bukti</th>
                                <th>Status</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($payments)) : ?>
                                <?php
                                $no = 1 + (10 * ($pager->getCurrentPage() - 1));
                                foreach ($payments as $payment) :
                                ?>
                                    <tr>
                                        <td><?= $no++ ?></td>
                                        <td><?= esc($payment['student_name']) ?></td>
                                        <td><?= esc($payment['class_name']) ?></td>
                                        <td><?= esc($payment['account_name']) ?></td>
                                        <td class="text-right">
                                            Rp <?= number_format($payment['amount'], 0, ',', '.') ?>
                                        </td>
                                        <td>
                                            <?php if ($payment['photo'] && file_exists(FCPATH . 'uploads/payment_parents/' . $payment['photo'])) : ?>
                                                <a href="<?= base_url('uploads/payment_parents/' . $payment['photo']) ?>" target="_blank">
                                                    <img src="<?= base_url('uploads/payment_parents/' . $payment['photo']) ?>"
                                                        alt="Bukti" style="max-width:60px; height:auto; border:1px solid #ddd; padding:2px;">
                                                </a>
                                            <?php else : ?>
                                                -
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?= $payment['student_status'] == 1 ? 'Lulus' : 'Tidak Lulus' ?>
                                        </td>
                                        <td>
                                            <div class="d-flex justify-content-center">
                                                <a href="<?= base_url('parent-payments/edit/' . $payment['id']) ?>" class="btn btn-sm btn-warning mr-2">
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
                                    <td colspan="8" class="text-center">Tidak ada data pembayaran.</td>
                                </tr>
                            <?php endif ?>
                        </tbody>
                    </table>
                </div>

                <!-- PAGINATION -->
                <div class="mt-3">
                    <?= custom_pagination_with_query(
                        $pager,
                        ['q' => $search ?? ''],
                        'parent_payments', // ✅ HARUS string
                        'bootstrap_full'
                    ) ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- SWEETALERT DELETE -->
<script>
    function confirmDelete(id) {
        Swal.fire({
            title: 'Yakin ingin menghapus?',
            text: "Data pembayaran tidak dapat dikembalikan!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Ya, hapus!',
            cancelButtonText: 'Batal'
        }).then((result) => {
            if (result.isConfirmed) {
                const form = document.createElement('form');
                form.method = 'get';
                form.action = '<?= base_url('parent-payments/delete') ?>/' + id;
                document.body.appendChild(form);
                form.submit();
            }
        });
    }
</script>

<!-- SWEETALERT SUCCESS -->
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