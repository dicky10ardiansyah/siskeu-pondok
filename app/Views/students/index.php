<?= $this->extend('layouts/template/apps') ?>

<?php $this->setVar('title', 'Data Siswa'); ?>

<?= $this->section('content') ?>
<div class="row">
    <div class="col">
        <div class="card">
            <div class="card-header d-flex align-items-center w-100">
                <h5 class="mb-0">Tabel Siswa</h5>
                <a href="<?= base_url('students/create') ?>" class="btn btn-primary ml-auto">
                    <i class="fas fa-plus"></i> Tambah Siswa
                </a>
            </div>

            <div class="card-body">

                <!-- Search -->
                <form action="<?= base_url('students') ?>" method="get" class="form-inline mb-3">
                    <input
                        type="text"
                        name="keyword"
                        class="form-control mr-2"
                        placeholder="Cari nama / NIS..."
                        value="<?= esc($keyword ?? '') ?>">
                    <button type="submit" class="btn btn-outline-primary">Cari</button>
                    <a href="<?= base_url('students') ?>" class="btn btn-outline-secondary ml-2">Reset</a>
                </form>

                <!-- Table -->
                <div class="table-responsive">
                    <table class="table table-bordered table-hover">
                        <thead class="thead-dark">
                            <tr>
                                <th>#</th>
                                <th>Nama</th>
                                <th>NIS</th>
                                <th>Kelas</th>
                                <th>Status Lulus</th>
                                <th>Tahun Lulus</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($students)) : ?>
                                <?php
                                $no = 1 + (5 * ($pager->getCurrentPage('students') - 1));
                                foreach ($students as $student) :
                                ?>
                                    <tr>
                                        <td><?= $no++ ?></td>
                                        <td><?= esc($student['name']) ?></td>
                                        <td><?= esc($student['nis']) ?></td>
                                        <td><?= esc($student['class']) ?></td>
                                        <td>
                                            <?php if ($student['status']) : ?>
                                                <span class="badge badge-success">Lulus</span>
                                            <?php else : ?>
                                                <span class="badge badge-secondary">Belum Lulus</span>
                                            <?php endif ?>
                                        </td>
                                        <td><?= esc($student['school_year'] ?? '-') ?></td>
                                        <td>
                                            <div class="d-flex justify-content-center">
                                                <a href="<?= base_url('students/edit/' . $student['id']) ?>" class="btn btn-sm btn-warning mr-2">
                                                    <i class="fas fa-edit"></i> Edit
                                                </a>
                                                <button type="button" class="btn btn-sm btn-danger" onclick="confirmDelete(<?= $student['id'] ?>)">
                                                    <i class="fas fa-trash"></i> Hapus
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach ?>
                            <?php else : ?>
                                <tr>
                                    <td colspan="7" class="text-center">Tidak ada data siswa.</td>
                                </tr>
                            <?php endif ?>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div class="mt-3">
                    <?= custom_pagination_with_query(
                        $pager,
                        ['keyword' => $keyword ?? ''],
                        'students',
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
            text: "Data siswa tidak dapat dikembalikan!",
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
                form.action = '/students/delete/' + id;

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