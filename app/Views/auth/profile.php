<?= $this->extend('layouts/template/apps') ?>

<?= $this->section('content') ?>

<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-3">

            <!-- Profile Image -->
            <div class="card card-success card-outline">
                <div class="card-body box-profile">
                    <div class="text-center">
                        <img class="profile-user-img img-fluid img-circle"
                            src="<?= base_url('templates') ?>/dist/img/bussiness-man.png"
                            alt="User profile picture">
                    </div>

                    <h3 class="profile-username text-center">About Me</h3>

                    <p class="text-muted text-center"><?= esc(session()->get('user_name')) ?></p>

                    <ul class="list-group list-group-unbordered mb-3">
                        <li class="list-group-item">
                            <b>Name</b> <a class="float-right"><?= esc(session()->get('user_name')) ?></a>
                        </li>
                        <li class="list-group-item">
                            <b>Email</b> <a class="float-right"><?= esc(session()->get('user_email')) ?></a>
                        </li>
                        <li class="list-group-item">
                            <b>Role</b> <a class="float-right"><?= esc(session()->get('user_role')) ?></a>
                        </li>
                    </ul>

                    <a href="<?= base_url('logout') ?>" class="btn btn-danger btn-block"><b>Logout</b></a>
                </div>
                <!-- /.card-body -->
            </div>

        </div>
    </div>
</div>

<?= $this->endSection() ?>