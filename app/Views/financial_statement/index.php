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