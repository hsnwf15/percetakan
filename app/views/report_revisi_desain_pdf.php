<?php
// tersedia: $month,$year,$start,$end
// $totalRevisi,$avgRevisiPerOrder,$topOrders,$perDesigner,$maxRev
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

        .small {
            color: #555;
            font-size: 11px;
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

        .muted {
            color: #777;
        }
    </style>
</head>

<body>

    <h1>Laporan Revisi Desain</h1>

    <div class="meta">
        <div><b>Periode:</b> <?= date('d-m-Y', strtotime($start)) ?> s/d <?= date('d-m-Y', strtotime($end . ' -1 day')) ?></div>
        <div class="small">Basis perhitungan revisi: design_revisions.created_at</div>
    </div>

    <div class="card">
        <table style="border:none; width:100%;">
            <tr>
                <td style="border:none; width:50%;">
                    <div class="small">Total Revisi</div>
                    <div style="font-size:20px; font-weight:700;"><?= (int)$totalRevisi ?></div>
                </td>
                <td style="border:none; width:50%;">
                    <div class="small">Rata-rata Revisi per Order</div>
                    <div style="font-size:20px; font-weight:700;"><?= number_format((float)$avgRevisiPerOrder, 2, ',', '.') ?></div>
                    <div class="small muted">Rata-rata untuk order yang memiliki revisi.</div>
                </td>
            </tr>
        </table>
    </div>

    <div class="card">
        <b>Grafik (PDF) — Jumlah Revisi per Desainer</b>
        <div class="small" style="margin:4px 0 8px 0;">Skala relatif terhadap revisi terbanyak.</div>

        <table>
            <thead>
                <tr>
                    <th style="width:26%;">Desainer</th>
                    <th style="width:14%;">Revisi</th>
                    <th>Bar</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($perDesigner)): ?>
                    <tr>
                        <td colspan="3" class="muted" style="text-align:center;">Tidak ada data revisi.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($perDesigner as $d):
                        $v = (int)($d['total_revisi'] ?? 0);
                        $pct = (int)round(($v / $maxRev) * 100);
                        if ($pct < 0) $pct = 0;
                        if ($pct > 100) $pct = 100;
                    ?>
                        <tr>
                            <td><?= htmlspecialchars($d['name']) ?></td>
                            <td><?= $v ?></td>
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
        <b>Order dengan Revisi Terbanyak + Dampak Waktu (Proxy)</b>
        <div class="small" style="margin:4px 0 8px 0;">
            Dampak waktu dihitung sebagai selisih hari dari order dibuat sampai revisi terakhir
            (<i>days_to_last_revision</i>). Ini proxy beban kerja; akan lebih akurat jika nanti ada timestamp “desain final disetujui”.
        </div>

        <table>
            <thead>
                <tr>
                    <th style="width:70px;">Order</th>
                    <th>Pelanggan</th>
                    <th>Produk</th>
                    <th style="width:95px;">Total</th>
                    <th style="width:95px;">Order</th>
                    <th style="width:110px;">Revisi Terakhir</th>
                    <th style="width:90px;">Selisih</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($topOrders)): ?>
                    <tr>
                        <td colspan="7" class="muted" style="text-align:center;">Tidak ada data.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($topOrders as $o): ?>
                        <tr>
                            <td>#<?= (int)$o['order_id'] ?></td>
                            <td><?= htmlspecialchars($o['customer'] ?? '-') ?></td>
                            <td><?= htmlspecialchars($o['product'] ?? '-') ?></td>
                            <td><b><?= (int)$o['total_revisi'] ?></b></td>
                            <td><?= !empty($o['order_created_at']) ? date('d-m-Y', strtotime($o['order_created_at'])) : '-' ?></td>
                            <td><?= !empty($o['last_revision_at']) ? date('d-m-Y', strtotime($o['last_revision_at'])) : '-' ?></td>
                            <td><?= isset($o['days_to_last_revision']) ? ((int)$o['days_to_last_revision'] . ' hari') : '-' ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

</body>

</html>