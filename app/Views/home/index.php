<?= $this->extend('layouts/template/apps') ?>
<?= $this->section('content') ?>

<div class="container-fluid">
    <h1 class="mb-4">Selamat Datang</h1>

    <!-- ================== Form Filter Tanggal ================== -->
    <div class="card mb-4 shadow-sm">
        <div class="card-header bg-light">
            <strong>Filter Berdasarkan Tanggal</strong>
        </div>
        <div class="card-body">
            <form method="get" action="">
                <div class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label">Start Date</label>
                        <input type="date" name="start_date" class="form-control" value="<?= esc($start_date) ?>">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">End Date</label>
                        <input type="date" name="end_date" class="form-control" value="<?= esc($end_date) ?>">
                    </div>
                    <div class="col-md-3 align-self-end">
                        <button type="submit" class="btn btn-primary w-100">Filter</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- ================== Total Tipe Akun ================== -->
    <h4 class="mt-4 mb-2">Total Saldo Akun</h4>
    <div class="row g-3 mb-4">
        <?php
        $akunList = [
            ['label' => 'Asset', 'value' => $totals['asset'], 'icon' => 'fas fa-wallet', 'color' => 'info'],
            ['label' => 'Liability', 'value' => $totals['liability'], 'icon' => 'fas fa-hand-holding-usd', 'color' => 'danger'],
            ['label' => 'Equity', 'value' => $totals['equity'], 'icon' => 'fas fa-chart-line', 'color' => 'success'],
            ['label' => 'Income', 'value' => $totals['income'], 'icon' => 'fas fa-dollar-sign', 'color' => 'warning'],
            ['label' => 'Expense', 'value' => $totals['expense'], 'icon' => 'fas fa-file-invoice-dollar', 'color' => 'secondary'],
        ];
        foreach ($akunList as $akun): ?>
            <div class="col-lg-3 col-6">
                <div class="small-box bg-<?= $akun['color'] ?> shadow-sm">
                    <div class="inner">
                        <h3><?= number_format($akun['value'], 2) ?></h3>
                        <p><?= $akun['label'] ?></p>
                    </div>
                    <div class="icon"><i class="<?= $akun['icon'] ?>"></i></div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <!-- ================== Total Tagihan & Pembayaran ================== -->
    <h4 class="mt-4 mb-2">Tagihan dan Pembayaran</h4>
    <div class="row g-3 mb-4">
        <?php
        $billingList = [
            ['label' => 'Total Tagihan', 'value' => $total_tagihan, 'icon' => 'fas fa-file-invoice', 'color' => 'primary'],
            ['label' => 'Total Dibayar', 'value' => $total_dibayar, 'icon' => 'fas fa-money-bill-wave', 'color' => 'success'],
            ['label' => 'Total Tunggakan', 'value' => $total_tunggakan, 'icon' => 'fas fa-exclamation-triangle', 'color' => 'danger'],
            ['label' => 'Siswa Menunggak', 'value' => $jumlah_siswa_menunggak, 'icon' => 'fas fa-user-graduate', 'color' => 'warning'],
        ];
        foreach ($billingList as $bill): ?>
            <div class="col-lg-3 col-6">
                <div class="small-box bg-<?= $bill['color'] ?> shadow-sm">
                    <div class="inner">
                        <h3><?= number_format($bill['value'], 2) ?></h3>
                        <p><?= $bill['label'] ?></p>
                    </div>
                    <div class="icon"><i class="<?= $bill['icon'] ?>"></i></div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <!-- ================== Charts ================== -->
    <h4 class="mt-4 mb-2">Visualisasi Data</h4>
    <div class="row g-3">
        <!-- Pie Chart Saldo Akun -->
        <div class="col-lg-6">
            <div class="card shadow-sm">
                <div class="card-header bg-light">
                    <strong>Distribusi Saldo Akun</strong>
                </div>
                <div class="card-body">
                    <canvas id="saldoChart"></canvas>
                </div>
            </div>
        </div>

        <!-- Bar Chart Tagihan vs Pembayaran -->
        <div class="col-lg-6">
            <div class="card shadow-sm">
                <div class="card-header bg-light">
                    <strong>Tagihan vs Pembayaran</strong>
                </div>
                <div class="card-body">
                    <canvas id="billingChart"></canvas>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ================== Chart.js ================== -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    // Pie Chart Saldo Akun
    const ctxSaldo = document.getElementById('saldoChart').getContext('2d');
    const saldoChart = new Chart(ctxSaldo, {
        type: 'pie',
        data: {
            labels: ['Asset', 'Liability', 'Equity', 'Income', 'Expense'],
            datasets: [{
                data: [
                    <?= $totals['asset'] ?>,
                    <?= $totals['liability'] ?>,
                    <?= $totals['equity'] ?>,
                    <?= $totals['income'] ?>,
                    <?= $totals['expense'] ?>
                ],
                backgroundColor: ['#17a2b8', '#dc3545', '#28a745', '#ffc107', '#6c757d']
            }]
        },
        options: {
            responsive: true
        }
    });

    // Bar Chart Tagihan vs Pembayaran vs Tunggakan
    const ctxBilling = document.getElementById('billingChart').getContext('2d');
    const billingChart = new Chart(ctxBilling, {
        type: 'bar',
        data: {
            labels: ['Tagihan', 'Dibayar', 'Tunggakan'],
            datasets: [{
                label: 'Jumlah (Rp)',
                data: [<?= $total_tagihan ?>, <?= $total_dibayar ?>, <?= $total_tunggakan ?>],
                backgroundColor: ['#007bff', '#28a745', '#dc3545']
            }]
        },
        options: {
            responsive: true,
            scales: {
                y: {
                    beginAtZero: true
                }
            }
        }
    });
</script>

<?= $this->endSection() ?>