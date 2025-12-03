<?= $this->extend('layouts/template/apps') ?>
<?php $this->setVar('title', 'Laporan Laba Rugi'); ?>
<?= $this->section('content') ?>
<div class="row">
    <div class="col">
        <div class="card card-primary card-outline">
            <div class="card-header">
                <h5 class="card-title mb-0">Laporan Laba Rugi</h5>
            </div>
            <div class="card-body">

                <!-- Pendapatan -->
                <h6>Pendapatan</h6>
                <div class="table-responsive mb-3">
                    <table class="table table-bordered table-hover">
                        <thead class="thead-dark">
                            <tr>
                                <th>#</th>
                                <th>Nama Akun</th>
                                <th class="text-end">Saldo</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $no = 1;
                            foreach ($income as $i): ?>
                                <tr>
                                    <td><?= $no++ ?></td>
                                    <td><?= esc($i['account_name']) ?></td>
                                    <td class="text-end"><?= number_format($i['saldo'], 2, ',', '.') ?></td>
                                </tr>
                            <?php endforeach ?>
                        </tbody>
                        <tfoot>
                            <tr class="table-primary">
                                <th colspan="2" class="text-end">Total Pendapatan</th>
                                <th class="text-end"><?= number_format($total_income, 2, ',', '.') ?></th>
                            </tr>
                        </tfoot>
                    </table>
                </div>

                <!-- Beban -->
                <h6>Beban</h6>
                <div class="table-responsive mb-3">
                    <table class="table table-bordered table-hover">
                        <thead class="thead-dark">
                            <tr>
                                <th>#</th>
                                <th>Nama Akun</th>
                                <th class="text-end">Saldo</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $no = 1;
                            foreach ($expense as $e): ?>
                                <tr>
                                    <td><?= $no++ ?></td>
                                    <td><?= esc($e['account_name']) ?></td>
                                    <td class="text-end"><?= number_format($e['saldo'], 2, ',', '.') ?></td>
                                </tr>
                            <?php endforeach ?>
                        </tbody>
                        <tfoot>
                            <tr class="table-danger">
                                <th colspan="2" class="text-end">Total Beban</th>
                                <th class="text-end"><?= number_format($total_expense, 2, ',', '.') ?></th>
                            </tr>
                        </tfoot>
                    </table>
                </div>

                <!-- Laba Bersih -->
                <h6>Laba/Rugi Bersih</h6>
                <div class="alert alert-info font-weight-bold text-end">
                    <?= number_format($net_profit, 2, ',', '.') ?>
                </div>

                <!-- Grafik Chart.js -->
                <h6>Grafik Pendapatan vs Beban</h6>
                <canvas id="profitChart" height="120"></canvas>

            </div>
        </div>
    </div>
</div>

<!-- Chart.js CDN -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    const ctx = document.getElementById('profitChart').getContext('2d');
    const profitChart = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: ['Pendapatan', 'Beban', 'Laba Bersih'],
            datasets: [{
                label: 'Jumlah (Rp)',
                data: [<?= $total_income ?>, <?= $total_expense ?>, <?= $net_profit ?>],
                backgroundColor: [
                    'rgba(40, 167, 69, 0.7)', // hijau
                    'rgba(220, 53, 69, 0.7)', // merah
                    'rgba(0, 123, 255, 0.7)' // biru
                ],
                borderColor: [
                    'rgba(40, 167, 69, 1)',
                    'rgba(220, 53, 69, 1)',
                    'rgba(0, 123, 255, 1)'
                ],
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            plugins: {
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            return 'Rp ' + context.raw.toLocaleString('id-ID', {
                                minimumFractionDigits: 2
                            });
                        }
                    }
                },
                legend: {
                    display: false
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: function(value) {
                            return 'Rp ' + value.toLocaleString('id-ID', {
                                minimumFractionDigits: 2
                            });
                        }
                    }
                }
            }
        }
    });
</script>
<?= $this->endSection() ?>