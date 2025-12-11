<!DOCTYPE html>
<html>

<head>
    <title>Kwitansi Pembayaran</title>
    <style>
        @page {
            size: A4;
            margin: 10mm;
        }

        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
        }

        /* Container kwitansi */
        .kwitansi {
            width: 100%;
            border: 1px solid #000;
            padding: 10px;
            margin-bottom: 10px;
            box-sizing: border-box;
            page-break-inside: avoid;
            /* agar tidak terpotong */
        }

        /* Tinggi tiap kwitansi untuk 2 per halaman */
        .half-page {
            height: calc((297mm - 30mm) / 2);
            /* A4 tinggi 297mm, margin top+bottom */
        }

        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 12px;
            margin-bottom: 5px;
        }

        table,
        th,
        td {
            border: 1px solid #000;
        }

        th,
        td {
            padding: 5px;
            text-align: left;
        }

        th {
            background: #f2f2f2;
        }

        /* Kotak tanda tangan */
        .signature-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }

        .signature-table td {
            border: 1px solid #000;
            width: 50%;
            height: 60px;
            text-align: center;
            vertical-align: bottom;
            padding-bottom: 5px;
            font-size: 12px;
        }

        .footer {
            text-align: center;
            font-size: 12px;
            margin-top: 5px;
        }
    </style>
</head>

<body>

    <!-- Kwitansi 1 -->
    <div class="kwitansi half-page">
        <table>
            <tr>
                <td colspan="2" style="text-align:center; font-weight:bold;">KWITANSI PEMBAYARAN</td>
            </tr>
            <tr>
                <td>ID Payment: <?= $payment['id'] ?></td>
                <td>Tanggal: <?php
                                setlocale(LC_TIME, 'id_ID.utf8'); // atau 'indonesia' tergantung server
                                $rawDate = $payment['date'];
                                echo strftime('%d %B %Y', strtotime($rawDate)); // contoh output: 06 Desember 2025
                                ?>
                </td>
            </tr>
        </table>

        <table>
            <tr>
                <td><strong>Nama Siswa:</strong> <?= $payment['student_name'] ?> (NIS: <?= $payment['nis'] ?>)</td>
            </tr>
            <tr>
                <td><strong>Metode Pembayaran:</strong> <?= $payment['method'] ?? '-' ?></td>
            </tr>
            <tr>
                <td><strong>Referensi:</strong> <?= $payment['reference'] ?? '' ?></td>
            </tr>
        </table>

        <table>
            <thead>
                <tr>
                    <th>Ceklis</th>
                    <th>Deskripsi</th>
                    <th>Jumlah</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($rules as $rule): ?>
                    <tr>
                        <td style="text-align:center;"><input type="checkbox"></td>
                        <td><?= $rule['category_name'] ?></td>
                        <td>Rp <?= number_format($rule['amount'], 2, ',', '.') ?></td>
                    </tr>
                <?php endforeach; ?>
                <tr>
                    <td></td>
                    <td>Total</td>
                    <td>Rp <?= number_format($payment['total_amount'], 2, ',', '.') ?></td>
                </tr>
            </tbody>
        </table>

        <table class="signature-table">
            <tr>
                <td>Penerima</td>
                <td>Orang Tua / Wali</td>
            </tr>
        </table>

        <div class="footer">
            Terima kasih atas pembayaran Anda.
        </div>
    </div>

</body>

</html>