<?= $this->extend('layouts/template/apps') ?>

<?php $this->setVar('title', 'Edit Data Siswa'); ?>

<?= $this->section('content') ?>
<div class="row">
    <div class="col-md-6 offset-md-3">
        <div class="card">
            <div class="card-header">
                <h5>Edit Data Siswa</h5>
            </div>
            <div class="card-body">

                <form action="<?= base_url('students/update/' . $student['id']) ?>" method="post">
                    <?= csrf_field() ?>

                    <div class="form-group">
                        <label for="name">Nama Siswa</label>
                        <input
                            type="text"
                            class="form-control <?= isset(session('errors')['name']) ? 'is-invalid' : '' ?>"
                            name="name"
                            id="name"
                            value="<?= old('name', $student['name']) ?>"
                            placeholder="Nama siswa...">
                        <?php if (isset(session('errors')['name'])) : ?>
                            <div class="invalid-feedback">
                                <?= session('errors')['name'] ?>
                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="form-group">
                        <label for="nis">NIS</label>
                        <input
                            type="text"
                            class="form-control <?= isset(session('errors')['nis']) ? 'is-invalid' : '' ?>"
                            name="nis"
                            id="nis"
                            value="<?= old('nis', $student['nis']) ?>"
                            placeholder="Nomor Induk Siswa">
                        <?php if (isset(session('errors')['nis'])) : ?>
                            <div class="invalid-feedback">
                                <?= session('errors')['nis'] ?>
                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="form-group">
                        <label for="class">Kelas</label>
                        <select
                            name="class"
                            id="class"
                            class="form-control <?= isset(session('errors')['class']) ? 'is-invalid' : '' ?>">
                            <option value="">-- Pilih Kelas --</option>
                            <?php foreach ($classes as $c) : ?>
                                <option value="<?= $c['id'] ?>" <?= old('class', $student['class']) == $c['id'] ? 'selected' : '' ?>>
                                    <?= esc($c['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <?php if (isset(session('errors')['class'])) : ?>
                            <div class="invalid-feedback">
                                <?= session('errors')['class'] ?>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Status Lulus -->
                    <div class="form-group form-check mt-3">
                        <input
                            type="checkbox"
                            class="form-check-input"
                            name="status"
                            id="status"
                            value="1"
                            <?= old('status', $student['status']) ? 'checked' : '' ?>>
                        <label class="form-check-label" for="status">Lulus</label>
                    </div>

                    <!-- Tahun Lulus -->
                    <div class="form-group mt-2">
                        <label for="school_year">Tahun Lulus</label>
                        <input
                            type="number"
                            class="form-control <?= isset(session('errors')['school_year']) ? 'is-invalid' : '' ?>"
                            name="school_year"
                            id="school_year"
                            value="<?= old('school_year', $student['school_year']) ?>"
                            placeholder="Contoh: 2025">
                        <?php if (isset(session('errors')['school_year'])) : ?>
                            <div class="invalid-feedback">
                                <?= session('errors')['school_year'] ?>
                            </div>
                        <?php endif; ?>
                        <small class="form-text text-muted">Isi hanya jika siswa sudah lulus.</small>
                    </div>

                    <div class="form-group mt-4">
                        <button type="submit" class="btn btn-warning">
                            <i class="fas fa-save"></i> Update
                        </button>
                        <a href="<?= base_url('students') ?>" class="btn btn-secondary">
                            <i class="fas fa-arrow-left"></i> Batal
                        </a>
                    </div>
                </form>

            </div>
        </div>
    </div>
</div>
<?= $this->endSection() ?>