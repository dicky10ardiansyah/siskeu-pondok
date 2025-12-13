<?= $this->extend('layouts/template/apps') ?>

<?php $this->setVar('title', 'Kategori Pembayaran'); ?>

<?= $this->section('content') ?>
<div class="row">
    <div class="col">
        <div class="card">
            <div class="card-header d-flex align-items-center w-100">
                <h5 class="mb-0">Tabel Kategori Pembayaran</h5>
                <a href="<?= base_url('payment-categories/create') ?>" class="btn btn-primary ml-auto">
                    <i class="fas fa-plus"></i> Tambah Kategori
                </a>
            </div>

            <div class="card-body">

                <form action="<?= base_url('payment-categories') ?>" method="get" class="form-inline mb-3">

                    <input type="text"
                        name="q"
                        class="form-control mr-2"
                        placeholder="Cari nama kategori..."
                        value="<?= esc($search ?? '') ?>">

                    <?php if (session()->get('user_role') === 'admin') : ?>
                        <select name="user_id"
                            class="form-control mr-2"
                            onchange="this.form.submit()">
                            <option value="">-- Semua User --</option>
                            <?php foreach ($users as $user) : ?>
                                <option value="<?= $user['id'] ?>"
                                    <?= ($filterUser == $user['id']) ? 'selected' : '' ?>>
                                    <?= esc($user['name']) ?>
                                </option>
                            <?php endforeach ?>
                        </select>
                    <?php endif ?>

                    <button type="submit" class="btn btn-outline-primary">
                        Cari
                    </button>
                </form>

                <div class="table-responsive">
                    <table class="table table-bordered table-hover">
                        <thead class="thead-dark">
                            <tr>
                                <th>#</th>
                                <th>Nama Kategori</th>
                                <th>Jumlah Default</th>
                                <th>Tipe Tagihan</th>
                                <th>Durasi (Bulan)</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($paymentCategories)) : ?>
                                <?php
                                $no = 1 + (10 * ($pager->getCurrentPage('payment_categories') - 1));
                                foreach ($paymentCategories as $category) :
                                ?>
                                    <tr>
                                        <td><?= $no++ ?></td>
                                        <td><?= esc($category['name']) ?></td>
                                        <td><?= esc(number_format($category['default_amount'], 2)) ?></td>
                                        <td><?= esc(ucfirst($category['billing_type'])) ?></td>
                                        <td><?= esc($category['duration_months'] ?? '-') ?></td>
                                        <td>
                                            <div class="d-flex justify-content-center">
                                                <!-- Edit kategori -->
                                                <a href="<?= base_url('payment-categories/edit/' . $category['id']) ?>" class="btn btn-sm btn-warning mr-2">
                                                    <i class="fas fa-edit"></i> Edit
                                                </a>
                                                <!-- Tarif per Kelas -->
                                                <a href="<?= base_url('payment-categories/class-rules/' . $category['id']) ?>" class="btn btn-sm btn-info mr-2">
                                                    <i class="fas fa-layer-group"></i> Tarif per Kelas
                                                </a>
                                                <!-- Hapus kategori -->
                                                <button type="button" class="btn btn-sm btn-danger" onclick="confirmDelete(<?= $category['id'] ?>)">
                                                    <i class="fas fa-trash"></i> Hapus
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach ?>
                            <?php else : ?>
                                <tr>
                                    <td colspan="6" class="text-center">Tidak ada data kategori pembayaran.</td>
                                </tr>
                            <?php endif ?>
                        </tbody>
                    </table>
                </div>

                <div class="mt-3">
                    <?= custom_pagination_with_query(
                        $pager,
                        [
                            'q'       => $search ?? '',
                            'user_id' => $filterUser ?? ''
                        ],
                        'payment_categories',
                        'bootstrap_full'
                    ) ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- SweetAlert2 untuk konfirmasi hapus -->
<script>
    function confirmDelete(id) {
        Swal.fire({
            title: 'Yakin ingin menghapus?',
            text: "Data kategori tidak dapat dikembalikan!",
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
                form.action = '/payment-categories/delete/' + id;

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

<!-- SweetAlert2 untuk pesan sukses -->
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