<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <title>Detail Billing - <?= esc($student['name']) ?></title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
            background-color: #f4f4f9;
            margin: 20px;
        }

        h2,
        h4 {
            margin: 0;
        }

        /* Card Ringkasan */
        .summary-card {
            display: flex;
            gap: 15px;
            margin-top: 20px;
        }

        .card {
            flex: 1;
            background-color: #ffffff;
            border-radius: 8px;
            padding: 15px 20px;
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.1);
            text-align: center;
            font-weight: bold;
        }

        .card.due-now {
            background-color: #ffc107;
            color: #000;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            background-color: #fff;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.05);
        }

        th,
        td {
            padding: 10px;
            text-align: center;
        }

        th {
            background-color: #007bff;
            color: #fff;
        }

        .badge {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 10px;
            color: #fff;
        }

        .Lunas {
            background-color: #28a745;
        }

        .Sebagian {
            background-color: #ffc107;
            color: #000;
        }

        .Belum_Bayar {
            background-color: #dc3545;
        }

        .progress {
            background-color: #e9ecef;
            border-radius: 4px;
            height: 12px;
            overflow: hidden;
            margin-top: 4px;
        }

        .progress-bar {
            height: 100%;
            border-radius: 4px;
            background-color: #28a745;
        }

        small {
            font-size: 10px;
        }
    </style>
</head>

<body>
    <h2>Detail Billing</h2>
    <h4>Siswa: <?= esc($student['name']) ?></h4>
    <p>Tanggal Cetak: <?= esc($datePrint) ?></p>

    <!-- Ringkasan Card -->
    <div class="summary-card">
        <div class="card">
            Total Tagihan<br>
            Rp <?= number_format($totalBills, 0, ',', '.') ?>
        </div>
        <div class="card">
            Total Pembayaran<br>
            Rp <?= number_format($totalPayments, 0, ',', '.') ?>
        </div>
        <div class="card due-now">
            Harus Dibayar Sekarang<br>
            Rp <?= number_format($amountDueNow, 0, ',', '.') ?>
        </div>
    </div>

    <!-- Billing Bulanan -->
    <h4>Billing Bulanan</h4>
    <table>
        <thead>
            <tr>
                <th>#</th>
                <th>Bulan</th>
                <th>Tahun</th>
                <th>Kategori</th>
                <th>Jumlah</th>
                <th>Terbayar</th>
                <th>Status</th>
                <th>Rincian</th>
            </tr>
        </thead>
        <tbody>
            <?php $no = 1;
            foreach ($monthly as $b):
                $paidPercent = $b['amount'] > 0 ? ($b['paid_amount'] / $b['amount']) * 100 : 0; ?>
                <tr>
                    <td><?= $no++ ?></td>
                    <td><?= $b['month'] ? date('F', mktime(0, 0, 0, $b['month'], 10)) : '-' ?></td>
                    <td><?= $b['year'] ?></td>
                    <td><?= $b['category'] ?></td>
                    <td>Rp <?= number_format($b['amount'], 0, ',', '.') ?></td>
                    <td>
                        Rp <?= number_format($b['paid_amount'], 0, ',', '.') ?>
                        <?php if ($paidPercent > 0 && $paidPercent < 100): ?>
                            <div class="progress">
                                <div class="progress-bar" style="width: <?= $paidPercent ?>%"></div>
                            </div>
                        <?php endif; ?>
                    </td>
                    <td><span class="badge <?= str_replace(' ', '_', $b['status']) ?>"><?= $b['status'] ?></span></td>
                    <td>
                        <?php if (!empty($b['payment_breakdown'])): ?>
                            <?php foreach ($b['payment_breakdown'] as $p): ?>
                                Rp <?= number_format($p['amount'], 0, ',', '.') ?> (<?= date('d M Y', strtotime($p['date'])) ?>)<br>
                            <?php endforeach; ?>
                        <?php endif; ?>
                        <?php if (!empty($b['partial_reason'])): ?>
                            <small><?= $b['partial_reason'] ?></small>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <!-- Billing One-time -->
    <h4>Billing One-time</h4>
    <table>
        <thead>
            <tr>
                <th>#</th>
                <th>Kategori</th>
                <th>Jumlah</th>
                <th>Terbayar</th>
                <th>Status</th>
                <th>Rincian</th>
            </tr>
        </thead>
        <tbody>
            <?php $no = 1;
            foreach ($one_time as $b):
                $paidPercent = $b['amount'] > 0 ? ($b['paid_amount'] / $b['amount']) * 100 : 0; ?>
                <tr>
                    <td><?= $no++ ?></td>
                    <td><?= $b['category'] ?></td>
                    <td>Rp <?= number_format($b['amount'], 0, ',', '.') ?></td>
                    <td>
                        Rp <?= number_format($b['paid_amount'], 0, ',', '.') ?>
                        <?php if ($paidPercent > 0 && $paidPercent < 100): ?>
                            <div class="progress">
                                <div class="progress-bar" style="width: <?= $paidPercent ?>%"></div>
                            </div>
                        <?php endif; ?>
                    </td>
                    <td><span class="badge <?= str_replace(' ', '_', $b['status']) ?>"><?= $b['status'] ?></span></td>
                    <td>
                        <?php if (!empty($b['payment_breakdown'])): ?>
                            <?php foreach ($b['payment_breakdown'] as $p): ?>
                                Rp <?= number_format($p['amount'], 0, ',', '.') ?> (<?= date('d M Y', strtotime($p['date'])) ?>)<br>
                            <?php endforeach; ?>
                        <?php endif; ?>
                        <?php if (!empty($b['partial_reason'])): ?>
                            <small><?= $b['partial_reason'] ?></small>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

</body>

</html>