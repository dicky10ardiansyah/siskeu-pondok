<?= $this->extend('layouts/template/apps') ?>

<?= $this->section('content') ?>
<div class="container text-center py-5">
    <img src="<?= base_url('templates') ?>/dist/img/under-construction.svg" alt="Sedang Dalam Pengembangan" style="max-width: 300px;" class="mb-4">
    <h1 class="display-4 text-warning">Halaman Sedang Dalam Pengembangan</h1>
    <p class="lead">Kami sedang menyiapkan sesuatu yang spesial untuk perjalanan Anda. Nantikan ya!</p>
    <a href="<?= base_url('/') ?>" class="btn btn-primary mt-3">Kembali ke Beranda</a>
</div>

<!-- <style>
    body {
        padding-top: 80px;
        padding-bottom: 100px;
        background-color: #f8f9fa;
    }

    .content-wrapper {
        min-height: 70vh;
    }

    .lead {
        color: #6c757d;
    }
</style> -->

<?= $this->endSection() ?>