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

                <?php if (!empty($students)) : ?>
                    <form action="<?= base_url('students/bulk-update') ?>" method="post">
                        <?= csrf_field() ?>

                        <div class="callout callout-info">
                            <!-- Pilihan global -->
                            <div class="form-row">
                                <div class="col-md-3">
                                    <select name="class_global" id="class_global" class="form-control">
                                        <option value="">- Pilih Kelas -</option>
                                        <?php foreach ($classes as $class) : ?>
                                            <option value="<?= $class['id'] ?>"><?= esc($class['name']) ?></option>
                                        <?php endforeach ?>
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <select name="status_global" id="status_global" class="form-control">
                                        <option value="">- Pilih Status Lulus -</option>
                                        <option value="1">Lulus</option>
                                        <option value="0">Belum Lulus</option>
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <input type="text" name="school_year_global" id="school_year_global" placeholder="Tahun Lulus" maxlength="4" class="form-control">
                                </div>
                                <div class="col-md-3">
                                    <button type="submit" class="btn btn-success">
                                        <i class="fas fa-save"></i> Update Semua
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Tabel siswa -->
                        <div class="table-responsive">
                            <table class="table table-bordered table-hover">
                                <thead class="thead-dark">
                                    <tr>
                                        <th><input type="checkbox" id="select-all"></th>
                                        <th>Nama</th>
                                        <th>NIS</th>
                                        <th>Kelas</th>
                                        <th>Status Lulus</th>
                                        <th>Tahun Lulus</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($students as $student) : ?>
                                        <tr>
                                            <td>
                                                <input type="checkbox" name="student_id[]" value="<?= $student['id'] ?>" class="student-checkbox">
                                            </td>
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
                <?php else : ?>
                    <div class="alert alert-warning text-center">Tidak ada data siswa.</div>
                <?php endif ?>
            </div>
        </div>
    </div>
</div>

<!-- Select All -->
<script>
    document.getElementById('select-all').addEventListener('click', function() {
        const checkboxes = document.querySelectorAll('.student-checkbox');
        checkboxes.forEach(cb => cb.checked = this.checked);
    });

    // Kirim nilai global ke setiap siswa yang dicentang
    document.querySelector('form').addEventListener('submit', function(e) {
        const classVal = document.getElementById('class_global').value;
        const statusVal = document.getElementById('status_global').value;
        const yearVal = document.getElementById('school_year_global').value;

        const checkboxes = document.querySelectorAll('.student-checkbox:checked');
        if (checkboxes.length === 0) {
            e.preventDefault();
            alert('Pilih minimal satu siswa!');
            return;
        }

        checkboxes.forEach(cb => {
            const id = cb.value;

            // Class
            if (classVal !== '') {
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'class[' + id + ']';
                input.value = classVal;
                this.appendChild(input);
            }

            // Status
            if (statusVal !== '') {
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'status[' + id + ']';
                input.value = statusVal;
                this.appendChild(input);
            }

            // Tahun Lulus
            if (yearVal !== '') {
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'school_year[' + id + ']';
                input.value = yearVal;
                this.appendChild(input);
            }
        });
    });
</script>

<?= $this->endSection() ?>