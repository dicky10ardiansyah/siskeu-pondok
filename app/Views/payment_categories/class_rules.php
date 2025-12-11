<?= $this->extend('layouts/template/apps') ?>
<?php $this->setVar('title', 'Tarif Kategori per Kelas'); ?>
<?= $this->section('content') ?>

<div class="row">
    <div class="col-md-8 offset-md-2">
        <div class="card">
            <div class="card-header d-flex align-items-center w-100">
                <h5 class="mb-0">Tarif Kategori: <?= esc($category['name']) ?></h5>
                <a href="<?= base_url('payment-categories') ?>" class="btn btn-secondary btn-sm ml-auto">
                    <i class="fas fa-arrow-left"></i> Kembali
                </a>
            </div>
            <div class="card-body">
                <?php if (session()->getFlashdata('success')): ?>
                    <div class="alert alert-success"><?= session()->getFlashdata('success') ?></div>
                <?php endif; ?>

                <form action="<?= base_url('payment-categories/class-rules/' . $category['id']) ?>" method="post">
                    <?= csrf_field() ?>
                    <table class="table table-bordered table-hover">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Kelas</th>
                                <th>Nominal (Rp)</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $no = 1; ?>
                            <?php foreach ($classes as $class): ?>
                                <?php
                                $rule = $classRules[$class['id']] ?? 0;
                                $rule = intval($rule); // hapus desimal .00
                                ?>
                                <tr>
                                    <td><?= $no++ ?></td>
                                    <td><?= esc($class['name']) ?></td>
                                    <td>
                                        <input
                                            type="text"
                                            name="amounts[<?= $class['id'] ?>]"
                                            class="form-control amount-input"
                                            value="<?= old("amounts.{$class['id']}", $rule) ?>"
                                            placeholder="Contoh: 100.000">
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>

                    <div class="mt-3">
                        <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Simpan</button>
                        <a href="<?= base_url('payment-categories') ?>" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Batal</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const inputs = document.querySelectorAll('.amount-input');

        function formatNumber(value) {
            if (!value) return '';
            return value.replace(/\B(?=(\d{3})+(?!\d))/g, '.');
        }

        inputs.forEach(input => {
            input.addEventListener('input', function() {
                let numericValue = this.value.replace(/\./g, '');
                let cursorPosition = this.selectionStart;
                this.value = formatNumber(numericValue);
                let newLength = this.value.length;
                this.selectionEnd = cursorPosition + (newLength - numericValue.length);
            });

            input.form.addEventListener('submit', function() {
                input.value = input.value.replace(/\./g, '');
            });
        });
    });
</script>

<?= $this->endSection() ?>