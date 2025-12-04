<?= $this->extend('layouts/template/apps') ?>

<?php $this->setVar('title', 'Edit Pembayaran'); ?>

<?= $this->section('content') ?>
<div class="row">
    <div class="col-md-6 offset-md-3">
        <div class="card">
            <div class="card-header">
                <h5>Edit Data Pembayaran</h5>
            </div>
            <div class="card-body">
                <!-- Tambahkan enctype untuk upload -->
                <form action="<?= base_url('payments/update/' . $payment['id']) ?>" method="post" enctype="multipart/form-data">
                    <?= csrf_field() ?>

                    <!-- Student -->
                    <div class="form-group">
                        <label for="student_id">Student</label>
                        <select
                            name="student_id"
                            id="student_id"
                            class="form-control <?= isset(session('errors')['student_id']) ? 'is-invalid' : '' ?>">
                            <option value="">-- Pilih Student --</option>
                            <?php $oldStudent = old('student_id', $payment['student_id']); ?>
                            <?php foreach ($students as $student) : ?>
                                <option value="<?= $student['id'] ?>" <?= $oldStudent == $student['id'] ? 'selected' : '' ?>>
                                    <?= esc($student['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <?php if (isset(session('errors')['student_id'])) : ?>
                            <div class="invalid-feedback"><?= session('errors')['student_id'] ?></div>
                        <?php endif; ?>
                    </div>

                    <!-- Debit Account -->
                    <div class="form-group">
                        <label for="debit_account_id">Debit Account</label>
                        <select name="debit_account_id" id="debit_account_id" class="form-control <?= isset(session('errors')['debit_account_id']) ? 'is-invalid' : '' ?>">
                            <option value="">-- Pilih Debit Account --</option>
                            <?php $oldDebit = old('debit_account_id', $payment['debit_account_id']); ?>
                            <?php foreach ($debitAccounts as $account) : ?>
                                <option value="<?= $account['id'] ?>" <?= $oldDebit == $account['id'] ? 'selected' : '' ?>>
                                    <?= esc($account['name']) ?> (<?= esc($account['code']) ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <?php if (isset(session('errors')['debit_account_id'])) : ?>
                            <div class="invalid-feedback"><?= session('errors')['debit_account_id'] ?></div>
                        <?php endif; ?>
                    </div>

                    <!-- Credit Account -->
                    <div class="form-group">
                        <label for="credit_account_id">Credit Account</label>
                        <select name="credit_account_id" id="credit_account_id" class="form-control <?= isset(session('errors')['credit_account_id']) ? 'is-invalid' : '' ?>">
                            <option value="">-- Pilih Credit Account --</option>
                            <?php $oldCredit = old('credit_account_id', $payment['credit_account_id']); ?>
                            <?php foreach ($creditAccounts as $account) : ?>
                                <option value="<?= $account['id'] ?>" <?= $oldCredit == $account['id'] ? 'selected' : '' ?>>
                                    <?= esc($account['name']) ?> (<?= esc($account['code']) ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <?php if (isset(session('errors')['credit_account_id'])) : ?>
                            <div class="invalid-feedback"><?= session('errors')['credit_account_id'] ?></div>
                        <?php endif; ?>
                    </div>

                    <!-- Jumlah -->
                    <div class="form-group">
                        <label for="total_amount">Jumlah</label>
                        <?php $rawAmount = (int) old('total_amount', $payment['total_amount']); ?>
                        <input
                            type="text"
                            class="form-control <?= isset(session('errors')['total_amount']) ? 'is-invalid' : '' ?>"
                            name="total_amount"
                            id="total_amount"
                            value="<?= number_format($rawAmount, 0, ',', '.') ?>"
                            data-raw="<?= $rawAmount ?>"
                            placeholder="Masukkan jumlah pembayaran">
                        <?php if (isset(session('errors')['total_amount'])) : ?>
                            <div class="invalid-feedback"><?= session('errors')['total_amount'] ?></div>
                        <?php endif; ?>
                    </div>

                    <!-- Tanggal -->
                    <div class="form-group">
                        <label for="date">Tanggal</label>
                        <input
                            type="date"
                            class="form-control <?= isset(session('errors')['date']) ? 'is-invalid' : '' ?>"
                            name="date"
                            id="date"
                            value="<?= old('date', $payment['date']) ?>">
                        <?php if (isset(session('errors')['date'])) : ?>
                            <div class="invalid-feedback"><?= session('errors')['date'] ?></div>
                        <?php endif; ?>
                    </div>

                    <!-- Metode -->
                    <div class="form-group">
                        <label for="method">Metode</label>
                        <select
                            name="method"
                            id="method"
                            class="form-control <?= isset(session('errors')['method']) ? 'is-invalid' : '' ?>">
                            <option value="">-- Pilih Metode --</option>
                            <?php $oldMethod = old('method', $payment['method']); ?>
                            <option value="cash" <?= $oldMethod === 'cash' ? 'selected' : '' ?>>Cash</option>
                            <option value="transfer" <?= $oldMethod === 'transfer' ? 'selected' : '' ?>>Transfer</option>
                        </select>
                        <?php if (isset(session('errors')['method'])) : ?>
                            <div class="invalid-feedback"><?= session('errors')['method'] ?></div>
                        <?php endif; ?>
                    </div>

                    <!-- File Referensi -->
                    <div class="form-group">
                        <label for="reference_file">Upload Referensi (Foto / PDF)</label>
                        <input type="file" name="reference_file" id="reference_file"
                            class="form-control <?= isset(session('errors')['reference_file']) ? 'is-invalid' : '' ?>"
                            accept=".jpg,.jpeg,.png,.pdf">
                        <?php if (isset(session('errors')['reference_file'])) : ?>
                            <div class="invalid-feedback"><?= session('errors')['reference_file'] ?></div>
                        <?php endif; ?>

                        <!-- Preview file lama -->
                        <div id="preview" class="mt-2">
                            <?php if (!empty($payment['reference_file'])): ?>
                                <?php $ext = pathinfo($payment['reference_file'], PATHINFO_EXTENSION); ?>
                                <?php if (in_array(strtolower($ext), ['jpg', 'jpeg', 'png'])): ?>
                                    <img src="<?= base_url('uploads/' . $payment['reference_file']) ?>" style="max-width:150px; height:auto;">
                                <?php elseif (strtolower($ext) === 'pdf'): ?>
                                    <a href="<?= base_url('uploads/' . $payment['reference_file']) ?>" target="_blank">Lihat PDF</a>
                                <?php endif; ?>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="form-group mt-4">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Update
                        </button>
                        <a href="<?= base_url('payments') ?>" class="btn btn-secondary">
                            <i class="fas fa-arrow-left"></i> Batal
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Choices.js -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/choices.js/public/assets/styles/choices.min.css" />
<script src="https://cdn.jsdelivr.net/npm/choices.js/public/assets/scripts/choices.min.js"></script>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Choices.js
        new Choices('#student_id', {
            searchPlaceholderValue: 'Cari student...',
            shouldSort: false
        });
        new Choices('#debit_account_id', {
            searchPlaceholderValue: 'Cari debit account...',
            shouldSort: false
        });
        new Choices('#credit_account_id', {
            searchPlaceholderValue: 'Cari credit account...',
            shouldSort: false
        });

        // Format jumlah
        const totalAmount = document.getElementById('total_amount');

        function formatNumber(value) {
            if (!value) return '';
            return value.replace(/\B(?=(\d{3})+(?!\d))/g, '.');
        }

        function setCursorPosition(el, oldPos, oldLength, newLength) {
            let newPos = oldPos + (newLength - oldLength);
            if (newPos > newLength) newPos = newLength;
            el.setSelectionRange(newPos, newPos);
        }
        let rawInit = totalAmount.getAttribute('data-raw');
        totalAmount.dataset.raw = rawInit;
        totalAmount.value = formatNumber(rawInit);

        totalAmount.addEventListener('input', function() {
            const oldValue = totalAmount.value;
            const oldLength = oldValue.length;
            const cursorPosition = totalAmount.selectionStart;
            const rawValue = oldValue.replace(/[^0-9]/g, '');
            totalAmount.dataset.raw = rawValue;
            const formattedValue = formatNumber(rawValue);
            totalAmount.value = formattedValue;
            setCursorPosition(totalAmount, cursorPosition, oldLength, formattedValue.length);
        });

        totalAmount.form.addEventListener('submit', function() {
            totalAmount.value = totalAmount.dataset.raw;
        });

        // Preview file baru
        const referenceFile = document.getElementById('reference_file');
        const preview = document.getElementById('preview');
        referenceFile.addEventListener('change', function() {
            preview.innerHTML = '';
            const file = this.files[0];
            if (!file) return;

            const ext = file.name.split('.').pop().toLowerCase();
            if (['jpg', 'jpeg', 'png'].includes(ext)) {
                const img = document.createElement('img');
                img.src = URL.createObjectURL(file);
                img.style.maxWidth = '150px';
                img.style.height = 'auto';
                preview.appendChild(img);
            } else if (ext === 'pdf') {
                const link = document.createElement('a');
                link.href = URL.createObjectURL(file);
                link.textContent = 'Preview PDF';
                link.target = '_blank';
                preview.appendChild(link);
            }
        });
    });
</script>

<?= $this->endSection() ?>