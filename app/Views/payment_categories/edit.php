<?= $this->extend('layouts/template/apps') ?>

<?php $this->setVar('title', 'Edit Kategori Pembayaran'); ?>

<?= $this->section('content') ?>
<div class="row">
    <div class="col-md-6 offset-md-3">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5>Edit Kategori Pembayaran</h5>
            </div>
            <div class="card-body">
                <form action="<?= base_url('payment-categories/update/' . $category['id']) ?>" method="post">
                    <?= csrf_field() ?>

                    <div class="form-group">
                        <label for="name">Nama Kategori</label>
                        <input
                            type="text"
                            class="form-control <?= isset(session('errors')['name']) ? 'is-invalid' : '' ?>"
                            name="name"
                            id="name"
                            value="<?= old('name', $category['name']) ?>"
                            placeholder="Contoh: SPP, Listrik, Air">
                        <?php if (isset(session('errors')['name'])) : ?>
                            <div class="invalid-feedback">
                                <?= session('errors')['name'] ?>
                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="form-group">
                        <label for="default_amount">Jumlah Default</label>
                        <input
                            type="text"
                            class="form-control <?= isset(session('errors')['default_amount']) ? 'is-invalid' : '' ?>"
                            name="default_amount"
                            id="default_amount"
                            value="<?= old('default_amount', number_format($category['default_amount'], 0, ',', '.')) ?>"
                            placeholder="Contoh: 250.000">
                        <?php if (isset(session('errors')['default_amount'])) : ?>
                            <div class="invalid-feedback">
                                <?= session('errors')['default_amount'] ?>
                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="form-group">
                        <label for="billing_type">Tipe Tagihan</label>
                        <select
                            name="billing_type"
                            id="billing_type"
                            class="form-control <?= isset(session('errors')['billing_type']) ? 'is-invalid' : '' ?>">
                            <option value="">-- Pilih Tipe Tagihan --</option>
                            <?php $oldType = old('billing_type', $category['billing_type']); ?>
                            <option value="monthly" <?= $oldType === 'monthly' ? 'selected' : '' ?>>Monthly (Bulanan)</option>
                            <option value="one-time" <?= $oldType === 'one-time' ? 'selected' : '' ?>>One-Time (Sekali)</option>
                        </select>
                        <?php if (isset(session('errors')['billing_type'])) : ?>
                            <div class="invalid-feedback">
                                <?= session('errors')['billing_type'] ?>
                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="form-group">
                        <label for="duration_months">Durasi (Bulan)</label>
                        <input
                            type="number"
                            class="form-control <?= isset(session('errors')['duration_months']) ? 'is-invalid' : '' ?>"
                            name="duration_months"
                            id="duration_months"
                            value="<?= old('duration_months', $category['duration_months']) ?>"
                            placeholder="Contoh: 12">
                        <?php if (isset(session('errors')['duration_months'])) : ?>
                            <div class="invalid-feedback">
                                <?= session('errors')['duration_months'] ?>
                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="form-group mt-4">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Update
                        </button>
                        <a href="<?= base_url('payment-categories') ?>" class="btn btn-secondary">
                            <i class="fas fa-arrow-left"></i> Batal
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const input = document.getElementById('default_amount');

        // Format ribuan saat mengetik
        function formatRibuan(angka) {
            return angka.replace(/\B(?=(\d{3})+(?!\d))/g, '.');
        }

        input.addEventListener('input', function() {
            let value = this.value.replace(/\D/g, '');
            this.value = formatRibuan(value);
        });

        // Saat submit, hapus titik ribuan agar database menerima angka murni
        input.form.addEventListener('submit', function() {
            if (input.value) {
                input.value = input.value.replace(/\./g, '');
            }
        });
    });
</script>

<?= $this->endSection() ?>