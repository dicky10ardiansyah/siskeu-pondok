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
                        <select
                            name="class"
                            id="class"
                            class="form-control <?= isset(session('errors')['class']) ? 'is-invalid' : '' ?>">
                            <option value="">-- Pilih Kelas --</option>
                            <?php foreach ($classes as $c) : ?>
                                <option value="<?= $c['id'] ?>" <?= old('class') == $c['id'] ? 'selected' : '' ?>>
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

                    <div class="form-group">
                        <label for="address">Alamat</label>
                        <textarea
                            name="address"
                            id="address"
                            class="form-control <?= isset(session('errors')['address']) ? 'is-invalid' : '' ?>"
                            rows="4"><?= old('address') ?></textarea>

                        <?php if (isset(session('errors')['address'])) : ?>
                            <div class="invalid-feedback">
                                <?= session('errors')['address'] ?>
                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="form-group">
                        <label for="parent_name">Nama Orang Tua/Wali</label>
                        <input
                            type="text"
                            class="form-control <?= isset(session('errors')['parent_name']) ? 'is-invalid' : '' ?>"
                            name="parent_name"
                            id="parent_name"
                            value="<?= old('parent_name') ?>"
                            placeholder="Nama orang tua...">
                        <?php if (isset(session('errors')['parent_name'])) : ?>
                            <div class="invalid-feedback">
                                <?= session('errors')['parent_name'] ?>
                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="form-group">
                        <label for="phone">Nomor Hp</label>
                        <input
                            type="number"
                            class="form-control <?= isset(session('errors')['phone']) ? 'is-invalid' : '' ?>"
                            name="phone"
                            id="phone"
                            value="<?= old('phone') ?>"
                            placeholder="Nomor hp...">
                        <?php if (isset(session('errors')['phone'])) : ?>
                            <div class="invalid-feedback">
                                <?= session('errors')['phone'] ?>
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

                    <?php if (!empty($users)) : ?>
                        <div class="form-group mt-3">
                            <label for="user_id">User</label>
                            <select
                                name="user_id"
                                id="user_id"
                                class="form-control <?= isset(session('errors')['user_id']) ? 'is-invalid' : '' ?>">
                                <option value="">-- Pilih User --</option>
                                <?php foreach ($users as $user) : ?>
                                    <option value="<?= $user['id'] ?>" <?= old('user_id', isset($student['user_id']) ? $student['user_id'] : '') == $user['id'] ? 'selected' : '' ?>>
                                        <?= esc($user['name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <?php if (isset(session('errors')['user_id'])) : ?>
                                <div class="invalid-feedback">
                                    <?= session('errors')['user_id'] ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>

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