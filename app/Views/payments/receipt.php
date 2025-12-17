<!DOCTYPE html>
<html>

<head>
    <title>Kwitansi Pembayaran</title>
    <style>
        @page {
            size: A4;
            margin: 15mm;
        }

        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
            margin: 0;
            padding: 0;
        }

        .kwitansi {
            border: 1px solid #000;
            padding: 20px;
        }

        .header {
            text-align: center;
            font-weight: bold;
            font-size: 16px;
            margin-bottom: 20px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
        }

        td {
            padding: 5px;
        }

        .amount {
            text-align: right;
            font-weight: bold;
        }

        .signature-table {
            width: 100%;
            margin-top: 30px;
        }

        .signature-table td {
            width: 50%;
            text-align: center;
            height: 60px;
            vertical-align: bottom;
            border-top: 1px solid #000;
        }

        .footer {
            text-align: center;
            font-size: 12px;
            margin-top: 10px;
        }
    </style>
</head>

<body>
    <div class="kwitansi">
        <div class="header">KWITANSI PEMBAYARAN</div>

        <table>
            <tr>
                <td><strong>ID Payment:</strong> <?= $payment['id'] ?></td>
                <td style="text-align:right;"><strong>Tanggal:</strong> <?= strftime('%d %B %Y', strtotime($payment['date'])) ?></td>
            </tr>
            <tr>
                <td colspan="2"><strong>Nama Siswa:</strong> <?= $payment['student_name'] ?> (NIS: <?= $payment['nis'] ?>)</td>
            </tr>
            <tr>
                <td colspan="2"><strong>Metode Pembayaran:</strong> <?= $payment['method'] ?? '-' ?></td>
            </tr>
            <tr>
                <td colspan="2"><strong>Referensi:</strong> <?= $payment['reference'] ?? '-' ?></td>
            </tr>
            <tr>
                <td colspan="2" class="amount">Total Pembayaran: Rp <?= number_format($payment['total_amount'], 2, ',', '.') ?></td>
            </tr>
        </table>

        <table class="signature-table">
            <tr>
                <td>Penerima</td>
                <td>Orang Tua / Wali</td>
            </tr>
        </table>

        <div class="footer">Terima kasih atas pembayaran Anda.</div>
    </div>
</body>

</html>