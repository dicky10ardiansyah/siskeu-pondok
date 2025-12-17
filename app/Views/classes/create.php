<?= $this->extend('layouts/template/apps') ?>

<?php $this->setVar('title', 'Add Class'); ?>

<?= $this->section('content') ?>
<div class="row">
    <div class="col-md-6 offset-md-3">
        <div class="card">
            <div class="card-header">
                <h5>Tambah Data Kelas</h5>
            </div>

            <div class="card-body">
                <form action="<?= base_url('classes/store') ?>" method="post">
                    <?= csrf_field() ?>

                    <!-- Nama Kelas -->
                    <div class="form-group">
                        <label for="name">Nama Kelas</label>
                        <input
                            type="text"
                            class="form-control <?= isset(session('errors')['name']) ? 'is-invalid' : '' ?>"
                            name="name"
                            id="name"
                            value="<?= old('name') ?>"
                            placeholder="Contoh: Kelas 1A, Kimia Dasar, XII IPA 3">

                        <?php if (isset(session('errors')['name'])) : ?>
                            <div class="invalid-feedback">
                                <?= session('errors')['name'] ?>
                            </div>
                        <?php endif; ?>
                    </div>

                    <?php if (session()->get('user_role') === 'admin') : ?>
                        <div class="form-group mt-3">
                            <label for="user_id">Pemilik Kelas</label>
                            <select name="user_id" id="user_id" class="form-control" required>
                                <option value="">-- Pilih User --</option>
                                <?php foreach ($users as $user) : ?>
                                    <option value="<?= $user['id'] ?>">
                                        <?= esc($user['name']) ?>
                                    </option>
                                <?php endforeach ?>
                            </select>
                        </div>
                    <?php endif; ?>

                    <!-- Tombol -->
                    <div class="form-group mt-4">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Simpan
                        </button>

                        <a href="<?= base_url('classes') ?>" class="btn btn-secondary">
                            <i class="fas fa-arrow-left"></i> Batal
                        </a>
                    </div>

                </form>
            </div>
        </div>
    </div>
</div>

<?= $this->endSection() ?>