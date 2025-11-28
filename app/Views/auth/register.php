<?= $this->extend('layouts/auth/apps') ?>

<?= $this->section('content') ?>

<div class="register-box">
    <div class="card card-outline card-success">
        <div class="card-header text-center">
            <!-- Logo di atas judul -->
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
            <p class="login-box-msg">Register a new membership</p>

            <?php if ($errors = session()->getFlashdata('errors')) : ?>
                <div class="alert alert-danger" role="alert">
                    <ul>
                        <?php foreach ($errors as $error) : ?>
                            <li><?= esc($error) ?></li>
                        <?php endforeach ?>
                    </ul>
                </div>
            <?php endif; ?>

            <?= form_open(base_url('save_register')) ?>

            <div class="input-group mb-3">
                <input name="name" type="text" class="form-control" placeholder="Full name" value="<?= old('name') ?>">
                <div class="input-group-append">
                    <div class="input-group-text">
                        <span class="fas fa-user"></span>
                    </div>
                </div>
            </div>

            <div class="input-group mb-3">
                <input name="email" type="email" class="form-control" placeholder="Email" value="<?= old('email') ?>">
                <div class="input-group-append">
                    <div class="input-group-text">
                        <span class="fas fa-envelope"></span>
                    </div>
                </div>
            </div>

            <div class="input-group mb-3">
                <input name="password" type="password" class="form-control" placeholder="Password">
                <div class="input-group-append">
                    <div class="input-group-text">
                        <span class="fas fa-lock"></span>
                    </div>
                </div>
            </div>

            <div class="input-group mb-3">
                <input name="password_confirmation" type="password" class="form-control" placeholder="Retype password">
                <div class="input-group-append">
                    <div class="input-group-text">
                        <span class="fas fa-lock"></span>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-8">
                    <div class="icheck-primary">
                        <input type="checkbox" id="agreeTerms" name="terms" value="agree">
                        <label for="agreeTerms">
                            I agree to the <a href="#">terms</a>
                        </label>
                    </div>
                </div>
                <div class="col-4">
                    <button type="submit" class="btn btn-primary btn-block mb-4">Register</button>
                </div>
            </div>

            <?= form_close() ?>

            <a href="<?= base_url('login') ?>" class="text-center">I already have a membership</a>
        </div>
    </div>
</div>

<!-- Styling tambahan untuk tampilan logo & efek hover -->
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