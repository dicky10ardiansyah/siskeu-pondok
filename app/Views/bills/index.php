<?= $this->extend('layouts/template/apps') ?>
<?php $this->setVar('title', 'Tagihan Siswa'); ?>

<?= $this->section('content') ?>
<div class="row">
    <div class="col">
        <div class="card">
            <div class="card-header d-flex align-items-center">
                <h5 class="mb-0">Tabel Tagihan Siswa</h5>
                <a href="#" class="btn btn-primary ml-auto" data-toggle="modal" data-target="#generateModal">
                    <i class="fas fa-plus"></i> Generate Tagihan
                </a>
            </div>
            <div class="card-body">

                <!-- SweetAlert2 -->
                <?php if (session()->getFlashdata('success')) : ?>
                    <script>
                        Swal.fire({
                            toast: true,
                            position: 'top-end',
                            icon: 'success',
                            title: '<?= session()->getFlashdata('success') ?>',
                            showConfirmButton: false,
                            timer: 3000,
                            timerProgressBar: true
                        });
                    </script>
                <?php endif ?>
                <?php if (session()->getFlashdata('error')) : ?>
                    <script>
                        Swal.fire({
                            toast: true,
                            position: 'top-end',
                            icon: 'error',
                            title: '<?= session()->getFlashdata('error') ?>',
                            showConfirmButton: false,
                            timer: 3000,
                            timerProgressBar: true
                        });
                    </script>
                <?php endif ?>

                <!-- Filter Form -->
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

                <!-- Table Tagihan -->
                <div class="table-responsive">
                    <table class="table table-bordered">
                        <thead class="thead-dark">
                            <tr>
                                <th class="text-center">#</th>
                                <th>Nama Siswa</th>
                                <th class="text-center">Kelas</th>
                                <th class="text-right">Jumlah</th>
                                <th class="text-center">Bulan</th>
                                <th class="text-center">Status</th>
                                <th class="text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($bills)): ?>
                                <?php $no = 1 + (10 * ($pager->getCurrentPage() - 1)); ?>
                                <?php foreach ($bills as $bill): ?>
                                    <tr>
                                        <td class="text-center"><?= $no++ ?></td>
                                        <td><?= esc($bill['student_name']) ?></td>
                                        <td class="text-center"><?= esc($bill['class'] ?? '-') ?></td>
                                        <td class="text-right"><?= number_format($bill['amount'], 2) ?></td>
                                        <td class="text-center"><?= date('F', mktime(0, 0, 0, $bill['month'], 1)) ?></td>
                                        <td class="text-center">
                                            <span class="badge <?= $bill['status'] == 'paid' ? 'badge-success' : ($bill['status'] == 'partial' ? 'badge-warning' : 'badge-danger') ?>">
                                                <?= $bill['status'] ?>
                                            </span>
                                        </td>
                                        <td class="text-center">
                                            <a href="<?= base_url('payments/create?student_id=' . $bill['student_id']) ?>" class="btn btn-sm btn-primary">Bayar</a>
                                            <a href="<?= base_url('bills/print/' . $bill['student_id']) ?>" target="_blank" class="btn btn-sm btn-secondary">Cetak</a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="7" class="text-center">Tidak ada tagihan.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div class="mt-3"><?= $pager->links('students', 'bootstrap_full') ?></div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Generate Tagihan -->
<div class="modal fade" id="generateModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="<?= base_url('bills/generate') ?>" method="post">
                <div class="modal-header">
                    <h5 class="modal-title">Generate Tagihan</h5>
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                </div>
                <div class="modal-body">
                    <?= csrf_field() ?>
                    <div class="form-group">
                        <label>Bulan</label>
                        <select name="month" class="form-control" required>
                            <?php for ($m = 1; $m <= 12; $m++): ?>
                                <option value="<?= $m ?>" <?= $m == date('m') ? 'selected' : '' ?>><?= date('F', mktime(0, 0, 0, $m, 1)) ?></option>
                            <?php endfor; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Tahun</label>
                        <input type="number" name="year" class="form-control" value="<?= date('Y') ?>" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-primary">Generate</button>
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?= $this->endSection() ?>