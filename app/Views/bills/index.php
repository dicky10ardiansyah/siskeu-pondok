<?= $this->extend('layouts/template/apps') ?>
<?php $this->setVar('title', 'Tagihan Siswa'); ?>

<?= $this->section('content') ?>
<div class="row">
    <div class="col">
        <div class="card">
            <div class="card-header d-flex align-items-center">
                <h5 class="mb-0">Tabel Tagihan Siswa</h5>
                <a href="<?= base_url('bills/generate') ?>" class="btn btn-primary ml-auto">
                    <i class="fas fa-plus"></i> Generate Tagihan
                </a>
            </div>

            <div class="card-body">

                <!-- Search -->
                <form action="<?= base_url('bills') ?>" method="get" class="form-inline mb-3">
                    <input type="text" name="q" class="form-control mr-2" placeholder="Cari nama siswa..." value="<?= esc($search ?? '') ?>">

                    <select name="class" class="form-control mr-2">
                        <option value="">-- Semua Kelas --</option>
                        <?php foreach ($classes as $cls): ?>
                            <option value="<?= esc($cls) ?>" <?= ($selectedClass ?? '') == $cls ? 'selected' : '' ?>><?= esc($cls) ?></option>
                        <?php endforeach; ?>
                    </select>

                    <select name="status" class="form-control mr-2">
                        <option value="">-- Semua Status --</option>
                        <option value="unpaid" <?= ($selectedStatus ?? '') == 'unpaid' ? 'selected' : '' ?>>Belum Lunas</option>
                        <option value="partial" <?= ($selectedStatus ?? '') == 'partial' ? 'selected' : '' ?>>Partial</option>
                        <option value="paid" <?= ($selectedStatus ?? '') == 'paid' ? 'selected' : '' ?>>Lunas</option>
                    </select>

                    <select name="month" class="form-control mr-2">
                        <option value="">-- Semua Bulan --</option>
                        <?php for ($m = 1; $m <= 12; $m++): ?>
                            <option value="<?= $m ?>" <?= ($selectedMonth ?? '') == $m ? 'selected' : '' ?>><?= date('F', mktime(0, 0, 0, $m, 1)) ?></option>
                        <?php endfor; ?>
                    </select>

                    <input type="number" name="year" class="form-control mr-2" placeholder="Tahun" value="<?= esc($selectedYear ?? '') ?>">

                    <button type="submit" class="btn btn-outline-primary">Filter</button>
                </form>

                <!-- Tabel Tagihan -->
                <div class="table-responsive">
                    <table class="table table-bordered table-hover">
                        <thead class="thead-dark">
                            <tr>
                                <th>#</th>
                                <th>Nama Siswa</th>
                                <th>Kelas</th>
                                <th>Total Tagihan</th>
                                <th>Status</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($students)) : ?>
                                <?php $no = 1 + (10 * ($pager->getCurrentPage() - 1)); ?>
                                <?php foreach ($students as $student) : ?>
                                    <?php
                                    $studentBills = $billsGrouped[$student['id']] ?? [];
                                    $total = array_sum(array_column($studentBills, 'amount'));
                                    $paid = array_sum(array_column($studentBills, 'paid_amount'));
                                    $status = $total == 0 ? 'Belum Ada Tagihan' : ($paid >= $total ? 'Lunas' : ($paid > 0 ? 'Partial' : 'Belum Lunas'));
                                    ?>
                                    <tr>
                                        <td><?= $no++ ?></td>
                                        <td><?= esc($student['name']) ?></td>
                                        <td><?= esc($student['class'] ?? '-') ?></td>
                                        <td><?= number_format($total, 2) ?></td>
                                        <td><?= $status ?></td>
                                        <td>
                                            <div class="dropdown">
                                                <button class="btn btn-sm btn-primary dropdown-toggle" type="button" data-toggle="dropdown">
                                                    Action
                                                </button>
                                                <div class="dropdown-menu">
                                                    <a class="dropdown-item" href="<?= base_url('payments/create?student_id=' . $student['id']) ?>">Bayar</a>
                                                    <a class="dropdown-item" href="#" data-toggle="modal" data-target="#detailModal-<?= $student['id'] ?>">Detail</a>
                                                    <a class="dropdown-item" href="<?= base_url('bills/print/' . $student['id']) ?>" target="_blank">Cetak</a>
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else : ?>
                                <tr>
                                    <td colspan="6" class="text-center">Tidak ada data siswa.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div class="mt-3">
                    <?= custom_pagination_with_query(
                        $pager,
                        ['q' => $search ?? ''],
                        'bills',
                        'bootstrap_full'
                    ) ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Detail Tagihan -->
<?php if (!empty($students)) : ?>
    <?php foreach ($students as $student) : ?>
        <?php $studentBills = $billsGrouped[$student['id']] ?? []; ?>
        <div class="modal fade" id="detailModal-<?= $student['id'] ?>" tabindex="-1">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title"><?= esc($student['name']) ?> - Detail Tagihan</h5>
                        <button type="button" class="close" data-dismiss="modal">&times;</button>
                    </div>
                    <div class="modal-body">
                        <table class="table table-sm table-bordered">
                            <thead>
                                <tr>
                                    <th>Kategori</th>
                                    <th>Bulan</th>
                                    <th>Tahun</th>
                                    <th>Jumlah</th>
                                    <th>Terbayar</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($studentBills)) : ?>
                                    <?php foreach ($studentBills as $bill) : ?>
                                        <tr>
                                            <td><?= esc($bill['category_name']) ?></td>
                                            <td><?= esc($bill['month']) ?></td>
                                            <td><?= esc($bill['year']) ?></td>
                                            <td><?= number_format($bill['amount'], 2) ?></td>
                                            <td><?= number_format($bill['paid_amount'] ?? 0, 2) ?></td>
                                            <td><?= esc($bill['status']) ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else : ?>
                                    <tr>
                                        <td colspan="6" class="text-center">Belum ada tagihan</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
<?php endif; ?>

<?= $this->endSection() ?>