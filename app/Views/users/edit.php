<?= $this->extend('layouts/template/apps') ?>

<?= $this->section('content') ?>
<div class="row">
    <div class="col-md-6 offset-md-3">
        <div class="card">
            <div class="card-header">
                <h5>Edit Data Pengguna</h5>
            </div>
            <div class="card-body">
                <form action="/user/update/<?= $user['id'] ?>" method="post">
                    <?= csrf_field() ?>
                    <input type="hidden" name="_method" value="POST">

                    <div class="form-group">
                        <label for="name">Nama</label>
                        <input type="text" class="form-control <?= session('errors.name') ? 'is-invalid' : '' ?>" name="name" id="name" value="<?= esc(old('name', $user['name'])) ?>">
                        <?php if (session('errors.name')) : ?>
                            <div class="invalid-feedback">
                                <?= session('errors.name') ?>
                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="form-group">
                        <label for="email">Email</label>
                        <input type="email" class="form-control <?= session('errors.email') ? 'is-invalid' : '' ?>" name="email" id="email" value="<?= esc(old('email', $user['email'])) ?>">
                        <?php if (session('errors.email')) : ?>
                            <div class="invalid-feedback">
                                <?= session('errors.email') ?>
                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="form-group">
                        <label for="password">Password (biarkan kosong jika tidak diubah)</label>
                        <input type="password" class="form-control <?= session('errors.password') ? 'is-invalid' : '' ?>" name="password" id="password">
                        <?php if (session('errors.password')) : ?>
                            <div class="invalid-feedback">
                                <?= session('errors.password') ?>
                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="form-group">
                        <label for="role">Role</label>
                        <select name="role" id="role" class="form-control <?= session('errors.role') ? 'is-invalid' : '' ?>">
                            <option value="">-- Pilih Role --</option>
                            <option value="admin" <?= old('role', $user['role']) === 'admin' ? 'selected' : '' ?>>Admin</option>
                            <option value="user" <?= old('role', $user['role']) === 'user' ? 'selected' : '' ?>>User</option>
                        </select>
                        <?php if (session('errors.role')) : ?>
                            <div class="invalid-feedback">
                                <?= session('errors.role') ?>
                            </div>
                        <?php endif; ?>
                    </div>

                    <button type="submit" class="btn btn-success">Update</button>
                    <a href="/user" class="btn btn-secondary">Batal</a>
                </form>
            </div>
        </div>
    </div>
</div>
<?= $this->endSection() ?>