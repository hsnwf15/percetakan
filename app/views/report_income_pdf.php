<!DOCTYPE html>
<html>

<head>
    <style>
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 12px;
        }

        h2 {
            text-align: center;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }

        th,
        td {
            border: 1px solid #000;
            padding: 6px;
        }

        th {
            background: #f0f0f0;
        }
    </style>
</head>

<body>

    <h2>Laporan Pemasukan Bulanan</h2>
    <p>
        Bulan: <?= date('F', mktime(0, 0, 0, $month, 1)) ?> <?= $year ?><br>
        Total Pemasukan: Rp <?= number_format($totalIncome ?? 0, 0, ',', '.') ?>
    </p>
    <?php if (!empty($chartUri)): ?>
        <div style="margin: 16px 0 10px 0;">
            <div style="font-weight: bold; margin-bottom: 6px;">Grafik Pemasukan Harian</div>
            <img src="<?= $chartUri ?>" style="width: 100%; max-width: 800px;">
        </div>
    <?php endif; ?>
    <div style="font-weight: bold; margin-bottom: 6px;">Detail Pemasukan per Order</div>
    <table>
        <thead>
            <tr>
                <th>No</th>
                <th>ID Order</th>
                <th>Pelanggan</th>
                <th>Jumlah</th>
                <th>Tanggal Bayar</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach (($details ?? []) as $i => $d): ?>
                <tr>
                    <td><?= $i + 1 ?></td>
                    <td>#<?= (int)$d['order_id'] ?></td>
                    <td><?= h($d['customer'] ?? '-') ?></td>
                    <td>Rp <?= number_format((float)$d['amount'], 0, ',', '.') ?></td>
                    <td><?= date('d-m-Y', strtotime($d['paid_at'])) ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

</body>

</html>