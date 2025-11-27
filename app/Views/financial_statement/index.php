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
                                <th>Tipe</th>
                                <th>Saldo</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($data)) : ?>
                                <?php $no = 1; ?>
                                <?php foreach ($data as $row) : ?>
                                    <tr>
                                        <td><?= $no++ ?></td>
                                        <td><?= esc($row['account_code']) ?></td>
                                        <td><?= esc($row['account_name']) ?></td>
                                        <td><?= esc(ucfirst($row['type'])) ?></td>
                                        <td><?= number_format($row['saldo'], 2, ',', '.') ?></td>
                                    </tr>
                                <?php endforeach ?>
                            <?php else : ?>
                                <tr>
                                    <td colspan="5" class="text-center">Tidak ada data laporan keuangan.</td>
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