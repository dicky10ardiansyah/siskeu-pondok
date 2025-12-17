<?= $this->extend('layouts/template/apps') ?>

<?php $this->setVar('title', 'Bulk Edit Siswa'); ?>

<?= $this->section('content') ?>
<div class="row">
    <div class="col">
        <div class="card">
            <div class="card-header d-flex align-items-center w-100">
                <h5 class="mb-0">Bulk Edit Siswa</h5>
                <div class="ml-auto">
                    <a href="<?= base_url('students') ?>" class="btn btn-outline-secondary">
                        <i class="fas fa-arrow-left"></i> Kembali
                    </a>
                </div>
            </div>

            <div class="card-body">

                <!-- ================= FILTER & SEARCH ================= -->
                <form id="filterForm" method="get" action="<?= base_url('students/bulk-edit') ?>" class="mb-3">
                    <div class="form-row">
                        <div class="col-md-4">
                            <input type="text" name="keyword" class="form-control" placeholder="Cari Nama / NIS" value="<?= esc($keyword ?? '') ?>">
                        </div>

                        <div class="col-md-4">
                            <select name="class" class="form-control">
                                <option value="">-- Semua Kelas --</option>
                                <?php foreach ($classes as $c): ?>
                                    <option value="<?= $c['id'] ?>" <?= ($classFilter ?? '') == $c['id'] ? 'selected' : '' ?>>
                                        <?= esc($c['name']) ?>
                                    </option>
                                <?php endforeach ?>
                            </select>
                        </div>

                        <div class="col-md-2">
                            <button type="submit" class="btn btn-primary btn-block">
                                <i class="fas fa-search"></i> Cari
                            </button>
                        </div>

                        <div class="col-md-2">
                            <a href="<?= base_url('students/bulk-edit') ?>" class="btn btn-secondary btn-block">
                                Reset
                            </a>
                        </div>
                    </div>
                </form>
                <!-- ==================================================== -->

                <?php if (!empty($students)) : ?>
                    <!-- ================= BULK UPDATE FORM ================= -->
                    <form id="bulkForm" action="<?= base_url('students/bulk-update') ?>" method="post">
                        <?= csrf_field() ?>

                        <div class="callout callout-info mb-3">
                            <div class="form-row">
                                <div class="col-md-3 mb-2">
                                    <select id="class_global" class="form-control">
                                        <option value="">- Pilih Kelas -</option>
                                        <?php foreach ($classes as $class) : ?>
                                            <option value="<?= $class['id'] ?>"><?= esc($class['name']) ?></option>
                                        <?php endforeach ?>
                                    </select>
                                </div>

                                <div class="col-md-2 mb-2">
                                    <select id="status_global" class="form-control">
                                        <option value="">- Pilih Status -</option>
                                        <option value="1">Lulus</option>
                                        <option value="0">Belum Lulus</option>
                                    </select>
                                </div>

                                <div class="col-md-2 mb-2">
                                    <input type="text" id="school_year_global" class="form-control" placeholder="Tahun Lulus" maxlength="4">
                                </div>

                                <?php if (!empty($users)) : ?>
                                    <div class="col-md-3 mb-2">
                                        <select id="user_global" class="form-control">
                                            <option value="">- Pilih User -</option>
                                            <?php foreach ($users as $user) : ?>
                                                <option value="<?= $user['id'] ?>"><?= esc($user['name']) ?></option>
                                            <?php endforeach ?>
                                        </select>
                                    </div>
                                <?php endif; ?>

                                <div class="col-md-2 mb-2">
                                    <button type="submit" class="btn btn-success btn-block">
                                        <i class="fas fa-save"></i> Update Semua
                                    </button>
                                </div>
                            </div>
                        </div>

                        <div class="table-responsive">
                            <table class="table table-bordered table-hover">
                                <thead class="thead-dark">
                                    <tr>
                                        <th><input type="checkbox" id="select-all"></th>
                                        <th>Nama</th>
                                        <th>NIS</th>
                                        <th>Kelas</th>
                                        <th>Status</th>
                                        <th>Tahun</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($students as $student) : ?>
                                        <tr>
                                            <td><input type="checkbox" class="student-checkbox" name="student_id[]" value="<?= $student['id'] ?>"></td>
                                            <td><?= esc($student['name']) ?></td>
                                            <td><?= esc($student['nis']) ?></td>
                                            <td><?= esc($student['class_name'] ?? '-') ?></td>
                                            <td><?= $student['status'] ? 'Lulus' : 'Belum Lulus' ?></td>
                                            <td><?= esc($student['school_year'] ?? '-') ?></td>
                                        </tr>
                                    <?php endforeach ?>
                                </tbody>
                            </table>
                        </div>
                    </form>
                    <!-- =================================================== -->

                <?php else : ?>
                    <div class="alert alert-warning text-center">
                        Tidak ada data siswa
                    </div>
                <?php endif ?>

            </div>
        </div>
    </div>
</div>

<!-- ================= SCRIPTS ================= -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
    // Select All
    document.getElementById('select-all')?.addEventListener('change', function() {
        document.querySelectorAll('.student-checkbox').forEach(cb => cb.checked = this.checked);
    });

    const bulkForm = document.getElementById('bulkForm');

    bulkForm?.addEventListener('submit', function(e) {
        const checked = document.querySelectorAll('.student-checkbox:checked');

        if (checked.length === 0) {
            e.preventDefault();
            Swal.fire({
                icon: 'warning',
                title: 'Perhatian',
                text: 'Pilih minimal satu siswa terlebih dahulu!',
                confirmButtonText: 'OK'
            });
            return;
        }

        const classVal = document.getElementById('class_global').value;
        const statusVal = document.getElementById('status_global').value;
        const yearVal = document.getElementById('school_year_global').value;
        const userVal = document.getElementById('user_global') ? document.getElementById('user_global').value : '';

        checked.forEach(cb => {
            const id = cb.value;

            if (classVal !== '') bulkForm.insertAdjacentHTML('beforeend', `<input type="hidden" name="class[${id}]" value="${classVal}">`);
            if (statusVal !== '') bulkForm.insertAdjacentHTML('beforeend', `<input type="hidden" name="status[${id}]" value="${statusVal}">`);
            if (yearVal !== '') bulkForm.insertAdjacentHTML('beforeend', `<input type="hidden" name="school_year[${id}]" value="${yearVal}">`);
            if (userVal !== '') bulkForm.insertAdjacentHTML('beforeend', `<input type="hidden" name="user_id[${id}]" value="${userVal}">`);
        });
    });
</script>
<!-- ============================================ -->

<?= $this->endSection() ?>