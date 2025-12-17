<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <title>Tagihan Siswa - <?= esc($student['name']) ?></title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
            margin: 20px;
            color: #333;
        }

        h2,
        h4 {
            margin: 0;
            padding: 0;
        }

        .summary {
            margin-top: 20px;
            width: 100%;
            border-collapse: collapse;
        }

        .summary td {
            padding: 10px;
            text-align: center;
            border: 1px solid #ddd;
            font-weight: bold;
        }

        .summary .due-now {
            background-color: #ffc107;
            color: #000;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 18px;
        }

        th,
        td {
            border: 1px solid #ccc;
            padding: 8px;
            text-align: center;
        }

        th {
            background-color: #007bff;
            color: #fff;
        }

        .badge {
            display: inline-block;
            padding: 3px 7px;
            border-radius: 4px;
            font-size: 10px;
            white-space: nowrap;
            font-weight: bold;
        }

        .paid {
            background-color: #28a745;
            color: #fff;
        }

        .partial {
            background-color: #ffc107;
            color: #000;
        }

        .unpaid {
            background-color: #dc3545;
            color: #fff;
        }

        .progress {
            background-color: #e9ecef;
            border-radius: 4px;
            height: 10px;
            width: 100%;
            margin-top: 3px;
        }

        .progress-bar {
            height: 100%;
            border-radius: 4px;
            background-color: #28a745;
        }

        small {
            font-size: 10px;
        }

        .section-title {
            margin-top: 25px;
            margin-bottom: 8px;
            font-weight: bold;
            font-size: 13px;
        }
    </style>
</head>

<body>

    <?php
    function tanggalIndonesia($date)
    {
        $bulan = [
            1 => 'Januari',
            'Februari',
            'Maret',
            'April',
            'Mei',
            'Juni',
            'Juli',
            'Agustus',
            'September',
            'Oktober',
            'November',
            'Desember'
        ];
        $d = date('d', strtotime($date));
        $m = (int)date('m', strtotime($date));
        $y = date('Y', strtotime($date));
        return "$d {$bulan[$m]} $y";
    }
    ?>

    <h2>Detail Billing</h2>
    <h4>Siswa: <?= esc($student['name']) ?></h4>
    <p>Tanggal Cetak: <?= tanggalIndonesia($datePrint) ?></p>

    <!-- SUMMARY -->
    <table class="summary">
        <tr>
            <td>Total Tagihan<br>Rp <?= number_format($totalBills, 0, ',', '.') ?></td>
            <td>Total Pembayaran + Saldo<br>Rp <?= number_format($totalPayments, 0, ',', '.') ?></td>
            <td class="due-now">Harus Dibayar Sekarang<br>Rp <?= number_format($amountDueNow, 0, ',', '.') ?></td>
            <td>Saldo / Uang Kelebihan<br>Rp <?= number_format($overpaid, 0, ',', '.') ?></td>
        </tr>
    </table>

    <!-- BILLING BULANAN -->
    <div class="section-title">Billing Bulanan</div>
    <table>
        <thead>
            <tr>
                <th>#</th>
                <th>Bulan</th>
                <th>Tahun</th>
                <th>Kategori</th>
                <th>Kelas</th>
                <th>Jumlah</th>
                <th>Terbayar</th>
                <th>Status</th>
                <th>Rincian</th>
            </tr>
        </thead>
        <tbody>
            <?php $no = 1;
            foreach ($monthly as $b):
                $paidPercent = $b['amount'] > 0 ? ($b['paid_amount'] / $b['amount']) * 100 : 0;
            ?>
                <tr>
                    <td><?= $no++ ?></td>
                    <td><?= $b['month'] ? date('F', mktime(0, 0, 0, $b['month'], 10)) : '-' ?></td>
                    <td><?= $b['year'] ?></td>
                    <td><?= $b['category_name'] ?></td>
                    <td><?= esc($b['kelas']) ?></td>
                    <td>Rp <?= number_format($b['amount'], 0, ',', '.') ?></td>
                    <td>
                        Rp <?= number_format($b['paid_amount'], 0, ',', '.') ?>
                        <?php if ($paidPercent > 0 && $paidPercent < 100): ?>
                            <div class="progress">
                                <div class="progress-bar" style="width:<?= $paidPercent ?>%"></div>
                            </div>
                        <?php endif; ?>
                    </td>
                    <td>
                        <span class="badge <?= $b['status'] ?>"><?= ucfirst($b['status']) ?></span>
                    </td>
                    <td>
                        <?php if (!empty($b['payment_breakdown'])): ?>
                            <?php foreach ($b['payment_breakdown'] as $p): ?>
                                Rp <?= number_format($p['amount'], 0, ',', '.') ?>
                                (<?= date('d M Y', strtotime($p['created_at'])) ?>)<br>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <!-- BILLING ONE-TIME -->
    <div class="section-title">Billing One-time</div>
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
                $paidPercent = $b['amount'] > 0 ? ($b['paid_amount'] / $b['amount']) * 100 : 0;
            ?>
                <tr>
                    <td><?= $no++ ?></td>
                    <td><?= $b['category_name'] ?></td>
                    <td>Rp <?= number_format($b['amount'], 0, ',', '.') ?></td>
                    <td>
                        Rp <?= number_format($b['paid_amount'], 0, ',', '.') ?>
                        <?php if ($paidPercent > 0 && $paidPercent < 100): ?>
                            <div class="progress">
                                <div class="progress-bar" style="width:<?= $paidPercent ?>%"></div>
                            </div>
                        <?php endif; ?>
                    </td>
                    <td>
                        <span class="badge <?= $b['status'] ?>"><?= ucfirst($b['status']) ?></span>
                    </td>
                    <td>
                        <?php if (!empty($b['payment_breakdown'])): ?>
                            <?php foreach ($b['payment_breakdown'] as $p): ?>
                                Rp <?= number_format($p['amount'], 0, ',', '.') ?>
                                (<?= date('d M Y', strtotime($p['created_at'])) ?>)<br>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <!-- PAYMENT HISTORY -->
    <div class="section-title">Daftar Pembayaran Siswa</div>
    <table>
        <thead>
            <tr>
                <th>#</th>
                <th>Tanggal</th>
                <th>Jumlah</th>
                <th>Metode</th>
                <th>Referensi</th>
            </tr>
        </thead>
        <tbody>
            <?php $no = 1;
            foreach ($payments as $p): ?>
                <tr>
                    <td><?= $no++ ?></td>
                    <td><?= $p['date'] ? date('d M Y', strtotime($p['date'])) : '-' ?></td>
                    <td>Rp <?= number_format($p['total_amount'], 0, ',', '.') ?></td>
                    <td><?= esc($p['method'] ?? '-') ?></td>
                    <td><?= esc($p['reference'] ?? '-') ?></td>
                </tr>
            <?php endforeach; ?>
            <?php if (empty($payments)): ?>
                <tr>
                    <td colspan="5">Belum ada pembayaran</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>

</body>

</html>