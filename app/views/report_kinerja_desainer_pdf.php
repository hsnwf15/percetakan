<?php
// tersedia: $month,$year,$start,$end,$rows
// $totalOrder,$totalDesignerAktif,$avgOrderPerDesigner,$totalFee
// $maxOrder,$maxAvgFee
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

        .grid {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 10px;
        }

        .grid td {
            border: 1px solid #333;
            padding: 8px;
            vertical-align: top;
        }

        .cardTitle {
            color: #555;
            font-size: 11px;
        }

        .cardValue {
            font-size: 18px;
            font-weight: 700;
            margin-top: 2px;
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

    <h1>Laporan Kinerja Desainer</h1>

    <div class="meta">
        <div><b>Periode:</b> <?= date('d-m-Y', strtotime($start)) ?> s/d <?= date('d-m-Y', strtotime($end . ' -1 day')) ?></div>
        <div class="small">Basis perhitungan: orders.created_at</div>
    </div>

    <!-- Cards -->
    <table class="grid">
        <tr>
            <td>
                <div class="cardTitle">Desainer Aktif</div>
                <div class="cardValue"><?= (int)$totalDesignerAktif ?></div>
                <div class="small">punya order di periode ini</div>
            </td>
            <td>
                <div class="cardTitle">Total Order</div>
                <div class="cardValue"><?= (int)$totalOrder ?></div>
                <div class="small">assigned ke desainer</div>
            </td>
            <td>
                <div class="cardTitle">Rata-rata Order/Desainer</div>
                <div class="cardValue"><?= number_format((float)$avgOrderPerDesigner, 1, ',', '.') ?></div>
                <div class="small">dibagi desainer aktif</div>
            </td>
            <td>
                <div class="cardTitle">Total Fee (konteks)</div>
                <div class="cardValue">Rp <?= number_format((float)$totalFee, 0, ',', '.') ?></div>
                <div class="small">bukan fokus utama</div>
            </td>
        </tr>
    </table>

    <!-- Grafik PDF: Order per desainer -->
    <div style="border:1px solid #333; padding:10px; margin-bottom:10px;">
        <b>Grafik (PDF) — Jumlah Order per Desainer</b>
        <div class="small" style="margin:4px 0 8px 0;">Skala relatif terhadap jumlah order terbesar.</div>

        <table>
            <thead>
                <tr>
                    <th style="width:22%;">Desainer</th>
                    <th style="width:15%;">Order</th>
                    <th>Bar</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($rows)): ?>
                    <tr>
                        <td colspan="3" style="text-align:center; color:#777;">Tidak ada data.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($rows as $r):
                        $o = (int)($r['total_order'] ?? 0);
                        $pct = (int)round(($o / $maxOrder) * 100);
                        if ($pct < 0) $pct = 0;
                        if ($pct > 100) $pct = 100;
                    ?>
                        <tr>
                            <td><?= htmlspecialchars($r['name']) ?></td>
                            <td><?= $o ?></td>
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

    <!-- Grafik PDF: Rata-rata fee -->
    <div style="border:1px solid #333; padding:10px; margin-bottom:10px;">
        <b>Grafik (PDF) — Rata-rata Fee per Order</b>
        <div class="small" style="margin:4px 0 8px 0;">Skala relatif terhadap rata-rata fee terbesar.</div>

        <table>
            <thead>
                <tr>
                    <th style="width:22%;">Desainer</th>
                    <th style="width:20%;">Avg Fee</th>
                    <th>Bar</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($rows)): ?>
                    <tr>
                        <td colspan="3" style="text-align:center; color:#777;">Tidak ada data.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($rows as $r):
                        $avg = (int)round((float)($r['avg_fee'] ?? 0));
                        $pct = (int)round(($avg / $maxAvgFee) * 100);
                        if ($pct < 0) $pct = 0;
                        if ($pct > 100) $pct = 100;
                    ?>
                        <tr>
                            <td><?= htmlspecialchars($r['name']) ?></td>
                            <td>Rp <?= number_format($avg, 0, ',', '.') ?></td>
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

    <!-- Tabel perbandingan -->
    <div style="border:1px solid #333; padding:10px;">
        <b>Perbandingan Antar Desainer</b>

        <table style="margin-top:8px;">
            <thead>
                <tr>
                    <th style="width:40px;">#</th>
                    <th>Desainer</th>
                    <th style="width:90px;">Total Order</th>
                    <th style="width:120px;">Total Fee</th>
                    <th style="width:120px;">Rata-rata Fee</th>
                    <th style="width:90px;">Kontribusi</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($rows)): ?>
                    <tr>
                        <td colspan="6" style="text-align:center; color:#777;">Tidak ada data.</td>
                    </tr>
                <?php else: ?>
                    <?php $no = 1;
                    $den = max(1, (int)$totalOrder); ?>
                    <?php foreach ($rows as $r):
                        $o = (int)($r['total_order'] ?? 0);
                        $pct = ($o / $den) * 100;
                    ?>
                        <tr>
                            <td><?= $no++ ?></td>
                            <td><?= htmlspecialchars($r['name']) ?></td>
                            <td><?= $o ?></td>
                            <td>Rp <?= number_format((float)($r['total_fee'] ?? 0), 0, ',', '.') ?></td>
                            <td>Rp <?= number_format((float)($r['avg_fee'] ?? 0), 0, ',', '.') ?></td>
                            <td><?= number_format($pct, 1, ',', '.') ?>%</td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

</body>

</html>