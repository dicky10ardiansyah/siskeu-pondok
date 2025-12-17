<?= $this->extend('layouts/template/apps') ?>

<?php $this->setVar('title', 'Edit Class'); ?>

<?= $this->section('content') ?>
<div class="row">
    <div class="col-md-6 offset-md-3">
        <div class="card">
            <div class="card-header">
                <h5>Edit Data Kelas</h5>
            </div>

            <div class="card-body">
                <form action="<?= base_url('classes/update/' . $class['id']) ?>" method="post">
                    <?= csrf_field() ?>

                    <!-- Nama Kelas -->
                    <div class="form-group">
                        <label for="name">Nama Kelas</label>
                        <input
                            type="text"
                            class="form-control <?= isset(session('errors')['name']) ? 'is-invalid' : '' ?>"
                            name="name"
                            id="name"
                            value="<?= old('name', $class['name']) ?>"
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
                            <select name="user_id" id="user_id" class="form-control">
                                <?php foreach ($users as $user) : ?>
                                    <option value="<?= $user['id'] ?>"
                                        <?= $user['id'] == $class['user_id'] ? 'selected' : '' ?>>
                                        <?= esc($user['name']) ?>
                                    </option>
                                <?php endforeach ?>
                            </select>
                        </div>
                    <?php endif; ?>

                    <!-- Tombol -->
                    <div class="form-group mt-4">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Update
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