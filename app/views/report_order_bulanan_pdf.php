<?php
// Variabel yang tersedia dari controller:
// $month, $year, $monthName, $totalOrder, $byStatus, $orders, $chartBase64
?>
<!doctype html>
<html>

<head>
    <meta charset="utf-8">
    <style>
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 12px;
        }

        h1 {
            text-align: center;
            margin: 0 0 6px 0;
        }

        .meta {
            margin: 8px 0 12px 0;
        }

        .box {
            border: 1px solid #333;
            padding: 10px;
            margin-bottom: 12px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }

        th,
        td {
            border: 1px solid #333;
            padding: 6px;
        }

        th {
            background: #eee;
        }

        .right {
            text-align: right;
        }

        .muted {
            color: #666;
        }
    </style>
</head>

<body>

    <h1>Laporan Order Bulanan</h1>
    <div class="meta">
        Bulan: <b><?= htmlspecialchars($monthName) ?> <?= (int)$year ?></b><br>
        Total Order: <b><?= (int)$totalOrder ?></b>
    </div>

    <div class="box">
        <b>Distribusi Status</b><br>
        Admin: <?= (int)($byStatus['admin'] ?? 0) ?> |
        Design: <?= (int)($byStatus['design'] ?? 0) ?> |
        Vendor: <?= (int)($byStatus['vendor'] ?? 0) ?> |
        Ready: <?= (int)($byStatus['ready'] ?? 0) ?> |
        Picked: <?= (int)($byStatus['picked'] ?? 0) ?>
    </div>

    <div class="box">
        <b>Grafik Distribusi Status</b><br><br>
        <img style="width:100%; height:auto;"
            src="data:image/png;base64,<?= $chartBase64 ?>" />
    </div>

    <b>Detail Order</b>
    <table>
        <thead>
            <tr>
                <th style="width:40px;">No</th>
                <th style="width:70px;">ID</th>
                <th>Tanggal</th>
                <th>Pelanggan</th>
                <th>Produk</th>
                <th style="width:40px;">Qty</th>
                <th style="width:60px;">Status</th>
                <th>Deadline</th>
                <th style="width:90px;">Total</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($orders)): ?>
                <tr>
                    <td colspan="9" class="muted" style="text-align:center;">Tidak ada order pada bulan ini.</td>
                </tr>
            <?php else: ?>
                <?php $no = 1;
                $grandTotal = 0;
                foreach ($orders as $o):
                    $grandTotal += (float)($o['total_price'] ?? 0);
                ?>
                    <tr>
                        <td><?= $no++ ?></td>
                        <td>#<?= (int)$o['id'] ?></td>
                        <td><?= !empty($o['created_at']) ? date('d-m-Y', strtotime($o['created_at'])) : '-' ?></td>
                        <td><?= htmlspecialchars($o['customer'] ?? '-') ?></td>
                        <td><?= htmlspecialchars($o['product'] ?? '-') ?></td>
                        <td class="right"><?= (int)($o['quantity'] ?? 0) ?></td>
                        <td><?= htmlspecialchars($o['status'] ?? '-') ?></td>
                        <td><?= !empty($o['deadline']) ? date('d-m-Y', strtotime($o['deadline'])) : '-' ?></td>
                        <td class="right">Rp <?= number_format((float)($o['total_price'] ?? 0), 0, ',', '.') ?></td>
                    </tr>
                <?php endforeach; ?>
                <tr style="background: #eee; font-weight: bold;">
                    <td colspan="8" style="text-align:right;">TOTAL</td>
                    <td class="right">Rp <?= number_format($grandTotal, 0, ',', '.') ?></td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>

</body>

</html>