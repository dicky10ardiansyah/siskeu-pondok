<?= $this->extend('layouts/template/apps') ?>

<?php $this->setVar('title', 'Edit Pembayaran'); ?>

<?= $this->section('content') ?>
<div class="row">
    <div class="col-md-6 offset-md-3">
        <div class="card">
            <div class="card-header">
                <h5>Edit Pembayaran Orang Tua</h5>
            </div>
            <div class="card-body">
                <form action="<?= base_url('parent-payments/update/' . $payment['id']) ?>" method="post" enctype="multipart/form-data">
                    <?= csrf_field() ?>

                    <!-- SISWA -->
                    <div class="form-group">
                        <label for="student_id">Siswa</label>
                        <select
                            name="student_id"
                            id="student_id"
                            class="form-control choices <?= isset(session('errors')['student_id']) ? 'is-invalid' : '' ?>">
                            <option value="">-- Pilih Siswa --</option>
                            <?php foreach ($students as $student) : ?>
                                <option value="<?= $student['id'] ?>"
                                    <?= old('student_id', $payment['student_id'] ?? '') == $student['id'] ? 'selected' : '' ?>>
                                    <?= esc($student['name']) ?>
                                    (<?= esc($student['class_name'] ?? '-') ?> | NIS: <?= esc($student['nis']) ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <?php if (isset(session('errors')['student_id'])) : ?>
                            <div class="invalid-feedback">
                                <?= session('errors')['student_id'] ?>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- KELAS -->
                    <div class="form-group">
                        <label for="class_id">Kelas</label>
                        <select
                            name="class_id"
                            id="class_id"
                            class="form-control choices <?= isset(session('errors')['class_id']) ? 'is-invalid' : '' ?>">
                            <option value="">-- Pilih Kelas --</option>
                            <?php foreach ($classes as $class) : ?>
                                <option value="<?= $class['id'] ?>" <?= (old('class_id', $payment['class_id']) == $class['id']) ? 'selected' : '' ?>>
                                    <?= esc($class['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <?php if (isset(session('errors')['class_id'])) : ?>
                            <div class="invalid-feedback">
                                <?= session('errors')['class_id'] ?>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- NAMA AKUN -->
                    <div class="form-group">
                        <label for="account_name">Rekening tujuan</label>
                        <input
                            type="text"
                            class="form-control <?= isset(session('errors')['account_name']) ? 'is-invalid' : '' ?>"
                            name="account_name"
                            id="account_name"
                            value="<?= old('account_name', $payment['account_name']) ?>"
                            placeholder="Contoh: Bank BNI">
                        <?php if (isset(session('errors')['account_name'])) : ?>
                            <div class="invalid-feedback">
                                <?= session('errors')['account_name'] ?>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- JUMLAH -->
                    <div class="form-group">
                        <label for="amount">Jumlah Pembayaran</label>
                        <input
                            type="text"
                            class="form-control <?= isset(session('errors')['amount']) ? 'is-invalid' : '' ?>"
                            name="amount"
                            id="amount"
                            value="<?= old('amount', number_format($payment['amount'], 0, '.', '')) ?>"
                            placeholder="Contoh: 500000">
                        <?php if (isset(session('errors')['amount'])) : ?>
                            <div class="invalid-feedback">
                                <?= session('errors')['amount'] ?>
                            </div>
                        <?php endif; ?>
                        <small class="text-muted">Format tampilan saja, angka asli akan disimpan tanpa pemisah ribuan.</small>
                    </div>

                    <!-- BUKTI -->
                    <div class="form-group">
                        <label for="photo">Bukti Pembayaran</label>
                        <input
                            type="file"
                            class="form-control-file <?= isset(session('errors')['photo']) ? 'is-invalid' : '' ?>"
                            name="photo"
                            id="photo"
                            accept="image/*">
                        <?php if (isset(session('errors')['photo'])) : ?>
                            <div class="invalid-feedback d-block">
                                <?= session('errors')['photo'] ?>
                            </div>
                        <?php endif; ?>
                        <small class="text-muted">JPG / PNG • Maks 2MB</small>

                        <!-- preview image -->
                        <div class="mt-2">
                            <img id="photoPreview" src="<?= $payment['photo'] ? base_url('uploads/payment_parents/' . $payment['photo']) : '#' ?>"
                                alt="Preview Bukti"
                                style="max-width:100%; height:auto; border:1px solid #ddd; padding:5px;"
                                <?= $payment['photo'] ? '' : 'style="display:none;"' ?>>
                        </div>
                    </div>

                    <div class="form-group mt-4">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Update
                        </button>
                        <a href="<?= base_url('parent-payments') ?>" class="btn btn-secondary">
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
            searchEnabled: true,
            itemSelectText: '',
            shouldSort: false
        });
        new Choices('#class_id', {
            searchEnabled: true,
            itemSelectText: '',
            shouldSort: false
        });

        // Format currency sementara (tampilan saja)
        const amountInput = document.getElementById('amount');
        amountInput.addEventListener('input', function(e) {
            let value = this.value.replace(/\D/g, '');
            this.value = value ? parseInt(value).toLocaleString('id-ID') : '';
        });

        // Preview image
        const photoInput = document.getElementById('photo');
        const photoPreview = document.getElementById('photoPreview');
        photoInput.addEventListener('change', function() {
            const file = this.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    photoPreview.src = e.target.result;
                    photoPreview.style.display = 'block';
                }
                reader.readAsDataURL(file);
            } else if (!photoPreview.src.includes('uploads/payment_parents')) {
                photoPreview.style.display = 'none';
            }
        });
    });
</script>

<?= $this->endSection() ?>