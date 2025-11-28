<?= $this->extend('layouts/template/apps') ?>

<?php $this->setVar('title', 'Edit Kategori Pembayaran'); ?>

<?= $this->section('content') ?>
<div class="row">
    <div class="col-md-6 offset-md-3">
        <div class="card">
            <div class="card-header">
                <h5>Edit Kategori Pembayaran</h5>
            </div>
            <div class="card-body">
                <form action="<?= base_url('payment-categories/update/' . $category['id']) ?>" method="post">
                    <?= csrf_field() ?>

                    <div class="form-group">
                        <label for="name">Nama Kategori</label>
                        <input type="text" name="name" id="name" class="form-control" value="<?= set_value('name', $category['name']) ?>" required>
                        <?php if (isset($validation) && $validation->hasError('name')) : ?>
                            <small class="text-danger"><?= $validation->getError('name') ?></small>
                        <?php endif ?>
                    </div>

                    <div class="form-group">
                        <label for="default_amount">Nominal Default</label>
                        <input type="number" name="default_amount" id="default_amount" class="form-control" step="0.01" value="<?= set_value('default_amount', $category['default_amount']) ?>" required>
                        <?php if (isset($validation) && $validation->hasError('default_amount')) : ?>
                            <small class="text-danger"><?= $validation->getError('default_amount') ?></small>
                        <?php endif ?>
                    </div>

                    <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
                    <a href="<?= base_url('payment-categories') ?>" class="btn btn-secondary">Batal</a>
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