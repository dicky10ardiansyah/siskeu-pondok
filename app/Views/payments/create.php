<?= $this->extend('layouts/template/apps') ?>
<?php $this->setVar('title', 'Tambah Pembayaran'); ?>
<?= $this->section('content') ?>

<div class="row">
    <div class="col-md-6 offset-md-3">
        <div class="card">
            <div class="card-header">
                <h5>Tambah Data Pembayaran</h5>
            </div>
            <div class="card-body">
                <form action="<?= base_url('payments/store') ?>" method="post" enctype="multipart/form-data">
                    <?= csrf_field() ?>

                    <!-- Student -->
                    <div class="form-group <?= isset(session('errors')['student_id']) ? 'is-invalid' : '' ?>">
                        <label for="student_id">Student</label>
                        <select name="student_id" id="student_id" class="form-control">
                            <option value="">-- Pilih Student --</option>
                            <?php $oldStudent = old('student_id', $selectedStudentId ?? ''); ?>
                            <?php foreach ($students as $student) : ?>
                                <option value="<?= $student['id'] ?>" <?= $oldStudent == $student['id'] ? 'selected' : '' ?>>
                                    <?= esc($student['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <?php if (isset(session('errors')['student_id'])) : ?>
                            <div class="invalid-feedback d-block"><?= session('errors')['student_id'] ?></div>
                        <?php endif; ?>
                    </div>

                    <!-- Debit Account -->
                    <div class="form-group <?= isset(session('errors')['debit_account_id']) ? 'is-invalid' : '' ?>">
                        <label for="debit_account_id">Debit Account</label>
                        <select name="debit_account_id" id="debit_account_id" class="form-control">
                            <option value="">-- Pilih Debit Account --</option>
                            <?php $oldDebit = old('debit_account_id'); ?>
                            <?php foreach ($debitAccounts as $account) : ?>
                                <option value="<?= $account['id'] ?>" <?= $oldDebit == $account['id'] ? 'selected' : '' ?>>
                                    <?= esc($account['name']) ?> (<?= esc($account['code']) ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <?php if (isset(session('errors')['debit_account_id'])) : ?>
                            <div class="invalid-feedback d-block"><?= session('errors')['debit_account_id'] ?></div>
                        <?php endif; ?>
                    </div>

                    <!-- Credit Account -->
                    <div class="form-group <?= isset(session('errors')['credit_account_id']) ? 'is-invalid' : '' ?>">
                        <label for="credit_account_id">Credit Account</label>
                        <select name="credit_account_id" id="credit_account_id" class="form-control">
                            <option value="">-- Pilih Credit Account --</option>
                            <?php $oldCredit = old('credit_account_id'); ?>
                            <?php foreach ($creditAccounts as $account) : ?>
                                <option value="<?= $account['id'] ?>" <?= $oldCredit == $account['id'] ? 'selected' : '' ?>>
                                    <?= esc($account['name']) ?> (<?= esc($account['code']) ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <?php if (isset(session('errors')['credit_account_id'])) : ?>
                            <div class="invalid-feedback d-block"><?= session('errors')['credit_account_id'] ?></div>
                        <?php endif; ?>
                    </div>

                    <!-- Jumlah -->
                    <div class="form-group <?= isset(session('errors')['total_amount']) ? 'is-invalid' : '' ?>">
                        <label for="total_amount">Jumlah</label>
                        <input type="text" class="form-control currency"
                            name="total_amount" id="total_amount"
                            placeholder="Masukkan jumlah pembayaran"
                            value="<?= old('total_amount') ?>">
                        <?php if (isset(session('errors')['total_amount'])) : ?>
                            <div class="invalid-feedback d-block"><?= session('errors')['total_amount'] ?></div>
                        <?php endif; ?>
                    </div>

                    <!-- Tanggal -->
                    <div class="form-group <?= isset(session('errors')['date']) ? 'is-invalid' : '' ?>">
                        <label for="date">Tanggal</label>
                        <input type="date" class="form-control" name="date" id="date" value="<?= old('date') ?>">
                        <?php if (isset(session('errors')['date'])) : ?>
                            <div class="invalid-feedback d-block"><?= session('errors')['date'] ?></div>
                        <?php endif; ?>
                    </div>

                    <!-- Metode -->
                    <div class="form-group <?= isset(session('errors')['method']) ? 'is-invalid' : '' ?>">
                        <label for="method">Metode</label>
                        <select name="method" id="method" class="form-control">
                            <option value="">-- Pilih Metode --</option>
                            <?php $oldMethod = old('method'); ?>
                            <option value="cash" <?= $oldMethod === 'cash' ? 'selected' : '' ?>>Cash</option>
                            <option value="transfer" <?= $oldMethod === 'transfer' ? 'selected' : '' ?>>Transfer</option>
                        </select>
                        <?php if (isset(session('errors')['method'])) : ?>
                            <div class="invalid-feedback d-block"><?= session('errors')['method'] ?></div>
                        <?php endif; ?>
                    </div>

                    <!-- Reference File -->
                    <div class="form-group <?= isset(session('errors')['reference_file']) ? 'is-invalid' : '' ?>">
                        <label for="reference_file">File Referensi (Gambar / PDF)</label>
                        <input type="file" class="form-control" name="reference_file" id="reference_file">
                        <?php if (isset(session('errors')['reference_file'])) : ?>
                            <div class="invalid-feedback d-block"><?= session('errors')['reference_file'] ?></div>
                        <?php endif; ?>
                        <div id="preview" class="mt-2"></div>
                    </div>

                    <div class="form-group mt-4">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Simpan
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

<!-- Choices.js CSS & JS -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/choices.js/public/assets/styles/choices.min.css" />
<script src="https://cdn.jsdelivr.net/npm/choices.js/public/assets/scripts/choices.min.js"></script>

<script>
    document.addEventListener('DOMContentLoaded', function() {

        // ==========================
        // Choices.js untuk select
        // ==========================
        const studentSelect = new Choices('#student_id', {
            shouldSort: false,
            searchPlaceholderValue: 'Cari student...'
        });
        const debitSelect = new Choices('#debit_account_id', {
            shouldSort: false,
            searchPlaceholderValue: 'Cari debit account...'
        });
        const creditSelect = new Choices('#credit_account_id', {
            shouldSort: false,
            searchPlaceholderValue: 'Cari credit account...'
        });

        // ==========================
        // Format Rupiah dengan prefix "Rp"
        // ==========================
        const currencyInputs = document.querySelectorAll('.currency');

        function formatRupiah(value) {
            value = value.replace(/\D/g, ''); // hanya angka
            if (!value) return '';
            return 'Rp ' + value.replace(/\B(?=(\d{3})+(?!\d))/g, '.');
        }

        currencyInputs.forEach(input => {
            // Format nilai awal
            if (input.value) input.value = formatRupiah(input.value);

            // Live formatting
            input.addEventListener('input', function() {
                const cursorPos = this.selectionStart;
                const oldLength = this.value.length;

                this.value = formatRupiah(this.value);

                const newLength = this.value.length;
                this.setSelectionRange(cursorPos + (newLength - oldLength), cursorPos + (newLength - oldLength));
            });
        });

        // Hapus Rp dan titik saat submit
        document.querySelectorAll('form').forEach(form => {
            form.addEventListener('submit', function() {
                currencyInputs.forEach(input => {
                    input.value = input.value.replace(/[^0-9]/g, ''); // hapus semua non-digit
                });
            });
        });

        // ==========================
        // Preview file (Gambar / PDF)
        // ==========================
        const referenceFile = document.getElementById('reference_file');
        const preview = document.getElementById('preview');

        referenceFile.addEventListener('change', function() {
            preview.innerHTML = '';
            const file = this.files[0];
            if (!file) return;

            const reader = new FileReader();
            reader.onload = function(e) {
                if (file.type.startsWith('image/')) {
                    preview.innerHTML = `<img src="${e.target.result}" class="img-thumbnail" style="max-width:150px">`;
                } else if (file.type === 'application/pdf') {
                    preview.innerHTML = `<i class="fas fa-file-pdf fa-3x text-danger"></i> ${file.name}`;
                } else {
                    preview.innerHTML = file.name;
                }
            };
            reader.readAsDataURL(file);
        });

    });
</script>

<?= $this->endSection() ?>