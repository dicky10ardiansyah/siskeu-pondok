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

                    <?php foreach ($rules as $rule): ?>
                        <div class="form-group d-flex align-items-center mb-2">
                            <label class="mr-2" style="width: 200px"><?= esc($rule['category_name']) ?></label>

                            <input type="text"
                                name="amount[<?= $rule['id'] ?>]"
                                class="form-control mr-2 currency"
                                value="<?= number_format($rule['amount'], 0, ',', '.') ?>">

                            <?php if ($rule['is_mandatory'] == 1): ?>
                                <!-- Button Disable -->
                                <a href="<?= base_url('students/' . $student['id'] . '/payment-rules/disable/' . $rule['id']) ?>"
                                    class="btn btn-warning btn-sm btn-disable">
                                    <i class="fas fa-ban"></i> Nonaktifkan
                                </a>
                            <?php else: ?>
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
                            <input type="text" name="amount" class="form-control currency" placeholder="Nominal" required>
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

        // SweetAlert2 untuk tombol Nonaktifkan
        document.querySelectorAll('.btn-disable').forEach(function(btn) {
            btn.addEventListener('click', function(e) {
                e.preventDefault();
                const href = this.getAttribute('href');

                Swal.fire({
                    title: 'Yakin ingin menonaktifkan rule ini?',
                    text: "Siswa tidak akan dikenakan tagihan kategori ini!",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#3085d6',
                    cancelButtonColor: '#d33',
                    confirmButtonText: 'Ya, nonaktifkan!',
                    cancelButtonText: 'Batal'
                }).then((result) => {
                    if (result.isConfirmed) {
                        window.location.href = href;
                    }
                });
            });
        });

        // SweetAlert2 untuk tombol Aktifkan
        document.querySelectorAll('.btn-enable').forEach(function(btn) {
            btn.addEventListener('click', function(e) {
                e.preventDefault();
                const href = this.getAttribute('href');

                Swal.fire({
                    title: 'Aktifkan kembali rule ini?',
                    text: "Siswa akan dikenakan kembali tagihan ini!",
                    icon: 'info',
                    showCancelButton: true,
                    confirmButtonColor: '#28a745',
                    cancelButtonColor: '#d33',
                    confirmButtonText: 'Ya, aktifkan!',
                    cancelButtonText: 'Batal'
                }).then((result) => {
                    if (result.isConfirmed) {
                        window.location.href = href;
                    }
                });
            });
        });

        // ===============================
        // FORMAT CURRENCY INDONESIA
        // ===============================
        function formatRupiah(angka) {
            return angka.replace(/\D/g, "") // hanya angka
                .replace(/\B(?=(\d{3})+(?!\d))/g, ".");
        }

        document.querySelectorAll(".currency").forEach(function(el) {

            // Format saat halaman dibuka
            el.value = formatRupiah(el.value);

            // Format live ketika mengetik
            el.addEventListener("keyup", function() {
                this.value = formatRupiah(this.value);
            });
        });

        // Saat submit form -> hilangkan titik agar server menerima angka murni
        document.querySelectorAll("form").forEach(function(form) {
            form.addEventListener("submit", function() {
                form.querySelectorAll(".currency").forEach(function(el) {
                    el.value = el.value.replace(/\./g, "");
                });
            });
        });

    });
</script>

<?= $this->endSection() ?>