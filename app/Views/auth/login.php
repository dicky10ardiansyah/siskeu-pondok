<?= $this->extend('layouts/auth/apps') ?>

<?= $this->section('content') ?>

<div class="login-box">
    <div class="card card-outline card-success">
        <div class="card-header text-center">
            <!-- Tambahkan logo di atas h1 -->
            <a href="<?= base_url('/') ?>">
                <img src="<?= base_url('templates') ?>/dist/img/budget.png"
                    alt="Logo"
                    class="mb-2"
                    style="width: 100px; height: auto;">
            </a>
            <a href="<?= base_url('/') ?>" class="h1 d-block mt-2">
                <b>FMIS</b>
            </a>
            <small class="text-muted d-block" style="margin-top: -5px;">
                Financial Management Information System
            </small>
        </div>
        <div class="card-body">
            <p class="login-box-msg">Sign in to start your session</p>

            <?php if ($success = session()->getFlashdata('success')) : ?>
                <div class="alert alert-success"><?= esc($success) ?></div>
            <?php endif; ?>

            <?php if ($errors = session()->getFlashdata('errors')) : ?>
                <div class="alert alert-danger">
                    <ul>
                        <?php foreach ($errors as $error) : ?>
                            <li><?= esc($error) ?></li>
                        <?php endforeach ?>
                    </ul>
                </div>
            <?php endif; ?>

            <?php if ($loginError = session()->getFlashdata('login_error')) : ?>
                <div class="alert alert-danger"><?= esc($loginError) ?></div>
            <?php endif; ?>

            <?= form_open(base_url('login_process')) ?>
            <div class="input-group mb-3">
                <input name="login" type="text" class="form-control" placeholder="Email atau Nama" value="<?= old('login') ?>">
                <div class="input-group-append">
                    <div class="input-group-text"><span class="fas fa-user"></span></div>
                </div>
            </div>
            <div class="input-group mb-3">
                <input name="password" type="password" class="form-control" placeholder="Password">
                <div class="input-group-append">
                    <div class="input-group-text"><span class="fas fa-lock"></span></div>
                </div>
            </div>
            <div class="row">
                <div class="col-8"></div>
                <div class="col-4">
                    <button type="submit" class="btn btn-primary btn-block">Login</button>
                </div>
            </div>
            <?= form_close() ?>

            <p class="mb-0">
                <a href="<?= base_url('register') ?>" class="text-center">Register a new membership</a>
            </p>
        </div>
    </div>
</div>

<style>
    .card-header img {
        transition: transform 0.3s ease;
        filter: drop-shadow(0 2px 4px rgba(0, 0, 0, 0.2));
    }

    .card-header img:hover {
        transform: scale(1.05);
    }
</style>

<?= $this->endSection() ?>