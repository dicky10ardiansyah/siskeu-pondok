<?= $this->extend('layouts/template/apps') ?>
<?php $this->setVar('title', 'Billing'); ?>

<?= $this->section('content') ?>
<div class="row">
    <div class="col">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Tabel Billing</h5>
                <form action="<?= base_url('billing/generate') ?>" method="post" class="ml-auto d-flex">
                    <?= csrf_field() ?>
                    <?php
                    $currentMonth = date('n'); // 1-12
                    $currentYear = date('Y');
                    ?>
                    <select name="month" class="form-control mr-2" required>
                        <option value="">Pilih Bulan</option>
                        <?php for ($m = 1; $m <= 12; $m++): ?>
                            <option value="<?= $m ?>" <?= ($m == $currentMonth) ? 'selected' : '' ?>>
                                <?= date('F', mktime(0, 0, 0, $m, 10)) ?>
                            </option>
                        <?php endfor; ?>
                    </select>
                    <select name="year" class="form-control mr-2" required>
                        <option value="">Pilih Tahun</option>
                        <?php for ($y = $currentYear - 3; $y <= $currentYear + 3; $y++): ?>
                            <option value="<?= $y ?>" <?= ($y == $currentYear) ? 'selected' : '' ?>>
                                <?= $y ?>
                            </option>
                        <?php endfor; ?>
                    </select>
                    <button type="submit" class="btn btn-primary d-flex align-items-center">
                        <i class="fas fa-download mr-2"></i> Generate
                    </button>
                </form>
            </div>

            <div class="card-body">

                <!-- Filter -->
                <form action="<?= base_url('billing') ?>" method="get" class="form-inline mb-3">
                    <input type="text" name="keyword" class="form-control mr-2" placeholder="Cari nama siswa..." value="<?= esc($keyword ?? '') ?>">

                    <select name="filter_month" class="form-control mr-2">
                        <option value="">Semua Bulan</option>
                        <?php for ($m = 1; $m <= 12; $m++): ?>
                            <option value="<?= $m ?>" <?= (isset($filter_month) && $filter_month == $m) ? 'selected' : '' ?>>
                                <?= date('F', mktime(0, 0, 0, $m, 10)) ?>
                            </option>
                        <?php endfor; ?>
                    </select>

                    <select name="filter_year" class="form-control mr-2">
                        <option value="">Semua Tahun</option>
                        <?php for ($y = date('Y'); $y <= date('Y') + 5; $y++): ?>
                            <option value="<?= $y ?>" <?= (isset($filter_year) && $filter_year == $y) ? 'selected' : '' ?>>
                                <?= $y ?>
                            </option>
                        <?php endfor; ?>
                    </select>

                    <select name="filter_status" class="form-control mr-2">
                        <option value="">Semua Status</option>
                        <option value="Lunas" <?= (isset($filter_status) && $filter_status == 'Lunas') ? 'selected' : '' ?>>Lunas</option>
                        <option value="Tunggakan" <?= (isset($filter_status) && $filter_status == 'Tunggakan') ? 'selected' : '' ?>>Tunggakan</option>
                    </select>

                    <button type="submit" class="btn btn-outline-primary">Filter</button>
                </form>

                <!-- Tabel Billing -->
                <div class="table-responsive">
                    <table class="table table-bordered table-hover">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Nama Siswa</th>
                                <th>Status</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($bills as $no => $bill): ?>
                                <tr>
                                    <td><?= $no + 1 + ($pager->getCurrentPage('bills') - 1) * 10 ?></td>
                                    <td><?= esc($bill['student']) ?></td>
                                    <td>
                                        <span class="badge <?= $bill['status_tagihan'] == 'Lunas' ? 'bg-success' : 'bg-danger' ?>">
                                            <?= $bill['status_tagihan'] ?>
                                        </span>
                                    </td>
                                    <td>
                                        <a href="<?= base_url('billing/detail/' . $bill['student_id']) ?>" class="btn btn-sm btn-info">
                                            <i class="fas fa-eye"></i> Detail
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div class="mt-3">
                    <?= custom_pagination_with_query(
                        $pager,
                        [
                            'keyword' => $keyword ?? '',
                            'filter_month' => $filter_month ?? '',
                            'filter_year' => $filter_year ?? '',
                            'filter_status' => $filter_status ?? ''
                        ],
                        'bills',
                        'bootstrap_full'
                    ) ?>
                </div>

            </div>
        </div>
    </div>
</div>

<!-- SweetAlert -->
<?php if (session()->getFlashdata('success')): ?>
    <script>
        Swal.fire({
            toast: true,
            position: 'top-end',
            icon: 'success',
            title: '<?= session()->getFlashdata('success') ?>',
            showConfirmButton: false,
            timer: 3000,
            timerProgressBar: true,
        });
    </script>
<?php endif; ?>

<?php if (session()->getFlashdata('error')): ?>
    <script>
        Swal.fire({
            icon: 'error',
            title: 'Terjadi Kesalahan',
            html: '<?= session()->getFlashdata('error') ?>',
        });
    </script>
<?php endif; ?>

<?= $this->endSection() ?>