<?php $this->setVar('title', '403 - Unauthorized'); ?>

<?= $this->extend('layouts/template/apps') ?>

<?= $this->section('content') ?>
<div class="container text-center py-5">
    <img src="<?= base_url('templates') ?>/dist/img/403.png" alt="Unauthorized Access" style="max-width: 300px;" class="mb-4">
    <h1 class="display-4 text-danger">403 - Akses Ditolak</h1>
    <p class="lead">Maaf, Anda tidak memiliki izin untuk mengakses halaman ini.</p>
    <a href="<?= base_url('/') ?>" class="btn btn-primary mt-3">Kembali ke Beranda</a>
</div>
<?= $this->endSection() ?>