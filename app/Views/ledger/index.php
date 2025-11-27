<?= $this->extend('layouts/template/apps') ?>
<?php $this->setVar('title', 'Buku Besar / Ledger'); ?>

<?= $this->section('content') ?>
<div class="row">
    <div class="col">
        <div class="card">
            <div class="card-header d-flex align-items-center w-100">
                <h5 class="mb-0">Ledger Buku Besar</h5>
            </div>

            <div class="card-body">
                <!-- Filter Tanggal & Export -->
                <form action="<?= base_url('ledger') ?>" method="get" class="form-inline mb-3">
                    <label for="start" class="mr-2">Dari:</label>
                    <input type="date" name="start" id="start" class="form-control mr-2" value="<?= esc($start) ?>">
                    <label for="end" class="mr-2">Sampai:</label>
                    <input type="date" name="end" id="end" class="form-control mr-2" value="<?= esc($end) ?>">
                    <button type="submit" class="btn btn-primary mr-2">Filter</button>
                    <a href="<?= base_url('ledger/export/pdf?start=' . $start . '&end=' . $end) ?>" class="btn btn-danger mr-2">Export PDF</a>
                    <a href="<?= base_url('ledger/export/excel?start=' . $start . '&end=' . $end) ?>" class="btn btn-success">Export Excel</a>
                </form>

                <?php if (!empty($ledger)) : ?>
                    <?php foreach ($ledger as $accountName => $data) : ?>
                        <h6 class="mt-4 font-weight-bold"><?= esc($accountName) ?></h6>
                        <div class="table-responsive">
                            <table class="table table-bordered table-hover table-sm">
                                <thead class="thead-dark">
                                    <tr>
                                        <th>#</th>
                                        <th>Tanggal</th>
                                        <th>Deskripsi</th>
                                        <th>Debit</th>
                                        <th>Kredit</th>
                                        <th>Saldo</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (!empty($data['entries'])) : ?>
                                        <?php $no = 1; ?>
                                        <?php foreach ($data['entries'] as $entry) : ?>
                                            <tr>
                                                <td><?= $no++ ?></td>
                                                <td><?= date('d M Y H:i', strtotime($entry['date'])) ?></td>
                                                <td><?= esc($entry['description']) ?></td>
                                                <td><?= number_format($entry['debit'], 2) ?></td>
                                                <td><?= number_format($entry['credit'], 2) ?></td>
                                                <td><?= number_format($entry['balance'], 2) ?></td>
                                            </tr>
                                        <?php endforeach ?>
                                        <tr class="font-weight-bold bg-light">
                                            <td colspan="3" class="text-center">Total</td>
                                            <td><?= number_format($data['totalDebit'], 2) ?></td>
                                            <td><?= number_format($data['totalCredit'], 2) ?></td>
                                            <td><?= number_format(end($data['entries'])['balance'], 2) ?></td>
                                        </tr>
                                    <?php else : ?>
                                        <tr>
                                            <td colspan="6" class="text-center">Tidak ada transaksi.</td>
                                        </tr>
                                    <?php endif ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endforeach ?>
                <?php else : ?>
                    <p class="text-center">Belum ada data ledger.</p>
                <?php endif ?>
            </div>
        </div>
    </div>
</div>
<?= $this->endSection() ?>