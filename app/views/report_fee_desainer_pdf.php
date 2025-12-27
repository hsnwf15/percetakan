<?php
// Variabel tersedia dari controller:
// $month, $year, $start, $end, $rows, $weekly, $totalFeeMonth, $totalOrderMonth, $maxFee
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
            margin: 0 0 8px 0;
            font-size: 18px;
        }

        .meta {
            margin-bottom: 10px;
        }

        .card {
            border: 1px solid #333;
            padding: 10px;
            margin-bottom: 10px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th,
        td {
            border: 1px solid #333;
            padding: 6px;
        }

        th {
            background: #eee;
        }

        .barwrap {
            width: 100%;
            height: 14px;
            border: 1px solid #333;
        }

        .bar {
            height: 14px;
            background: #3C6E71;
        }

        .small {
            color: #555;
            font-size: 11px;
        }
    </style>
</head>

<body>

    <h1>Laporan Fee Desainer Bulanan</h1>

    <div class="meta">
        <div><b>Bulan:</b> <?= htmlspecialchars(date('F Y', strtotime($start))) ?></div>
        <div><b>Periode:</b> <?= date('d-m-Y', strtotime($start)) ?> s/d <?= date('d-m-Y', strtotime($end . ' -1 day')) ?></div>
    </div>

    <div class="card">
        <div><b>Total Fee Bulan Ini:</b> Rp <?= number_format((float)$totalFeeMonth, 0, ',', '.') ?></div>
        <div><b>Total Order (yang ada fee):</b> <?= (int)$totalOrderMonth ?></div>
        <div class="small">Sumber data berdasarkan orders.created_at dan orders.designer_fee.</div>
    </div>

    <div class="card">
        <b>Grafik Batang Fee per Desainer (versi PDF)</b>
        <div class="small" style="margin:4px 0 8px 0;">Skala relatif terhadap fee terbesar pada bulan tersebut.</div>

        <table>
            <thead>
                <tr>
                    <th style="width: 22%;">Desainer</th>
                    <th style="width: 18%;">Total Fee</th>
                    <th>Bar</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($rows)): ?>
                    <tr>
                        <td colspan="3" style="text-align:center; color:#777;">Tidak ada data fee di bulan ini.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($rows as $r):
                        $fee = (int)($r['total_fee'] ?? 0);
                        $pct = (int)round(($fee / $maxFee) * 100);
                        if ($pct < 0) $pct = 0;
                        if ($pct > 100) $pct = 100;
                    ?>
                        <tr>
                            <td><?= htmlspecialchars($r['name']) ?></td>
                            <td>Rp <?= number_format($fee, 0, ',', '.') ?></td>
                            <td>
                                <div class="barwrap">
                                    <div class="bar" style="width: <?= $pct ?>%;"></div>
                                </div>
                                <div class="small"><?= $pct ?>%</div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <div class="card">
        <b>Rekap Fee per Desainer</b>
        <table style="margin-top:8px;">
            <thead>
                <tr>
                    <th style="width:40px;">#</th>
                    <th>Desainer</th>
                    <th style="width:90px;">Total Order</th>
                    <th style="width:120px;">Total Fee</th>
                    <th style="width:120px;">Rata-rata</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($rows)): ?>
                    <tr>
                        <td colspan="5" style="text-align:center; color:#777;">Tidak ada data.</td>
                    </tr>
                <?php else: ?>
                    <?php $no = 1;
                    foreach ($rows as $r): ?>
                        <tr>
                            <td><?= $no++ ?></td>
                            <td><?= htmlspecialchars($r['name']) ?></td>
                            <td><?= (int)$r['total_order'] ?></td>
                            <td>Rp <?= number_format((float)$r['total_fee'], 0, ',', '.') ?></td>
                            <td>Rp <?= number_format((float)$r['avg_fee'], 0, ',', '.') ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <div class="card">
        <b>Rekap Mingguan (dalam bulan ini)</b>
        <table style="margin-top:8px;">
            <thead>
                <tr>
                    <th style="width:90px;">Minggu ke-</th>
                    <th style="width:120px;">Total Order</th>
                    <th style="width:140px;">Total Fee</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($weekly)): ?>
                    <tr>
                        <td colspan="3" style="text-align:center; color:#777;">Tidak ada data mingguan.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($weekly as $w): ?>
                        <tr>
                            <td><?= (int)$w['week_in_month'] ?></td>
                            <td><?= (int)$w['total_order'] ?></td>
                            <td>Rp <?= number_format((float)$w['total_fee'], 0, ',', '.') ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

</body>

</html>