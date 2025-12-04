<?= $this->extend('layouts/template/apps') ?>

<?php $this->setVar('title', 'Tabel Transaksi'); ?>

<?= $this->section('content') ?>
<div class="row">
    <div class="col">
        <div class="card">
            <div class="card-header d-flex align-items-center w-100">
                <h5 class="mb-0">Tabel Transaksi</h5>
                <a href="<?= base_url('transactions/create') ?>" class="btn btn-primary ml-auto">
                    <i class="fas fa-plus"></i> Tambah Transaksi
                </a>
            </div>

            <div class="card-body">

                <!-- Search Form -->
                <form action="<?= base_url('transactions') ?>" method="get" class="form-inline mb-3">
                    <input type="text" name="search" class="form-control mr-2"
                        placeholder="Cari deskripsi/type..." value="<?= esc($keyword ?? '') ?>">
                    <button type="submit" class="btn btn-outline-primary">Cari</button>
                </form>

                <div class="table-responsive">
                    <table class="table table-bordered table-hover">
                        <thead class="thead-dark">
                            <tr>
                                <th>#</th>
                                <th>Tanggal</th>
                                <th>Deskripsi</th>
                                <th>Type</th>
                                <th>Amount</th>
                                <th>Debit Account</th>
                                <th>Credit Account</th>
                                <th>Bukti</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>

                        <tbody>
                            <?php if (!empty($transactions)) : ?>
                                <?php
                                $accountsMap = [];
                                foreach ($accounts as $acc) {
                                    $accountsMap[$acc['id']] = $acc['code'] . ' - ' . $acc['name'];
                                }
                                $no = 1 + (10 * ($pager->getCurrentPage('transactions') - 1));
                                ?>

                                <?php foreach ($transactions as $trx) : ?>
                                    <tr>
                                        <td><?= $no++ ?></td>
                                        <td><?= date('d-m-Y', strtotime($trx['date'])) ?></td>
                                        <td><?= esc($trx['description']) ?></td>
                                        <td>
                                            <span class="badge <?= $trx['type'] === 'income' ? 'badge-success' : 'badge-danger' ?>">
                                                <?= esc(ucfirst($trx['type'])) ?>
                                            </span>
                                        </td>
                                        <td><?= number_format($trx['amount'], 2) ?></td>
                                        <td><?= esc($accountsMap[$trx['debit_account_id']] ?? '-') ?></td>
                                        <td><?= esc($accountsMap[$trx['credit_account_id']] ?? '-') ?></td>

                                        <td>
                                            <?php if (!empty($trx['proof'])) : ?>
                                                <?php
                                                $url = base_url($trx['proof']);
                                                $ext = strtolower(pathinfo($trx['proof'], PATHINFO_EXTENSION));
                                                ?>

                                                <?php if (in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp'])) : ?>
                                                    <a href="<?= $url ?>" target="_blank">
                                                        <img src="<?= $url ?>"
                                                            class="img-thumbnail"
                                                            style="width:60px;height:60px;object-fit:cover;">
                                                    </a>
                                                <?php elseif ($ext === 'pdf') : ?>
                                                    <a href="<?= $url ?>" target="_blank"
                                                        class="d-flex align-items-center justify-content-center bg-light border rounded"
                                                        style="width:60px;height:60px; text-decoration:none;">
                                                        <i class="fas fa-file-pdf fa-2x text-danger"></i>
                                                    </a>
                                                <?php endif; ?>

                                            <?php else: ?>
                                                -
                                            <?php endif; ?>
                                        </td>

                                        <td>
                                            <div class="d-flex justify-content-center">
                                                <a href="<?= base_url('transactions/edit/' . $trx['id']) ?>"
                                                    class="btn btn-sm btn-warning mr-2">Edit</a>
                                                <form action="<?= base_url('transactions/delete/' . $trx['id']) ?>"
                                                    method="post" style="display:inline;"
                                                    onsubmit="return confirm('Yakin ingin menghapus transaksi ini?');">
                                                    <?= csrf_field() ?>
                                                    <button type="submit" class="btn btn-sm btn-danger">Hapus</button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach ?>

                            <?php else: ?>
                                <tr>
                                    <td colspan="9" class="text-center">Tidak ada data transaksi.</td>
                                </tr>
                            <?php endif ?>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div class="mt-3">
                    <?= custom_pagination_with_query(
                        $pager,
                        ['search' => $keyword ?? ''],
                        'transactions',
                        'bootstrap_full'
                    ) ?>
                </div>

            </div>
        </div>
    </div>
</div>

<?= $this->endSection() ?>