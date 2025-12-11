<?= $this->extend('layouts/template/apps') ?>
<?php $this->setVar('title', 'Edit Tarif Siswa'); ?>
<?= $this->section('content') ?>

<div class="row">
    <div class="col-md-8 offset-md-2">
        <div class="card">
            <div class="card-header">
                <h5>Edit Tarif Siswa: <?= esc($student['name']) ?> (<?= esc($student['nis']) ?>)</h5>
            </div>
            <div class="card-body">

                <!-- Update Amount -->
                <form action="<?= base_url('students/' . $student['id'] . '/payment-rules') ?>" method="post">
                    <?= csrf_field() ?>

                    <?php
                    // Siapkan tarif default per kelas
                    $classRulesArr = [];
                    foreach ($classRules as $cr) {
                        $classRulesArr[$cr['category_id']] = $cr['amount'];
                    }
                    ?>

                    <?php foreach ($categories as $category): ?>
                        <?php
                        // Cari rule siswa
                        $rule = array_filter($rules, fn($r) => $r['category_id'] == $category['id']);
                        $rule = $rule ? array_values($rule)[0] : null;

                        // Tentukan nilai default: rule siswa > tarif kelas > default kategori
                        $amount = $rule['amount'] ?? ($classRulesArr[$category['id']] ?? $category['default_amount'] ?? 0);
                        ?>
                        <div class="form-group d-flex align-items-center mb-2">
                            <label class="mr-2" style="width: 200px"><?= esc($category['name']) ?></label>

                            <input type="text"
                                name="amount[<?= $rule['id'] ?? 'new_' . $category['id'] ?>]"
                                class="form-control mr-2 currency"
                                value="<?= number_format($amount, 0, ',', '.') ?>">

                            <?php if ($rule && $rule['is_mandatory'] == 1): ?>
                                <!-- Button Disable -->
                                <a href="<?= base_url('students/' . $student['id'] . '/payment-rules/disable/' . $rule['id']) ?>"
                                    class="btn btn-warning btn-sm btn-disable">
                                    <i class="fas fa-ban"></i> Nonaktifkan
                                </a>
                            <?php elseif ($rule): ?>
                                <!-- Button Enable -->
                                <a href="<?= base_url('students/' . $student['id'] . '/payment-rules/enable/' . $rule['id']) ?>"
                                    class="btn btn-success btn-sm btn-enable">
                                    <i class="fas fa-check"></i> Aktifkan
                                </a>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>

                    <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Update</button>
                    <a href="<?= base_url('students') ?>" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Batal</a>
                </form>

                <hr>

                <!-- Tambah Rule Baru -->
                <form action="<?= base_url('students/' . $student['id'] . '/payment-rules/add') ?>" method="post" class="mt-3">
                    <?= csrf_field() ?>
                    <div class="form-row align-items-center">
                        <div class="col">
                            <select name="category_id" class="form-control" required>
                                <option value="">Pilih Kategori</option>
                                <?php foreach ($categories as $cat): ?>
                                    <option value="<?= $cat['id'] ?>"><?= esc($cat['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col">
                            <input type="text" name="amount" class="form-control currency" placeholder="Nominal (kosongkan untuk default kelas)">
                        </div>
                        <div class="col-auto">
                            <button type="submit" class="btn btn-success"><i class="fas fa-plus"></i> Tambah Rule</button>
                        </div>
                    </div>
                </form>

            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {

        const Toast = Swal.mixin({
            toast: true,
            position: 'top-end',
            showConfirmButton: false,
            timer: 2000,
            timerProgressBar: true
        });

        <?php if (session()->getFlashdata('success')): ?>
            Toast.fire({
                icon: 'success',
                title: '<?= session()->getFlashdata('success') ?>'
            });
        <?php endif; ?>

        <?php if (session()->getFlashdata('error')): ?>
            Toast.fire({
                icon: 'error',
                title: '<?= session()->getFlashdata('error') ?>'
            });
        <?php endif; ?>

            // SweetAlert2 untuk tombol Nonaktifkan & Aktifkan
            ['disable', 'enable'].forEach(function(type) {
                document.querySelectorAll('.btn-' + type).forEach(function(btn) {
                    btn.addEventListener('click', function(e) {
                        e.preventDefault();
                        const href = this.getAttribute('href');
                        const text = type === 'disable' ?
                            "Siswa tidak akan dikenakan tagihan kategori ini!" :
                            "Siswa akan dikenakan kembali tagihan ini!";
                        Swal.fire({
                            title: type === 'disable' ? 'Yakin ingin menonaktifkan rule ini?' : 'Aktifkan kembali rule ini?',
                            text: text,
                            icon: type === 'disable' ? 'warning' : 'info',
                            showCancelButton: true,
                            confirmButtonColor: type === 'disable' ? '#3085d6' : '#28a745',
                            cancelButtonColor: '#d33',
                            confirmButtonText: 'Ya',
                            cancelButtonText: 'Batal'
                        }).then((result) => {
                            if (result.isConfirmed) {
                                window.location.href = href;
                            }
                        });
                    });
                });
            });

        // Format currency
        function formatRupiah(angka) {
            return angka.replace(/\D/g, "").replace(/\B(?=(\d{3})+(?!\d))/g, ".");
        }

        document.querySelectorAll(".currency").forEach(function(el) {
            el.value = formatRupiah(el.value);
            el.addEventListener("keyup", function() {
                this.value = formatRupiah(this.value);
            });
        });

        // Submit form -> hilangkan titik
        document.querySelectorAll("form").forEach(function(form) {
            form.addEventListener("submit", function() {
                form.querySelectorAll(".currency").forEach(el => el.value = el.value.replace(/\./g, ""));
            });
        });

    });
</script>

<?= $this->endSection() ?>