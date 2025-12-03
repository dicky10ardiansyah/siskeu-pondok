<?= $this->extend('layouts/template/apps') ?>
<?= $this->section('content') ?>

<div class="card card-info shadow-sm">
    <div class="card-header">
        <h3 class="card-title">
            <i class="fas fa-calendar-alt"></i> Pilih Tahun
        </h3>
    </div>
    <div class="card-body">
        <form method="get">
            <div class="input-group">
                <select name="year" class="form-control mr-2">
                    <?php
                    $currentYear = date('Y');
                    $startYear = $currentYear - 5; // 5 tahun sebelumnya
                    for ($y = $currentYear; $y >= $startYear; $y--):
                    ?>
                        <option value="<?= $y ?>" <?= $year == $y ? 'selected' : '' ?>><?= $y ?></option>
                    <?php endfor; ?>
                </select>
                <button class="btn btn-info">
                    <i class="fas fa-filter"></i> Filter
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Summary Cards -->
<div class="row">
    <!-- Total Tagihan -->
    <div class="col-lg-3 col-6">
        <div class="small-box bg-info">
            <div class="inner">
                <h3>Rp <?= number_format($summary['total_bill'], 0, ',', '.') ?></h3>
                <p>Total Tagihan</p>
            </div>
            <div class="icon">
                <i class="fas fa-file-invoice-dollar"></i>
            </div>
        </div>
    </div>

    <!-- Total Pembayaran -->
    <div class="col-lg-3 col-6">
        <div class="small-box bg-success">
            <div class="inner">
                <h3>Rp <?= number_format($summary['total_payment'], 0, ',', '.') ?></h3>
                <p>Total Pembayaran</p>
            </div>
            <div class="icon">
                <i class="fas fa-money-bill-wave"></i>
            </div>
        </div>
    </div>

    <!-- Total Tunggakan -->
    <div class="col-lg-3 col-6">
        <div class="small-box bg-warning">
            <div class="inner">
                <h3>Rp <?= number_format($summary['total_due'], 0, ',', '.') ?></h3>
                <p>Total Tunggakan</p>
            </div>
            <div class="icon">
                <i class="fas fa-exclamation-triangle"></i>
            </div>
        </div>
    </div>

    <!-- Realisasi % -->
    <div class="col-lg-3 col-6">
        <div class="small-box bg-primary">
            <div class="inner">
                <h3><?= $summary['realization_percentage'] ?>%</h3>
                <p>Realisasi</p>
            </div>
            <div class="icon">
                <i class="fas fa-percentage"></i>
            </div>
        </div>
    </div>
</div>

<!-- Students in Debt -->
<div class="row mb-4">
    <div class="col-lg-3 col-6">
        <div class="small-box bg-danger">
            <div class="inner">
                <h3><?= $summary['students_in_debt'] ?></h3>
                <p>Siswa Menunggak</p>
            </div>
            <div class="icon">
                <i class="fas fa-user-times"></i>
            </div>
        </div>
    </div>
</div>

<!-- Line Chart: Tagihan vs Pembayaran per Bulan -->
<div class="row">
    <div class="col-12">
        <div class="card card-primary">
            <div class="card-header">
                <h3 class="card-title">Tagihan vs Pembayaran per Bulan</h3>
            </div>
            <div class="card-body">
                <canvas id="monthlyChart" style="height: 300px;"></canvas>
            </div>
        </div>
    </div>
</div>

<!-- Chart.js Script -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    const ctx = document.getElementById('monthlyChart').getContext('2d');
    const monthlyChart = new Chart(ctx, {
        type: 'line',
        data: {
            labels: <?= json_encode($monthly_chart['months']) ?>,
            datasets: [{
                    label: 'Tagihan',
                    data: <?= json_encode($monthly_chart['bills']) ?>,
                    borderColor: 'rgba(60,141,188,1)',
                    backgroundColor: 'rgba(60,141,188,0.2)',
                    fill: true,
                    tension: 0.3
                },
                {
                    label: 'Pembayaran',
                    data: <?= json_encode($monthly_chart['payments']) ?>,
                    borderColor: 'rgba(0,166,90,1)',
                    backgroundColor: 'rgba(0,166,90,0.2)',
                    fill: true,
                    tension: 0.3
                }
            ]
        },
        options: {
            responsive: true,
            plugins: {
                legend: {
                    position: 'top'
                },
                tooltip: {
                    mode: 'index',
                    intersect: false
                }
            },
            interaction: {
                mode: 'nearest',
                axis: 'x',
                intersect: false
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: function(value) {
                            return 'Rp ' + value.toLocaleString();
                        }
                    }
                }
            }
        }
    });
</script>

<?= $this->endSection() ?>