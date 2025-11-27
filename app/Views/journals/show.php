<?= $this->extend('layouts/template/apps') ?>

<?php $this->setVar('title', 'Detail Jurnal'); ?>

<?= $this->section('content') ?>
<div class="container mt-4">
    <div class="card">
        <div class="card-header bg-primary text-white">
            <h5 class="mb-0">Detail Jurnal</h5>
        </div>
        <div class="card-body">
            <?php if ($journal): ?>
                <p><strong>Tanggal:</strong> <?= esc($journal['date']) ?></p>
                <p><strong>Deskripsi:</strong> <?= esc($journal['description']) ?></p>

                <hr>
                <h6>Rincian Akun</h6>
                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>Akun</th>
                            <th>Debit</th>
                            <th>Kredit</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $totalDebit = 0;
                        $totalCredit = 0;
                        foreach ($entries as $i => $entry):
                            $account = model('AccountModel')->find($entry['account_id']);
                            $totalDebit += $entry['debit'];
                            $totalCredit += $entry['credit'];
                        ?>
                            <tr>
                                <td><?= $i + 1 ?></td>
                                <td><?= esc($account['name'] ?? '-') ?> (<?= esc($account['code'] ?? '-') ?>)</td>
                                <td><?= number_format($entry['debit'], 2, ',', '.') ?></td>
                                <td><?= number_format($entry['credit'], 2, ',', '.') ?></td>
                            </tr>
                        <?php endforeach ?>
                        <tr>
                            <th colspan="2" class="text-center">Total</th>
                            <th><?= number_format($totalDebit, 2, ',', '.') ?></th>
                            <th><?= number_format($totalCredit, 2, ',', '.') ?></th>
                        </tr>
                    </tbody>
                </table>

                <a href="<?= base_url('journals') ?>" class="btn btn-secondary mt-3">Kembali</a>
            <?php else: ?>
                <div class="alert alert-warning">
                    Data jurnal tidak ditemukan.
                </div>
                <a href="<?= base_url('journals') ?>" class="btn btn-secondary mt-3">Kembali</a>
            <?php endif; ?>
        </div>
    </div>
</div>
<?= $this->endSection() ?>