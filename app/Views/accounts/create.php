<?= $this->extend('layouts/template/apps') ?>

<?php $this->setVar('title', 'Add Account'); ?>

<?= $this->section('content') ?>
<div class="row">
    <div class="col-md-6 offset-md-3">
        <div class="card">
            <div class="card-header">
                <h5>Tambah Data Akun</h5>
            </div>
            <div class="card-body">
                <form action="<?= base_url('accounts/store') ?>" method="post">
                    <?= csrf_field() ?>

                    <div class="form-group">
                        <label for="name">Nama Akun</label>
                        <input
                            type="text"
                            class="form-control <?= isset(session('errors')['name']) ? 'is-invalid' : '' ?>"
                            name="name"
                            id="name"
                            value="<?= old('name') ?>"
                            placeholder="Contoh: Kas, Pendapatan, Biaya Listrik">
                        <?php if (isset(session('errors')['name'])) : ?>
                            <div class="invalid-feedback">
                                <?= session('errors')['name'] ?>
                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="form-group">
                        <label for="type">Tipe Akun</label>
                        <select
                            name="type"
                            id="type"
                            class="form-control <?= isset(session('errors')['type']) ? 'is-invalid' : '' ?>">
                            <option value="">-- Pilih Tipe Akun --</option>
                            <?php $oldType = old('type'); ?>
                            <option value="asset" <?= $oldType === 'asset' ? 'selected' : '' ?>>Asset (Aset)</option>
                            <option value="income" <?= $oldType === 'income' ? 'selected' : '' ?>>Income (Pendapatan)</option>
                            <option value="expense" <?= $oldType === 'expense' ? 'selected' : '' ?>>Expense (Biaya)</option>
                            <option value="liability" <?= $oldType === 'liability' ? 'selected' : '' ?>>Liability (Hutang)</option>
                            <option value="equity" <?= $oldType === 'equity' ? 'selected' : '' ?>>Equity (Modal)</option>
                        </select>
                        <?php if (isset(session('errors')['type'])) : ?>
                            <div class="invalid-feedback">
                                <?= session('errors')['type'] ?>
                            </div>
                        <?php endif; ?>
                    </div>

                    <?php if (session()->get('user_role') === 'admin') : ?>
                        <div class="form-group">
                            <label for="user_id">User</label>
                            <select name="user_id" id="user_id" class="form-control" required>
                                <option value="">-- Pilih User --</option>
                                <?php foreach ($users as $user) : ?>
                                    <option value="<?= $user['id'] ?>" <?= old('user_id') == $user['id'] ? 'selected' : '' ?>>
                                        <?= esc($user['name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    <?php endif; ?>

                    <div class="form-group mt-4">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Simpan
                        </button>
                        <a href="<?= base_url('accounts') ?>" class="btn btn-secondary">
                            <i class="fas fa-arrow-left"></i> Batal
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
<?= $this->endSection() ?>