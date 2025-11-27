<?php $this->setVar('title', '404 - Not Found'); ?>

<?= $this->extend('layouts/template/apps') ?>

<?= $this->section('content') ?>
<div class="container text-center py-5 mt-5">
    <img src="<?= base_url('templates') ?>/dist/img/404.svg" alt="Halaman Tidak Ditemukan" style="max-width: 300px;" class="mb-4">
    <h1 class="display-4 text-danger">404 - Halaman Tidak Ditemukan</h1>
    <p class="lead">Halaman yang Anda cari tidak ditemukan.</p>
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

    .empty-message {
        font-size: 1.2rem;
        color: #6c757d;
    }
</style> -->

<?= $this->endSection() ?>