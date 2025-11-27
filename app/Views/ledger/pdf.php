<!DOCTYPE html>
<html>

<head>
    <title>Ledger Buku Besar</title>
    <style>
        body {
            font-family: Arial, Helvetica, sans-serif;
            font-size: 11px;
            margin: 20px;
        }

        h3,
        h4 {
            margin: 0;
        }

        h3 {
            text-align: center;
            margin-bottom: 5px;
        }

        h4 {
            margin-top: 20px;
        }

        p.period {
            text-align: center;
            margin-top: 0;
            margin-bottom: 20px;
            font-size: 10px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
            page-break-inside: auto;
        }

        table,
        th,
        td {
            border: 1px solid #333;
        }

        th {
            background-color: #f2f2f2;
            padding: 5px;
            text-align: center;
        }

        td {
            padding: 4px;
            vertical-align: top;
        }

        tr {
            page-break-inside: avoid;
            page-break-after: auto;
        }

        .total-row {
            font-weight: bold;
            background-color: #e6e6e6;
        }

        .negative {
            color: red;
        }

        .center {
            text-align: center;
        }
    </style>
</head>

<body>
    <h3>Ledger Buku Besar</h3>
    <?php if ($start && $end): ?>
        <p class="period">Periode: <?= esc($start) ?> s/d <?= esc($end) ?></p>
    <?php endif; ?>

    <?php foreach ($ledger as $accountName => $data): ?>
        <h4><?= esc($accountName) ?></h4>
        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>Tanggal</th>
                    <th>Deskripsi</th>
                    <th>Debit</th>
                    <th>Kredit</th>
                    <th>Saldo</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($data['entries'])): ?>
                    <?php $no = 1; ?>
                    <?php foreach ($data['entries'] as $entry): ?>
                        <tr>
                            <td class="center"><?= $no++ ?></td>
                            <td><?= esc($entry['date']) ?></td>
                            <td><?= esc($entry['description']) ?></td>
                            <td class="center"><?= number_format($entry['debit'], 2) ?></td>
                            <td class="center"><?= number_format($entry['credit'], 2) ?></td>
                            <td class="<?= $entry['balance'] < 0 ? 'negative' : '' ?>"><?= number_format($entry['balance'], 2) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    <tr class="total-row">
                        <td colspan="3" class="center">Total</td>
                        <td class="center"><?= number_format($data['totalDebit'], 2) ?></td>
                        <td class="center"><?= number_format($data['totalCredit'], 2) ?></td>
                        <td></td>
                    </tr>
                <?php else: ?>
                    <tr>
                        <td colspan="6" class="center">Tidak ada transaksi</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    <?php endforeach; ?>
</body>

</html>