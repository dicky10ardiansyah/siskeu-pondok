<?= $this->extend('layouts/template/apps') ?>

<?= $this->section('content') ?>

<div class="container mt-4">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <?php if (session()->getFlashdata('message')): ?>
                <script>
                    document.addEventListener('DOMContentLoaded', function() {
                        Swal.fire({
                            toast: true,
                            position: 'top-end',
                            icon: 'success',
                            title: '<?= session()->getFlashdata('message') ?>',
                            showConfirmButton: false,
                            timer: 3000,
                            timerProgressBar: true
                        });
                    });
                </script>
            <?php endif; ?>

            <div class="card shadow">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">Pengaturan Registrasi</h5>
                </div>
                <div class="card-body">
                    <form method="post" action="/setting/toggleRegister">
                        <?= csrf_field() ?>

                        <div class="form-group">
                            <label>Status Pendaftaran:</label><br>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="status" id="statusOn" value="on" <?= $status == 'on' ? 'checked' : '' ?>>
                                <label class="form-check-label" for="statusOn">Aktif</label>
                            </div>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="status" id="statusOff" value="off" <?= $status == 'off' ? 'checked' : '' ?>>
                                <label class="form-check-label" for="statusOff">Nonaktif</label>
                            </div>
                        </div>

                        <button type="submit" class="btn btn-success">
                            <i class="fas fa-save"></i> Simpan
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?= $this->endSection() ?>