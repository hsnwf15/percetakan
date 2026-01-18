<?php /* variabel tersedia: $order,$payments,$totalPaid,$totalPrice,$outstanding,$store,$invoiceNo,$qrSrc */ ?>
<!doctype html>
<html>

<head>
    <meta charset="utf-8">
    <title><?= h($invoiceNo) ?></title>
    <style>
        * {
            font-family: DejaVu Sans, Arial, Helvetica, sans-serif;
            font-size: 10px;
        }

        h1,
        h2,
        h3 {
            margin: 0;
        }

        .wrap {
            width: 100%;
        }

        .row {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
        }

        .small {
            color: #666;
            font-size: 11px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 8px;
        }

        th,
        td {
            border: 1px solid #ddd;
            padding: 6px;
        }

        th {
            background: #f4f4f4;
            text-align: left;
        }

        .right {
            text-align: right;
        }

        .mb8 {
            margin-bottom: 8px;
        }

        .mt8 {
            margin-top: 8px;
        }

        .box {
            border: 1px solid #ddd;
            padding: 8px;
            border-radius: 6px;
        }

        .total {
            font-weight: bold;
        }

        .hr {
            height: 1px;
            background: #eee;
            margin: 8px 0;
        }
    </style>
</head>

<body>
    <div class="wrap">
        <div class="row">
            <div style="display: flex; gap: 15px; width: 60%;">
                <?php
                $logoPath = $store['logo'] ?? 'assets/images/logo.png';
                // Convert relative path to absolute file system path for DOMPDF compatibility
                if (!filter_var($logoPath, FILTER_VALIDATE_URL) && !file_exists($logoPath)) {
                    $logoPath = dirname(dirname(dirname(__FILE__))) . '/public/' . $logoPath;
                }
                // Convert image to base64 for DOMPDF PDF rendering
                if (!empty($logoPath) && file_exists($logoPath)) {
                    $imageData = base64_encode(file_get_contents($logoPath));
                    $mimeType = mime_content_type($logoPath);
                    $imageSrc = 'data:' . $mimeType . ';base64,' . $imageData;
                } else {
                    $imageSrc = null;
                }
                ?>
                <?php if (!empty($imageSrc)): ?>
                    <img src="<?= h($imageSrc) ?>" alt="Logo" style="width: 80px; height: auto;">
                <?php endif; ?>
                <div>
                    <h2><?= h($store['name']) ?></h2>
                    <div class="small"><?= h($store['address']) ?></div>
                    <div class="small">Telp: <?= h($store['phone']) ?></div>
                    <div class="small">Email: <?= h($store['email']) ?></div>
                </div>
            </div>
            <div style="text-align:right">
                <h3>NOTA PEMBAYARAN</h3>
                <div class="small">No: <?= h($invoiceNo) ?></div>
                <div class="small">Tanggal: <?= date('d/m/Y H:i') ?></div>
            </div>
        </div>

        <div class="hr"></div>

        <div class="row">
            <div class="box" style="width:58%">
                <b>Pelanggan</b><br>
                <?= h($order['customer_name'] ?? '-') ?><br>
                <span class="small">
                    <?= h($order['customer_phone'] ?? '-') ?>
                </span>
            </div>
            <br>
            <div class="box" style="width:40%">
                <b>Info Order</b><br>
                ID Order: #<?= h($order['id']) ?><br>
                Produk: <?= h($order['product']) ?> (Qty: <?= h($order['quantity']) ?>)<br>
                Channel: <?= h($order['channel']) ?><br>
                Deadline: <?= h($order['deadline']) ?>
            </div>
        </div>

        <?php if (!empty($order['spec'])): ?>
            <div class="mt8"><b>Spesifikasi</b><br><?= nl2br(h($order['spec'])) ?></div>
        <?php endif; ?>

        <h4 class="mt8">Pembayaran</h4>
        <table>
            <thead>
                <tr>
                    <th style="width:28%">Tanggal</th>
                    <th style="width:35%">Metode/Jenis</th>
                    <th class="right">Nominal</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!$payments): ?>
                    <tr>
                        <td colspan="3" class="small">Belum ada pembayaran.</td>
                    </tr>
                    <?php else: foreach ($payments as $p): ?>
                        <tr>
                            <td><?= h($p['paid_at']) ?></td>
                            <td><?= h($p['method']) ?></td>
                            <td class="right"><?= idr($p['amount']) ?></td>
                        </tr>
                <?php endforeach;
                endif; ?>
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="2" class="total right">Total Dibayar</td>
                    <td class="right total"><?= idr($totalPaid) ?></td>
                </tr>
                <?php if ($totalPrice > 0): ?>
                    <tr>
                        <td colspan="2" class="right">Total Tagihan</td>
                        <td class="right"><?= idr($totalPrice) ?></td>
                    </tr>
                    <tr>
                        <td colspan="2" class="right">Sisa Bayar</td>
                        <td class="right"><?= idr($outstanding) ?></td>
                    </tr>
                <?php endif; ?>
            </tfoot>
        </table>

        <div class="row mt8">
            <div class="small">Desainer: <?= h($order['designer_name'] ?? '-') ?></div>
            <div style="text-align:right">
                <!-- QR verifikasi (opsional) -->
                <div class="small">Verifikasi nota:</div>
                <img src="<?= h($qrSrc) ?>" alt="QR" width="90" height="90">
            </div>
        </div>

        <div class="small mt8">
            *Nota dicetak otomatis dari sistem. Terima kasih telah berbelanja di <?= h($store['name']) ?>.
        </div>
    </div>
</body>

</html>