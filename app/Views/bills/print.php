<!DOCTYPE html>
<html>

<head>
    <title>Tagihan Siswa - <?= esc($student['name']) ?></title>
    <style>
        body {
            font-family: sans-serif;
            font-size: 12px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }

        th,
        td {
            border: 1px solid #000;
            padding: 5px;
            text-align: left;
        }

        th {
            background-color: #f0f0f0;
        }

        h2 {
            text-align: center;
        }
    </style>
</head>

<body>
    <h2>Tagihan Siswa</h2>
    <p><strong>Nama:</strong> <?= esc($student['name']) ?><br>
        <strong>Kelas:</strong> <?= esc($student['class'] ?? '-') ?>
    </p>

    <table>
        <thead>
            <tr>
                <th>No</th>
                <th>Kategori</th>
                <th>Bulan</th>
                <th>Tahun</th>
                <th>Jumlah</th>
                <th>Terbayar</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            <?php if (!empty($bills)) : ?>
                <?php $no = 1; ?>
                <?php foreach ($bills as $bill) : ?>
                    <tr>
                        <td><?= $no++ ?></td>
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
                    <td colspan="7" style="text-align:center;">Belum ada tagihan</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</body>

</html>