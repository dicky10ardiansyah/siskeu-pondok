<?= $this->extend('layouts/template/apps') ?>

<?php $this->setVar('title', 'Kelas'); ?>

<?= $this->section('content') ?>
<div class="row">
    <div class="col">
        <div class="card">
            <div class="card-header d-flex align-items-center w-100">
                <h5 class="mb-0">Tabel Kelas</h5>
                <a href="<?= base_url('classes/create') ?>" class="btn btn-primary ml-auto">
                    <i class="fas fa-plus"></i> Tambah Kelas
                </a>
            </div>

            <div class="card-body">

                <!-- Form Search -->
                <form action="<?= base_url('classes') ?>" method="get" class="form-inline mb-3">
                    <input type="text" name="q" class="form-control mr-2" placeholder="Cari nama kelas..."
                        value="<?= esc($search ?? '') ?>">
                    <button type="submit" class="btn btn-outline-primary">Cari</button>
                </form>

                <!-- Table -->
                <div class="table-responsive">
                    <table class="table table-bordered table-hover">
                        <thead class="thead-dark">
                            <tr>
                                <th>#</th>
                                <th>Nama Kelas</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>

                        <tbody>
                            <?php if (!empty($classes)) : ?>
                                <?php
                                $no = 1 + (10 * ($pager->getCurrentPage('classes') - 1));
                                foreach ($classes as $class) :
                                ?>
                                    <tr>
                                        <td><?= $no++ ?></td>
                                        <td><?= esc($class['name']) ?></td>
                                        <td>
                                            <div class="d-flex justify-content-center">
                                                <a href="<?= base_url('classes/edit/' . $class['id']) ?>" class="btn btn-sm btn-warning mr-2">
                                                    <i class="fas fa-edit"></i> Edit
                                                </a>

                                                <button type="button" class="btn btn-sm btn-danger" onclick="confirmDelete(<?= $class['id'] ?>)">
                                                    <i class="fas fa-trash"></i> Hapus
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach ?>
                            <?php else : ?>
                                <tr>
                                    <td colspan="3" class="text-center">Tidak ada data kelas.</td>
                                </tr>
                            <?php endif ?>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div class="mt-3">
                    <?= $pager->links('classes', 'bootstrap_full') ?>
                </div>

            </div>
        </div>
    </div>
</div>

<!-- Konfirmasi Hapus SweetAlert2 -->
<script>
    function confirmDelete(id) {
        Swal.fire({
            title: 'Yakin ingin menghapus?',
            text: "Data kelas tidak dapat dikembalikan!",
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
                form.action = '/classes/delete/' + id;

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

<!-- Notifikasi Sukses -->
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