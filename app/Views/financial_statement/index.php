<?= $this->extend('layouts/template/apps') ?>

<?php $this->setVar('title', 'Laporan Keuangan'); ?>

<?= $this->section('content') ?>
<div class="row">
    <div class="col">
        <div class="card">
            <div class="card-header d-flex align-items-center w-100">
                <h5 class="mb-0">Tabel Laporan Keuangan</h5>
            </div>

            <div class="card-body">

                <div class="callout callout-info">
                    <form method="get" class="row g-2 align-items-end">
                        <!-- Filter Tahun -->
                        <div class="col-md-3">
                            <label for="year" class="form-label">Pilih Tahun</label>
                            <select name="year" id="year" class="form-control">
                                <option value="">-- Semua Tahun --</option>
                                <?php for ($y = date('Y'); $y >= 2020; $y--) : ?>
                                    <option value="<?= $y ?>" <?= request()->getGet('year') == $y ? 'selected' : '' ?>>
                                        <?= $y ?>
                                    </option>
                                <?php endfor ?>
                            </select>
                        </div>

                        <!-- Filter User (hanya admin) -->
                        <?php if (isset($role) && $role === 'admin') : ?>
                            <div class="col-md-3">
                                <label for="user_id" class="form-label">Pilih User</label>
                                <select name="user_id" id="user_id" class="form-control">
                                    <option value="">-- Semua User --</option>
                                    <?php foreach ($users as $user) : ?>
                                        <option value="<?= $user['id'] ?>" <?= ($selected_user == $user['id']) ? 'selected' : '' ?>>
                                            <?= esc($user['name']) ?>
                                        </option>
                                    <?php endforeach ?>
                                </select>
                            </div>
                        <?php endif ?>

                        <div class="col-md-2">
                            <button type="submit" class="btn btn-primary"><i class="fas fa-filter"></i> Filter</button>
                        </div>
                    </form>
                </div>

                <div class="table-responsive">
                    <table class="table table-bordered table-hover">
                        <thead class="thead-dark">
                            <tr>
                                <th>#</th>
                                <th>Kode Akun</th>
                                <th>Nama Akun</th>
                                <th>Debit</th>
                                <th>Kredit</th>
                                <th>Saldo</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $no = 1; ?>
                            <?php if (!empty($data)) : ?>
                                <?php foreach ($data as $type => $accounts) : ?>
                                    <!-- Header Tipe Akun -->
                                    <tr class="table-primary">
                                        <td colspan="6"><strong><?= ucfirst($type) ?></strong></td>
                                    </tr>

                                    <?php
                                    $subtotal = 0;
                                    foreach ($accounts as $acc) :
                                        $subtotal += $acc['saldo'];
                                    ?>
                                        <tr>
                                            <td><?= $no++ ?></td>
                                            <td><?= esc($acc['code'] ?? '-') ?></td>
                                            <td><?= esc($acc['name']) ?></td>
                                            <td class="text-end"><?= number_format($acc['debit'], 2, ',', '.') ?></td>
                                            <td class="text-end"><?= number_format($acc['credit'], 2, ',', '.') ?></td>
                                            <td class="text-end"><?= number_format($acc['saldo'], 2, ',', '.') ?></td>
                                        </tr>
                                    <?php endforeach ?>

                                    <!-- Subtotal per Tipe Akun -->
                                    <tr class="table-secondary">
                                        <td colspan="5" class="text-end"><strong>Subtotal <?= ucfirst($type) ?></strong></td>
                                        <td class="text-end"><strong><?= number_format($subtotal, 2, ',', '.') ?></strong></td>
                                    </tr>
                                <?php endforeach ?>
                            <?php else : ?>
                                <tr>
                                    <td colspan="6" class="text-center">Tidak ada data laporan keuangan.</td>
                                </tr>
                            <?php endif ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
<?= $this->endSection() ?>