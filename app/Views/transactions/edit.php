<?= $this->extend('layouts/template/apps') ?>

<?php $this->setVar('title', 'Edit Transaksi'); ?>

<?= $this->section('content') ?>
<div class="row">
    <div class="col-md-6 offset-md-3">
        <div class="card">
            <div class="card-header">
                <h5>Edit Transaksi</h5>
            </div>
            <div class="card-body">
                <form action="/transactions/update/<?= $transaction['id'] ?>" method="post" enctype="multipart/form-data">
                    <?= csrf_field() ?>
                    <input type="hidden" name="_method" value="PUT">

                    <!-- Tanggal -->
                    <div class="form-group">
                        <label for="date">Tanggal</label>
                        <input type="date" class="form-control <?= session('errors.date') ? 'is-invalid' : '' ?>" name="date" id="date" value="<?= old('date', $transaction['date']) ?>">
                        <?php if (session('errors.date')) : ?>
                            <div class="invalid-feedback"><?= session('errors.date') ?></div>
                        <?php endif ?>
                    </div>

                    <!-- Deskripsi -->
                    <div class="form-group">
                        <label for="description">Deskripsi</label>
                        <input type="text" class="form-control <?= session('errors.description') ? 'is-invalid' : '' ?>" name="description" id="description" value="<?= old('description', $transaction['description']) ?>">
                        <?php if (session('errors.description')) : ?>
                            <div class="invalid-feedback"><?= session('errors.description') ?></div>
                        <?php endif ?>
                    </div>

                    <!-- Type -->
                    <div class="form-group">
                        <label for="type">Type</label>
                        <select name="type" id="type" class="form-control <?= session('errors.type') ? 'is-invalid' : '' ?>">
                            <option value="">-- Pilih Type --</option>
                            <option value="income" <?= old('type', $transaction['type']) === 'income' ? 'selected' : '' ?>>Income</option>
                            <option value="expense" <?= old('type', $transaction['type']) === 'expense' ? 'selected' : '' ?>>Expense</option>
                        </select>
                        <?php if (session('errors.type')) : ?>
                            <div class="invalid-feedback"><?= session('errors.type') ?></div>
                        <?php endif ?>
                    </div>

                    <!-- Amount -->
                    <div class="form-group">
                        <label for="amount">Jumlah</label>
                        <input type="text"
                            class="form-control <?= session('errors.amount') ? 'is-invalid' : '' ?>"
                            id="amount"
                            value="<?= old('amount', number_format($transaction['amount'], 2, '.', ',')) ?>">
                        <input type="hidden" name="amount" id="amount_raw" value="<?= old('amount', $transaction['amount']) ?>">

                        <?php if (session('errors.amount')) : ?>
                            <div class="invalid-feedback"><?= session('errors.amount') ?></div>
                        <?php endif ?>
                    </div>

                    <!-- Debit Account -->
                    <div class="form-group">
                        <label for="debit_account_id">Akun Debit</label>
                        <select name="debit_account_id" id="debit_account_id" class="form-control <?= session('errors.debit_account_id') ? 'is-invalid' : '' ?>">
                            <option value="">-- Pilih Akun Debit --</option>
                            <?php foreach ($debitAccounts as $acc) : ?>
                                <option value="<?= $acc['id'] ?>" <?= old('debit_account_id', $transaction['debit_account_id']) == $acc['id'] ? 'selected' : '' ?>>
                                    <?= $acc['code'] . ' - ' . $acc['name'] ?>
                                </option>
                            <?php endforeach ?>
                        </select>
                        <?php if (session('errors.debit_account_id')) : ?>
                            <div class="invalid-feedback"><?= session('errors.debit_account_id') ?></div>
                        <?php endif ?>
                    </div>

                    <!-- Credit Account -->
                    <div class="form-group">
                        <label for="credit_account_id">Akun Kredit</label>
                        <select name="credit_account_id" id="credit_account_id" class="form-control <?= session('errors.credit_account_id') ? 'is-invalid' : '' ?>">
                            <option value="">-- Pilih Akun Kredit --</option>
                            <?php foreach ($creditAccounts as $acc) : ?>
                                <option value="<?= $acc['id'] ?>" <?= old('credit_account_id', $transaction['credit_account_id']) == $acc['id'] ? 'selected' : '' ?>>
                                    <?= $acc['code'] . ' - ' . $acc['name'] ?>
                                </option>
                            <?php endforeach ?>
                        </select>
                        <?php if (session('errors.credit_account_id')) : ?>
                            <div class="invalid-feedback"><?= session('errors.credit_account_id') ?></div>
                        <?php endif ?>
                    </div>

                    <!-- Bukti Upload (opsional) -->
                    <div class="form-group">
                        <label for="proof">Bukti (Gambar / PDF)</label>
                        <input type="file" name="proof" id="proof" class="form-control <?= session('errors.proof') ? 'is-invalid' : '' ?>">
                        <?php if (!empty($transaction['proof'])) : ?>
                            <small>File saat ini: <a href="<?= base_url($transaction['proof']) ?>" target="_blank"><?= basename($transaction['proof']) ?></a></small>
                        <?php endif ?>
                        <?php if (session('errors.proof')) : ?>
                            <div class="invalid-feedback"><?= session('errors.proof') ?></div>
                        <?php endif ?>
                    </div>

                    <?php if ($role === 'admin') : ?>
                        <div class="form-group">
                            <label for="user_id">User</label>
                            <select name="user_id" id="user_id" class="form-control">
                                <option value="">-- Pilih User --</option>
                                <?php foreach ($users as $u) : ?>
                                    <option value="<?= $u['id'] ?>" <?= old('user_id', $transaction['user_id']) == $u['id'] ? 'selected' : '' ?>>
                                        <?= esc($u['name']) ?>
                                    </option>
                                <?php endforeach ?>
                            </select>
                        </div>
                    <?php endif ?>

                    <button type="submit" class="btn btn-primary">Update</button>
                    <a href="/transactions" class="btn btn-secondary">Batal</a>
                </form>
            </div>
        </div>
    </div>
</div>

<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/choices.js/public/assets/styles/choices.min.css">
<script src="https://cdn.jsdelivr.net/npm/choices.js/public/assets/scripts/choices.min.js"></script>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Format angka
        const amountInput = document.getElementById('amount');
        const amountRaw = document.getElementById('amount_raw');

        function formatNumber(value) {
            const parts = value.replace(/[^\d.]/g, '').split('.');
            parts[0] = parts[0].replace(/\B(?=(\d{3})+(?!\d))/g, ',');
            return parts.join('.');
        }

        amountInput.addEventListener('input', function() {
            const raw = this.value.replace(/,/g, '');
            amountRaw.value = raw;
            this.value = formatNumber(raw);
        });
        amountInput.value = formatNumber(amountInput.value);

        // Choices.js
        new Choices('#debit_account_id', {
            searchEnabled: true,
            itemSelectText: '',
            shouldSort: false,
            placeholder: true,
            placeholderValue: '-- Pilih Akun Debit --'
        });
        new Choices('#credit_account_id', {
            searchEnabled: true,
            itemSelectText: '',
            shouldSort: false,
            placeholder: true,
            placeholderValue: '-- Pilih Akun Kredit --'
        });
    });
</script>

<?= $this->endSection() ?>