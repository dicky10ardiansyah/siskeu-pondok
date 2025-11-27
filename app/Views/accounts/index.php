<?= $this->extend('layouts/template/apps') ?>

<?php $this->setVar('title', 'Akun'); ?>

<?= $this->section('content') ?>
<div class="row">
    <div class="col">
        <div class="card">
            <div class="card-header d-flex align-items-center w-100">
                <h5 class="mb-0">Tabel Akun</h5>
                <a href="<?= base_url('accounts/create') ?>" class="btn btn-primary ml-auto">
                    <i class="fas fa-plus"></i> Tambah Akun
                </a>
            </div>

            <div class="card-body">

                <form action="<?= base_url('accounts') ?>" method="get" class="form-inline mb-3">
                    <input type="text" name="q" class="form-control mr-2" placeholder="Cari kode/nama akun..." value="<?= esc($search ?? '') ?>">
                    <button type="submit" class="btn btn-outline-primary">Cari</button>
                </form>

                <div class="table-responsive">
                    <table class="table table-bordered table-hover">
                        <thead class="thead-dark">
                            <tr>
                                <th>#</th>
                                <th>Kode</th>
                                <th>Nama Akun</th>
                                <th>Tipe</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($accounts)) : ?>
                                <?php
                                $no = 1 + (10 * ($pager->getCurrentPage('accounts') - 1));
                                foreach ($accounts as $account) :
                                ?>
                                    <tr>
                                        <td><?= $no++ ?></td>
                                        <td><?= esc($account['code']) ?></td>
                                        <td><?= esc($account['name']) ?></td>
                                        <td><?= esc(ucfirst($account['type'])) ?></td>
                                        <td>
                                            <div class="d-flex justify-content-center">
                                                <a href="<?= base_url('accounts/edit/' . $account['id']) ?>" class="btn btn-sm btn-warning mr-2">
                                                    <i class="fas fa-edit"></i> Edit
                                                </a>
                                                <button type="button" class="btn btn-sm btn-danger" onclick="confirmDelete(<?= $account['id'] ?>)">
                                                    <i class="fas fa-trash"></i> Hapus
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach ?>
                            <?php else : ?>
                                <tr>
                                    <td colspan="5" class="text-center">Tidak ada data akun.</td>
                                </tr>
                            <?php endif ?>
                        </tbody>
                    </table>
                </div>

                <div class="mt-3">
                    <?= custom_pagination_with_query(
                        $pager,
                        ['q' => $search ?? ''],
                        'accounts',
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
            text: "Data akun tidak dapat dikembalikan!",
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
                form.action = '/accounts/delete/' + id;

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