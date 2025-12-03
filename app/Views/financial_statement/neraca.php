<?= $this->extend('layouts/template/apps') ?>
<?php $this->setVar('title', 'Laporan Neraca'); ?>
<?= $this->section('content') ?>
<div class="row">
    <div class="col">
        <div class="card">
            <div class="card-header">
                <h5>Neraca (Balance Sheet)</h5>
            </div>
            <div class="card-body">

                <!-- Aset -->
                <h6>Aset</h6>
                <table class="table table-bordered table-hover">
                    <thead class="thead-dark">
                        <tr>
                            <th>#</th>
                            <th>Nama Akun</th>
                            <th>Debit</th>
                            <th>Kredit</th>
                            <th>Saldo</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $no = 1;
                        foreach ($assets as $a): ?>
                            <tr>
                                <td><?= $no++ ?></td>
                                <td><?= esc($a['name']) ?></td>
                                <td class="text-end"><?= number_format($a['debit'], 2, ',', '.') ?></td>
                                <td class="text-end"><?= number_format($a['credit'], 2, ',', '.') ?></td>
                                <td class="text-end"><?= number_format($a['saldo'], 2, ',', '.') ?></td>
                            </tr>
                        <?php endforeach ?>
                    </tbody>
                    <tfoot>
                        <tr class="table-secondary">
                            <th colspan="4" class="text-end">Total Aset</th>
                            <th class="text-end"><?= number_format($total_assets, 2, ',', '.') ?></th>
                        </tr>
                    </tfoot>
                </table>

                <!-- Kewajiban -->
                <h6>Kewajiban</h6>
                <table class="table table-bordered table-hover">
                    <thead class="thead-dark">
                        <tr>
                            <th>#</th>
                            <th>Nama Akun</th>
                            <th>Debit</th>
                            <th>Kredit</th>
                            <th>Saldo</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $no = 1;
                        foreach ($liabilities as $l): ?>
                            <tr>
                                <td><?= $no++ ?></td>
                                <td><?= esc($l['name']) ?></td>
                                <td class="text-end"><?= number_format($l['debit'], 2, ',', '.') ?></td>
                                <td class="text-end"><?= number_format($l['credit'], 2, ',', '.') ?></td>
                                <td class="text-end"><?= number_format($l['saldo'], 2, ',', '.') ?></td>
                            </tr>
                        <?php endforeach ?>
                    </tbody>
                    <tfoot>
                        <tr class="table-secondary">
                            <th colspan="4" class="text-end">Total Kewajiban</th>
                            <th class="text-end"><?= number_format($total_liabilities, 2, ',', '.') ?></th>
                        </tr>
                    </tfoot>
                </table>

                <!-- Ekuitas -->
                <h6>Ekuitas</h6>
                <table class="table table-bordered table-hover">
                    <thead class="thead-dark">
                        <tr>
                            <th>#</th>
                            <th>Nama Akun</th>
                            <th>Debit</th>
                            <th>Kredit</th>
                            <th>Saldo</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $no = 1;
                        foreach ($equities as $e): ?>
                            <tr>
                                <td><?= $no++ ?></td>
                                <td><?= esc($e['name']) ?></td>
                                <td class="text-end"><?= number_format($e['debit'], 2, ',', '.') ?></td>
                                <td class="text-end"><?= number_format($e['credit'], 2, ',', '.') ?></td>
                                <td class="text-end"><?= number_format($e['saldo'], 2, ',', '.') ?></td>
                            </tr>
                        <?php endforeach ?>
                    </tbody>
                    <tfoot>
                        <tr class="table-secondary">
                            <th colspan="4" class="text-end">Total Ekuitas</th>
                            <th class="text-end"><?= number_format($total_equities, 2, ',', '.') ?></th>
                        </tr>
                        <tr class="table-secondary">
                            <th colspan="4" class="text-end">Total Kewajiban + Ekuitas</th>
                            <th class="text-end"><?= number_format($total_liabilities_equities, 2, ',', '.') ?></th>
                        </tr>
                    </tfoot>
                </table>

            </div>
        </div>
    </div>
</div>
<?= $this->endSection() ?>