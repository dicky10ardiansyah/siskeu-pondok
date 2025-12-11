<?= $this->extend('layouts/template/apps') ?>

<?= $this->section('content') ?>
<div class="row">
    <div class="col">
        <div class="card">
            <div class="card-header d-flex align-items-center w-100">
                <h5 class="mb-0">Tabel Pengguna</h5>
                <a href="<?= base_url('user/create') ?>" class="btn btn-primary ml-auto">
                    <i class="fas fa-plus"></i> Tambah Pengguna
                </a>
            </div>

            <div class="card-body">

                <form action="<?= base_url('user') ?>" method="get" class="form-inline mb-3">
                    <input type="text" name="search" class="form-control mr-2" placeholder="Cari nama/email..." value="<?= esc($search ?? '') ?>">
                    <button type="submit" class="btn btn-outline-primary">Cari</button>
                </form>

                <div class="table-responsive">
                    <table class="table table-bordered table-hover">
                        <thead class="thead-dark">
                            <tr>
                                <th>#</th>
                                <th>Nama</th>
                                <th>Email</th>
                                <th>Role</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($users)) : ?>
                                <?php $no = 1 + (10 * ($pager->getCurrentPage('users') - 1));
                                foreach ($users as $user) : ?>
                                    <tr>
                                        <td><?= $no++ ?></td>
                                        <td><?= esc($user['name']) ?></td>
                                        <td><?= esc($user['email']) ?></td>
                                        <td><?= esc($user['role']) ?></td>
                                        <td>
                                            <div class="d-flex justify-content-center">
                                                <a href="<?= base_url('user/edit/' . $user['id']) ?>" class="btn btn-sm btn-warning mr-2">Edit</a>
                                                <button type="button" class="btn btn-sm btn-danger" onclick="confirmDelete(<?= $user['id'] ?>)">
                                                    Hapus
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach ?>
                            <?php else : ?>
                                <tr>
                                    <td colspan="5" class="text-center">Tidak ada data pengguna.</td>
                                </tr>
                            <?php endif ?>
                        </tbody>
                    </table>
                </div>

                <div class="mt-3">
                    <?= custom_pagination_with_query(
                        $pager,
                        ['search' => $search ?? ''],
                        'users',
                        'bootstrap_full'
                    ) ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- SweetAlert2 for delete confirmation -->
<script>
    function confirmDelete(id) {
        Swal.fire({
            title: 'Yakin ingin menghapus?',
            text: "Data pengguna tidak dapat dikembalikan!",
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
                form.action = '/user/delete/' + id;

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

<!-- SweetAlert2 for flash success -->
<?php if (session()->getFlashdata('message')) : ?>
    <script>
        Swal.fire({
            toast: true,
            position: 'top-end',
            icon: 'success',
            title: '<?= session()->getFlashdata('message') ?>',
            showConfirmButton: false,
            timer: 3000,
            timerProgressBar: true,
        });
    </script>
<?php endif ?>

<?= $this->endSection() ?>