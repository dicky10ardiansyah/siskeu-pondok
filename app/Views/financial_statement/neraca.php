<?= $this->extend('layouts/template/apps') ?>

<?php $this->setVar('title', 'Neraca Lengkap'); ?>

<?= $this->section('content') ?>
<div class="row">
    <div class="col">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Laporan Neraca</h5>
            </div>
            <div class="card-body">

                <!-- Bagian Aset -->
                <h6>Aset</h6>
                <div class="table-responsive mb-3">
                    <table class="table table-bordered table-hover">
                        <thead class="thead-dark">
                            <tr>
                                <th>#</th>
                                <th>Nama Akun</th>
                                <th>Saldo</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $no = 1;
                            foreach ($assets as $asset) : ?>
                                <tr>
                                    <td><?= $no++ ?></td>
                                    <td><?= esc($asset['account_name']) ?></td>
                                    <td><?= number_format($asset['saldo'], 2, ',', '.') ?></td>
                                </tr>
                            <?php endforeach ?>
                        </tbody>
                        <tfoot>
                            <tr>
                                <th colspan="2" class="text-right">Total Aset</th>
                                <th><?= number_format($total_assets, 2, ',', '.') ?></th>
                            </tr>
                        </tfoot>
                    </table>
                </div>

                <!-- Bagian Kewajiban -->
                <h6>Kewajiban</h6>
                <div class="table-responsive mb-3">
                    <table class="table table-bordered table-hover">
                        <thead class="thead-dark">
                            <tr>
                                <th>#</th>
                                <th>Nama Akun</th>
                                <th>Saldo</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $no = 1;
                            foreach ($liabilities as $liability) : ?>
                                <tr>
                                    <td><?= $no++ ?></td>
                                    <td><?= esc($liability['account_name']) ?></td>
                                    <td><?= number_format($liability['saldo'], 2, ',', '.') ?></td>
                                </tr>
                            <?php endforeach ?>
                        </tbody>
                        <tfoot>
                            <tr>
                                <th colspan="2" class="text-right">Total Kewajiban</th>
                                <th><?= number_format($total_liabilities, 2, ',', '.') ?></th>
                            </tr>
                        </tfoot>
                    </table>
                </div>

                <!-- Bagian Ekuitas -->
                <h6>Ekuitas</h6>
                <div class="table-responsive mb-3">
                    <table class="table table-bordered table-hover">
                        <thead class="thead-dark">
                            <tr>
                                <th>#</th>
                                <th>Nama Akun</th>
                                <th>Saldo</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $no = 1;
                            foreach ($equities as $equity) : ?>
                                <tr>
                                    <td><?= $no++ ?></td>
                                    <td><?= esc($equity['account_name']) ?></td>
                                    <td><?= number_format($equity['saldo'], 2, ',', '.') ?></td>
                                </tr>
                            <?php endforeach ?>
                        </tbody>
                        <tfoot>
                            <tr>
                                <th colspan="2" class="text-right">Total Ekuitas</th>
                                <th><?= number_format($total_equities, 2, ',', '.') ?></th>
                            </tr>
                            <tr>
                                <th colspan="2" class="text-right">Total Kewajiban + Ekuitas</th>
                                <th><?= number_format($total_liabilities_equities, 2, ',', '.') ?></th>
                            </tr>
                        </tfoot>
                    </table>
                </div>

            </div>
        </div>
    </div>
</div>
<?= $this->endSection() ?>