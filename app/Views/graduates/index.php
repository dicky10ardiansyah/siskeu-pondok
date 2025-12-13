<?= $this->extend('layouts/template/apps') ?>
<?php $this->setVar('title', 'Daftar Lulusan'); ?>
<?= $this->section('content') ?>

<div class="row">
    <div class="col">
        <div class="card">
            <div class="card-header d-flex align-items-center w-100">
                <h5 class="mb-0">Tabel Lulusan</h5>
            </div>
            <div class="card-body">
                <!-- Search & Filter -->
                <form method="get" class="form-inline mb-3">
                    <!-- Filter User (opsional) -->
                    <select name="user_id" class="form-control mr-2">
                        <option value="">-- Semua User --</option>
                        <?php foreach ($users as $u): ?>
                            <option value="<?= esc($u['id']) ?>" <?= ($filterUser ?? '') == $u['id'] ? 'selected' : '' ?>>
                                <?= esc($u['name']) ?>
                            </option>
                        <?php endforeach ?>
                    </select>

                    <input type="text" name="q" class="form-control mr-2" placeholder="Cari nama/NIS..." value="<?= esc($search ?? '') ?>">

                    <select name="class" class="form-control mr-2">
                        <option value="">-- Pilih Kelas --</option>
                        <?php foreach ($classes as $c): ?>
                            <option value="<?= esc($c['class']) ?>" <?= ($filterClass ?? '') == $c['class'] ? 'selected' : '' ?>><?= esc($c['class']) ?></option>
                        <?php endforeach; ?>
                    </select>

                    <select name="school_year" class="form-control mr-2">
                        <option value="">-- Pilih Tahun Lulus --</option>
                        <?php foreach ($years as $y): ?>
                            <option value="<?= esc($y['school_year']) ?>" <?= ($filterYear ?? '') == $y['school_year'] ? 'selected' : '' ?>><?= esc($y['school_year']) ?></option>
                        <?php endforeach; ?>
                    </select>

                    <select name="status_payment" class="form-control mr-2">
                        <option value="">-- Status Pembayaran --</option>
                        <option value="Lunas" <?= ($filterStatus ?? '') == 'Lunas' ? 'selected' : '' ?>>Lunas</option>
                        <option value="Tunggakan" <?= ($filterStatus ?? '') == 'Tunggakan' ? 'selected' : '' ?>>Tunggakan</option>
                    </select>

                    <button type="submit" class="btn btn-primary">Filter</button>
                </form>

                <!-- Tabel -->
                <div class="table-responsive">
                    <table class="table table-bordered table-hover">
                        <thead class="thead-dark">
                            <tr>
                                <th>#</th>
                                <th>Nama</th>
                                <th>NIS</th>
                                <th>Kelas</th>
                                <th>Status Lulus</th>
                                <th>Tahun Lulus</th>
                                <th>Total Tagihan</th>
                                <th>Total Bayar</th>
                                <th>Status Pembayaran</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($students)) : ?>
                                <?php $no = 1; ?>
                                <?php foreach ($students as $student) : ?>
                                    <tr>
                                        <td><?= $no++ ?></td>
                                        <td><?= esc($student['name']) ?></td>
                                        <td><?= esc($student['nis']) ?></td>
                                        <td><?= esc($student['class']) ?></td>
                                        <td><?= esc($student['status_lulus']) ?></td>
                                        <td><?= esc($student['school_year']) ?></td>
                                        <td><?= number_format($student['total_bill'], 0, ',', '.') ?></td>
                                        <td><?= number_format($student['total_paid'], 0, ',', '.') ?></td>
                                        <td>
                                            <?php if ($student['status_payment'] === 'Lunas') : ?>
                                                <span class="badge badge-success"><?= $student['status_payment'] ?></span>
                                            <?php else : ?>
                                                <span class="badge badge-danger"><?= $student['status_payment'] ?></span>
                                            <?php endif ?>
                                        </td>
                                        <td>
                                            <a href="<?= base_url('billing/detail/' . $student['id']) ?>" class="btn btn-sm btn-info">
                                                <i class="fas fa-search"></i> Cek
                                            </a>
                                            <a href="<?= base_url('payments/create?student_id=' . $student['id']) ?>" class="btn btn-sm btn-success">
                                                <i class="fas fa-dollar-sign"></i> Bayar
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach ?>
                            <?php else : ?>
                                <tr>
                                    <td colspan="10" class="text-center">Tidak ada data lulusan.</td>
                                </tr>
                            <?php endif ?>
                        </tbody>
                    </table>

                    <div class="mt-3">
                        <?= $pager->links('graduates', 'bootstrap_full') ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?= $this->endSection() ?>