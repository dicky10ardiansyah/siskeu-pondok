<?= $this->extend('layouts/template/apps') ?>

<?php $this->setVar('title', 'Tambah Data Siswa'); ?>

<?= $this->section('content') ?>
<div class="row">
    <div class="col-md-6 offset-md-3">
        <div class="card">
            <div class="card-header">
                <h5>Tambah Data Siswa</h5>
            </div>
            <div class="card-body">

                <form action="<?= base_url('students/store') ?>" method="post">
                    <?= csrf_field() ?>

                    <div class="form-group">
                        <label for="name">Nama Siswa</label>
                        <input
                            type="text"
                            class="form-control <?= isset(session('errors')['name']) ? 'is-invalid' : '' ?>"
                            name="name"
                            id="name"
                            value="<?= old('name') ?>"
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
                            value="<?= old('nis') ?>"
                            placeholder="Nomor Induk Siswa">
                        <?php if (isset(session('errors')['nis'])) : ?>
                            <div class="invalid-feedback">
                                <?= session('errors')['nis'] ?>
                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="form-group">
                        <label for="class">Kelas</label>
                        <input
                            type="text"
                            class="form-control <?= isset(session('errors')['class']) ? 'is-invalid' : '' ?>"
                            name="class"
                            id="class"
                            value="<?= old('class') ?>"
                            placeholder="Contoh: X IPA 1 / XII RPL / VII B">
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
                            <?= old('status') ? 'checked' : '' ?>>
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
                            value="<?= old('school_year') ?>"
                            placeholder="Contoh: 2025">
                        <?php if (isset(session('errors')['school_year'])) : ?>
                            <div class="invalid-feedback">
                                <?= session('errors')['school_year'] ?>
                            </div>
                        <?php endif; ?>
                        <small class="form-text text-muted">Isi hanya jika siswa sudah lulus.</small>
                    </div>

                    <div class="form-group mt-4">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Simpan
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