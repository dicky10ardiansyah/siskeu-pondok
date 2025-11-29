<?= $this->extend('layouts/template/apps') ?>

<?php $this->setVar('title', 'Tambah Pembayaran'); ?>

<?= $this->section('content') ?>
<div class="row">
    <div class="col-md-8 offset-md-2">
        <div class="card">
            <div class="card-header">
                <h5>Tambah Pembayaran</h5>
            </div>

            <?php if (session()->getFlashdata('error')) : ?>
                <script>
                    Swal.fire({
                        icon: 'error',
                        title: 'Gagal!',
                        text: '<?= session()->getFlashdata('error') ?>',
                    });
                </script>
            <?php endif ?>

            <div class="card-body">
                <form action="<?= base_url('payments/store') ?>" method="post" id="paymentForm">
                    <?= csrf_field() ?>

                    <div class="form-group">
                        <label for="student_id">Siswa</label>
                        <select name="student_id" id="student_id" class="form-control" <?= $preselectedStudent ? 'disabled' : '' ?>>
                            <option value="">-- Pilih Siswa --</option>
                            <?php foreach ($students as $student): ?>
                                <option value="<?= $student['id'] ?>"
                                    <?= set_select('student_id', $student['id'], $preselectedStudent == $student['id']) ?>>
                                    <?= esc($student['name']) ?> (<?= esc($student['class'] ?? '-') ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <?php if ($preselectedStudent): ?>
                            <input type="hidden" name="student_id" value="<?= $preselectedStudent ?>">
                        <?php endif; ?>
                    </div>

                    <div class="form-group">
                        <label for="total_amount">Jumlah Bayar</label>
                        <input type="number" name="total_amount" id="total_amount" class="form-control" step="0.01" value="<?= set_value('total_amount') ?>">
                        <?php if ($validation->hasError('total_amount')) : ?>
                            <small class="text-danger"><?= $validation->getError('total_amount') ?></small>
                        <?php endif ?>
                    </div>

                    <div class="form-group">
                        <label for="date">Tanggal Pembayaran</label>
                        <input type="date" name="date" id="date" class="form-control" value="<?= set_value('date', date('Y-m-d')) ?>">
                        <?php if ($validation->hasError('date')) : ?>
                            <small class="text-danger"><?= $validation->getError('date') ?></small>
                        <?php endif ?>
                    </div>

                    <div class="form-group">
                        <label for="method">Metode Pembayaran</label>
                        <select name="method" id="method" class="form-control">
                            <option value="cash" <?= set_select('method', 'cash') ?>>Cash</option>
                            <option value="transfer" <?= set_select('method', 'transfer') ?>>Transfer</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="reference">Referensi</label>
                        <input type="text" name="reference" id="reference" class="form-control" placeholder="Nomor kwitansi / transfer" value="<?= set_value('reference') ?>">
                    </div>

                    <div class="form-group">
                        <label for="account_id">Akun</label>
                        <select name="account_id" class="form-control" required>
                            <?php foreach ($accounts as $account): ?>
                                <option value="<?= $account['id'] ?>" <?= set_select('account_id', $account['id']) ?>>
                                    <?= esc($account['name']) ?> (<?= esc($account['type']) ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <button type="submit" class="btn btn-primary">Simpan Pembayaran</button>
                    <a href="<?= base_url('payments') ?>" class="btn btn-secondary">Batal</a>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- SweetAlert2 pesan sukses -->
<?php if (session()->getFlashdata('success')) : ?>
    <script>
        Swal.fire({
            toast: true,
            position: 'top-end',
            icon: 'success',
            title: '<?= session()->getFlashdata('success') ?>',
            showConfirmButton: false,
            timer: 3000,
            timerProgressBar: true,
        });
    </script>
<?php endif ?>

<?= $this->endSection() ?>