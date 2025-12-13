<?= $this->extend('layouts/template/apps') ?>
<?php $this->setVar('title', 'Laporan Neraca'); ?>
<?= $this->section('content') ?>

<section class="content">
    <div class="container-fluid">

        <!-- Filter Tanggal & User -->
        <div class="row mb-3">
            <div class="col-md-12">
                <div class="callout callout-info">
                    <form method="get">
                        <div class="form-row">

                            <!-- Start Date -->
                            <div class="col">
                                <input type="date" name="start_date" class="form-control" value="<?= esc($start_date) ?>">
                            </div>

                            <!-- End Date -->
                            <div class="col">
                                <input type="date" name="end_date" class="form-control" value="<?= esc($end_date) ?>">
                            </div>

                            <!-- User Select (hanya admin) -->
                            <?php if ($role === 'admin'): ?>
                                <div class="col">
                                    <select name="user_id" class="form-control">
                                        <option value="">-- Semua User --</option>
                                        <?php foreach ($users as $user): ?>
                                            <option value="<?= $user['id'] ?>" <?= ($selected_user == $user['id']) ? 'selected' : '' ?>>
                                                <?= esc($user['name']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            <?php endif; ?>

                            <div class="col">
                                <button type="submit" class="btn btn-primary">Filter</button>
                            </div>

                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Bagian Tabel Neraca (Aset, Liabilitas, Ekuitas, Income, Expense) -->
        <div class="row">

            <!-- ASET -->
            <div class="col-md-4">
                <div class="card card-success">
                    <div class="card-header">
                        <h3 class="card-title">Aset</h3>
                    </div>
                    <div class="card-body p-0">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Akun</th>
                                    <th class="text-right">Saldo</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($detail['asset'] as $row): ?>
                                    <tr>
                                        <td><?= esc($row['name']) ?></td>
                                        <td class="text-right"><?= number_format($row['saldo'], 0, ',', '.') ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                            <tfoot>
                                <tr>
                                    <th>Total Aset</th>
                                    <th class="text-right"><?= number_format($totals['asset'], 0, ',', '.') ?></th>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>

            <!-- KEWAJIBAN -->
            <div class="col-md-4">
                <div class="card card-warning">
                    <div class="card-header">
                        <h3 class="card-title">Kewajiban (Liabilitas)</h3>
                    </div>
                    <div class="card-body p-0">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Akun</th>
                                    <th class="text-right">Saldo</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($detail['liability'] as $row): ?>
                                    <tr>
                                        <td><?= esc($row['name']) ?></td>
                                        <td class="text-right"><?= number_format($row['saldo'], 0, ',', '.') ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                            <tfoot>
                                <tr>
                                    <th>Total Kewajiban</th>
                                    <th class="text-right"><?= number_format($totals['liability'], 0, ',', '.') ?></th>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>

            <!-- EKUITAS -->
            <div class="col-md-4">
                <div class="card card-primary">
                    <div class="card-header">
                        <h3 class="card-title">Ekuitas</h3>
                    </div>
                    <div class="card-body p-0">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Akun</th>
                                    <th class="text-right">Saldo</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($detail['equity'] as $row): ?>
                                    <tr class="<?= ($row['saldo'] < 0 ? 'table-danger' : '') ?>">
                                        <td><?= esc($row['name']) ?></td>
                                        <td class="text-right"><?= number_format($row['saldo'], 0, ',', '.') ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                            <tfoot>
                                <tr>
                                    <th>Total Ekuitas</th>
                                    <th class="text-right"><?= number_format($totals['equity'], 0, ',', '.') ?></th>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>

        </div>

        <div class="row">

            <!-- INCOME -->
            <div class="col-md-6">
                <div class="card card-info">
                    <div class="card-header">
                        <h3 class="card-title">Pendapatan (Income)</h3>
                    </div>
                    <div class="card-body p-0">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Akun</th>
                                    <th class="text-right">Saldo</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($detail['income'] as $row): ?>
                                    <tr>
                                        <td><?= esc($row['name']) ?></td>
                                        <td class="text-right"><?= number_format($row['saldo'], 0, ',', '.') ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                            <tfoot>
                                <tr>
                                    <th>Total Income</th>
                                    <th class="text-right"><?= number_format($totals['income'], 0, ',', '.') ?></th>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>

            <!-- EXPENSE -->
            <div class="col-md-6">
                <div class="card card-danger">
                    <div class="card-header">
                        <h3 class="card-title">Beban (Expense)</h3>
                    </div>
                    <div class="card-body p-0">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Akun</th>
                                    <th class="text-right">Saldo</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($detail['expense'] as $row): ?>
                                    <tr>
                                        <td><?= esc($row['name']) ?></td>
                                        <td class="text-right"><?= number_format($row['saldo'], 0, ',', '.') ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                            <tfoot>
                                <tr>
                                    <th>Total Expense</th>
                                    <th class="text-right"><?= number_format($totals['expense'], 0, ',', '.') ?></th>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>

        </div>

        <!-- Kesimpulan Neraca -->
        <div class="col-md-12">
            <div class="card <?= $balance_check ? 'card-success' : 'card-danger' ?>">
                <div class="card-header">
                    <h3 class="card-title">Kesimpulan Neraca</h3>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-4">
                            <h5>Total Aset:</h5>
                            <h3><?= number_format($totals['asset'], 0, ',', '.') ?></h3>
                        </div>
                        <div class="col-md-4">
                            <h5>Total Kewajiban + Ekuitas:</h5>
                            <h3><?= number_format($totals['liability'] + $totals['equity'], 0, ',', '.') ?></h3>
                        </div>
                        <div class="col-md-4">
                            <h5><?= $net_profit >= 0 ? 'Laba Bersih' : 'Defisit Berjalan' ?>:</h5>
                            <h3 class="<?= $net_profit < 0 ? 'text-danger' : '' ?>"><?= number_format($net_profit, 0, ',', '.') ?></h3>
                        </div>
                    </div>
                    <hr>
                    <?php if ($balance_check): ?>
                        <div class="alert alert-success"><strong>Neraca Seimbang ✔</strong></div>
                    <?php else: ?>
                        <div class="alert alert-danger"><strong>Neraca Tidak Seimbang ✘</strong></div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

    </div>
</section>

<?= $this->endSection() ?>