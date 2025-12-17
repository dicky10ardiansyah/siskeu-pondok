<?= $this->extend('layouts/template/apps') ?>

<?php $this->setVar('title', 'Data Siswa'); ?>

<?= $this->section('content') ?>
<div class="row">
    <div class="col">
        <div class="card">
            <div class="card-header d-flex align-items-center w-100">
                <h5 class="mb-0">Tabel Siswa</h5>
                <div class="ml-auto">
                    <a href="<?= base_url('students/create') ?>" class="btn btn-primary">
                        <i class="fas fa-plus"></i> Tambah Siswa
                    </a>
                </div>
            </div>

            <div class="card-body">

                <!-- Filter Form -->
                <form action="<?= base_url('students') ?>" method="get" class="form-inline mb-3">

                    <!-- Filter Keyword -->
                    <input type="text" name="keyword" class="form-control mr-2"
                        placeholder="Cari nama / NIS..."
                        value="<?= esc($keyword ?? '') ?>">

                    <!-- Filter User (Admin Only) -->
                    <?php if (!empty($users)) : ?>
                        <select name="user_id" class="form-control mr-2">
                            <option value="">Semua User</option>
                            <?php foreach ($users as $u): ?>
                                <option value="<?= $u['id'] ?>" <?= ($selected_user ?? '') == $u['id'] ? 'selected' : '' ?>>
                                    <?= esc($u['name']) ?>
                                </option>
                            <?php endforeach ?>
                        </select>
                    <?php endif ?>

                    <!-- Filter Kelas -->
                    <select name="class" class="form-control mr-2">
                        <option value="">Semua Kelas</option>
                        <?php foreach ($classes as $c): ?>
                            <option value="<?= $c['id'] ?>" <?= ($class ?? '') == $c['id'] ? 'selected' : '' ?>>
                                <?= esc($c['name']) ?>
                            </option>
                        <?php endforeach ?>
                    </select>

                    <button type="submit" class="btn btn-outline-primary">Filter</button>
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
                                $perPage = $pager->getPerPage('students');
                                $no = 1 + ($perPage * ($pager->getCurrentPage('students') - 1));
                                foreach ($students as $student) :
                                ?>
                                    <tr>
                                        <td><?= $no++ ?></td>
                                        <td><?= esc($student['name']) ?></td>
                                        <td><?= esc($student['nis'] ?? '-') ?></td>
                                        <td><?= esc($student['class_name'] ?? '-') ?></td>
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
                                                <a href="<?= base_url('students/edit/' . $student['id']) ?>"
                                                    class="btn btn-sm btn-warning mr-2">
                                                    <i class="fas fa-edit"></i> Edit
                                                </a>

                                                <a href="<?= base_url('students/' . $student['id'] . '/payment-rules') ?>"
                                                    class="btn btn-sm btn-success mr-2">
                                                    <i class="fas fa-money-bill"></i> Tarif
                                                </a>

                                                <button type="button"
                                                    class="btn btn-sm btn-danger"
                                                    onclick="confirmDelete(<?= $student['id'] ?>)">
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
                        ['keyword' => $keyword ?? '', 'class' => $class ?? '', 'user_id' => $selected_user ?? ''],
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

<!-- SweetAlert2 pesan sukses/error -->
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

<?php if (session()->getFlashdata('error')) : ?>
    <script>
        Swal.fire({
            toast: true,
            position: 'top-end',
            icon: 'error',
            title: '<?= session()->getFlashdata('error') ?>',
            showConfirmButton: false,
            timer: 3000,
            timerProgressBar: true,
        });
    </script>
<?php endif ?>

<?= $this->endSection() ?>