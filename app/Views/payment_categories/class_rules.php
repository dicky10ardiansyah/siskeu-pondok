<?= $this->extend('layouts/template/apps') ?>
<?php $this->setVar('title', 'Tarif Kategori per Kelas'); ?>
<?= $this->section('content') ?>

<div class="row">
    <div class="col-md-10 offset-md-1">
        <div class="card">
            <div class="card-header d-flex align-items-center w-100">
                <h5 class="mb-0">Tarif Kategori: <?= esc($category['name']) ?></h5>
                <a href="<?= base_url('payment-categories') ?>" class="btn btn-secondary btn-sm ml-auto">
                    <i class="fas fa-arrow-left"></i> Kembali
                </a>
            </div>
            <div class="card-body">

                <form action="<?= base_url('payment-categories/class-rules/' . $category['id']) ?>" method="post">
                    <?= csrf_field() ?>

                    <?php if (session()->get('user_role') === 'admin'): ?>
                        <div class="form-group mb-3">
                            <label for="user-select">Pilih User (untuk semua kelas):</label>
                            <select id="user-select" class="form-control" name="user_ids_default">
                                <?php foreach ($users as $user): ?>
                                    <option value="<?= $user['id'] ?>" <?= $category['user_id'] == $user['id'] ? 'selected' : '' ?>>
                                        <?= esc($user['name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    <?php endif; ?>

                    <table class="table table-bordered table-hover">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Kelas</th>
                                <th>Nominal (Rp)</th>
                                <th>Wajib</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $no = 1; ?>
                            <?php foreach ($classes as $class):
                                $rule = $classRules[$class['id']] ?? ['amount' => 0, 'is_mandatory' => 1];
                                $amount = intval($rule['amount']);
                                $isMandatory = $rule['is_mandatory'] ? 'checked' : '';
                            ?>
                                <tr>
                                    <td><?= $no++ ?></td>
                                    <td><?= esc($class['name']) ?></td>
                                    <td>
                                        <input
                                            type="text"
                                            name="amounts[<?= $class['id'] ?>]"
                                            class="form-control amount-input"
                                            value="<?= old("amounts.{$class['id']}", $amount) ?>"
                                            placeholder="Contoh: 100.000">
                                    </td>
                                    <td class="text-center">
                                        <div class="custom-control custom-switch">
                                            <input type="checkbox"
                                                class="custom-control-input"
                                                id="mandatory-<?= $class['id'] ?>"
                                                name="mandatory[<?= $class['id'] ?>]"
                                                value="1"
                                                <?= $isMandatory ?>>
                                            <label class="custom-control-label" for="mandatory-<?= $class['id'] ?>"></label>
                                        </div>
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

<?php if (session()->getFlashdata('success')): ?>
    <script>
        Swal.fire({
            toast: true,
            position: 'top-end',
            icon: 'success',
            title: '<?= esc(session()->getFlashdata('success')) ?>',
            showConfirmButton: false,
            timer: 3000,
            timerProgressBar: true
        });
    </script>
<?php endif; ?>

<?= $this->endSection() ?>