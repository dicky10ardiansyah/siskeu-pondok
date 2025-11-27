<?= $this->extend('layouts/template/apps') ?>

<?php $this->setVar('title', 'Edit Jurnal'); ?>

<?= $this->section('content') ?>
<div class="row">
    <div class="col-md-8 offset-md-2">
        <div class="card">
            <div class="card-header">
                <h5>Edit Jurnal</h5>
            </div>
            <div class="card-body">
                <form action="<?= base_url('journals/update/' . $journal['id']) ?>" method="post">
                    <?= csrf_field() ?>

                    <!-- Tanggal -->
                    <div class="form-group">
                        <label for="date">Tanggal</label>
                        <input type="date"
                            class="form-control <?= session('errors.date') ? 'is-invalid' : '' ?>"
                            name="date"
                            id="date"
                            value="<?= old('date', $journal['date']) ?>">
                        <?php if (session('errors.date')) : ?>
                            <div class="invalid-feedback">
                                <?= session('errors.date') ?>
                            </div>
                        <?php endif ?>
                    </div>

                    <!-- Deskripsi -->
                    <div class="form-group">
                        <label for="description">Deskripsi</label>
                        <textarea name="description"
                            id="description"
                            class="form-control <?= session('errors.description') ? 'is-invalid' : '' ?>"
                            rows="3"><?= old('description', $journal['description']) ?></textarea>
                        <?php if (session('errors.description')) : ?>
                            <div class="invalid-feedback">
                                <?= session('errors.description') ?>
                            </div>
                        <?php endif ?>
                    </div>

                    <!-- Tabel Entries -->
                    <div class="form-group">
                        <label>Daftar Akun</label>
                        <table class="table table-bordered" id="entries-table">
                            <thead>
                                <tr>
                                    <th>Akun</th>
                                    <th>Tipe</th>
                                    <th>Nominal</th>
                                    <th>
                                        <button type="button" class="btn btn-sm btn-success" onclick="addRow()">
                                            <i class="fas fa-plus"></i>
                                        </button>
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $oldAccounts = old('account_id', array_column($entries, 'account_id'));
                                $oldDebits   = old('debit', array_column($entries, 'debit'));
                                $oldCredits  = old('credit', array_column($entries, 'credit'));

                                foreach ($oldAccounts as $i => $accId):
                                    $debit  = (float)($oldDebits[$i] ?? 0);
                                    $credit = (float)($oldCredits[$i] ?? 0);
                                    $type   = $debit > 0 ? 'debit' : 'credit';
                                    $amount = $debit > 0 ? $debit : $credit;
                                ?>
                                    <tr>
                                        <td>
                                            <select name="account_id[]" class="form-control">
                                                <option value="">-- Pilih Akun --</option>
                                                <?php foreach ($accounts as $account): ?>
                                                    <option value="<?= $account['id'] ?>" <?= $accId == $account['id'] ? 'selected' : '' ?>>
                                                        <?= esc($account['name']) ?> (<?= $account['code'] ?>)
                                                    </option>
                                                <?php endforeach ?>
                                            </select>
                                        </td>
                                        <td>
                                            <select name="type[]" class="form-control">
                                                <option value="debit" <?= $type == 'debit' ? 'selected' : '' ?>>Debit</option>
                                                <option value="credit" <?= $type == 'credit' ? 'selected' : '' ?>>Kredit</option>
                                            </select>
                                        </td>
                                        <td>
                                            <input type="text" class="form-control amount-input" value="<?= number_format($amount, 2, '.', ',') ?>">
                                            <input type="hidden" name="amount[]" class="amount-raw" value="<?= $amount ?>">
                                        </td>
                                        <td>
                                            <button type="button" class="btn btn-sm btn-danger" onclick="removeRow(this)">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach ?>
                            </tbody>
                        </table>

                        <?php if (session('errors.account_id')): ?>
                            <div class="text-danger"><?= session('errors.account_id') ?></div>
                        <?php endif ?>
                    </div>

                    <!-- Tombol -->
                    <div class="form-group mt-4">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Simpan Perubahan
                        </button>
                        <a href="<?= base_url('journals') ?>" class="btn btn-secondary">
                            <i class="fas fa-arrow-left"></i> Batal
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
    // Tambah baris baru
    function addRow() {
        const table = document.querySelector('#entries-table tbody');
        const row = document.createElement('tr');
        row.innerHTML = `
            <td>
                <select name="account_id[]" class="form-control">
                    <option value="">-- Pilih Akun --</option>
                    <?php foreach ($accounts as $account): ?>
                        <option value="<?= $account['id'] ?>"><?= esc($account['name']) ?> (<?= $account['code'] ?>)</option>
                    <?php endforeach ?>
                </select>
            </td>
            <td>
                <select name="type[]" class="form-control">
                    <option value="debit">Debit</option>
                    <option value="credit">Kredit</option>
                </select>
            </td>
            <td>
                <input type="text" class="form-control amount-input">
                <input type="hidden" name="amount[]" class="amount-raw">
            </td>
            <td>
                <button type="button" class="btn btn-sm btn-danger" onclick="removeRow(this)">
                    <i class="fas fa-trash"></i>
                </button>
            </td>
        `;
        table.appendChild(row);
        attachCurrencyFormatting(row.querySelector('.amount-input'));
    }

    // Hapus baris
    function removeRow(button) {
        button.closest('tr').remove();
    }

    // ============ Format Currency ============
    function formatNumber(value) {
        const parts = value.replace(/[^\d.]/g, '').split('.');
        parts[0] = parts[0].replace(/\B(?=(\d{3})+(?!\d))/g, ',');
        return parts.join('.');
    }

    function attachCurrencyFormatting(input) {
        const hidden = input.closest('td').querySelector('.amount-raw');
        input.addEventListener('input', function() {
            const raw = this.value.replace(/,/g, '');
            hidden.value = raw;
            this.value = formatNumber(raw);
        });
    }

    // Aktifkan format untuk semua input nominal yang sudah ada
    document.querySelectorAll('.amount-input').forEach(attachCurrencyFormatting);
</script>

<?= $this->endSection() ?>