<div class="d-flex justify-content-between align-items-center mb-3">
    <h3>Dashboard</h3>
    <a class="btn btn-sm btn-outline-secondary" href="<?= h(
                                                            url("orders/index"),
                                                        ) ?>">Ke Daftar Order</a>
</div>

<div class="row g-3">
    <div class="col-sm-6 col-lg-3">
        <div class="card shadow-sm">
            <div class="card-body">
                <div class="text-muted small">Order Aktif</div>
                <div class="h4 mb-0"><?= h($active) ?></div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-lg-3">
        <div class="card shadow-sm">
            <div class="card-body">
                <div class="text-muted small">Siap Diambil</div>
                <div class="h4 mb-0"><?= h($ready) ?></div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-lg-3">
        <div class="card shadow-sm">
            <div class="card-body">
                <div class="text-muted small">Selesai (Picked)</div>
                <div class="h4 mb-0"><?= h($picked) ?></div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-lg-3">
        <div class="card shadow-sm">
            <div class="card-body">
                <div class="text-muted small">Omzet 7 Hari</div>
                <div class="h4 mb-0">Rp <?= number_format($rev7, 0, ",", ".") ?></div>
            </div>
        </div>
    </div>
</div>

<div class="row g-3 mt-1">
    <div class="col-lg-3">
        <div class="card shadow-sm h-100">
            <div class="card-body">
                <div class="text-muted small">Piutang (Estimasi)</div>
                <div class="h4 mb-1">Rp <?= number_format(
                                            $outstanding,
                                            0,
                                            ",",
                                            ".",
                                        ) ?></div>
                <div class="small text-muted">Total tagihan: Rp <?= number_format(
                                                                    $totalInvoice,
                                                                    0,
                                                                    ",",
                                                                    ".",
                                                                ) ?><br>
                    Terbayar: Rp <?= number_format($totalPaidAll, 0, ",", ".") ?></div>
            </div>
        </div>
    </div>
    <div class="col-lg-9">
        <div class="card shadow-sm">
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <div class="text-muted small">Order / Hari (7 hari)</div>
                        <canvas id="chartOrders" height="130"></canvas>
                    </div>
                    <div class="col-md-6">
                        <div class="text-muted small">Pemasukan / Hari (7 hari)</div>
                        <canvas id="chartRevenue" height="130"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row g-3 mt-1">
    <div class="col-lg-6">
        <div class="card shadow-sm">
            <div class="card-header">Deadline Terdekat</div>
            <div class="card-body p-0">
                <table class="table table-sm mb-0">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Pelanggan</th>
                            <th>Produk</th>
                            <th>Qty</th>
                            <th>Deadline</th>
                            <th>Status</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!$near): ?>
                            <tr>
                                <td colspan="7" class="text-muted small p-3">Tidak ada.</td>
                            </tr>
                            <?php else: foreach ($near as $r): ?>
                                <tr>
                                    <td><?= h($r["id"]) ?></td>
                                    <td><?= h($r["customer"] ?? "-") ?></td>
                                    <td><?= h($r["product"]) ?></td>
                                    <td><?= h($r["quantity"]) ?></td>
                                    <td><?= h(date("d-m-Y H:i", strtotime($r["deadline"]))) ?></td>
                                    <td><span class="badge bg-<?= status_badge_class(
                                                                    $r["status"],
                                                                ) ?>"><?= h($r["status"]) ?></span></td>
                                    <td><a class="btn btn-sm btn-primary" href="<?= h(
                                                                                    url("orders/detail"),
                                                                                ) ?>&id=<?= h($r["id"]) ?>">Detail</a></td>
                                </tr>
                        <?php endforeach;
                        endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="col-lg-6">
        <div class="card shadow-sm">
            <div class="card-header">Pembayaran Terakhir</div>
            <div class="card-body p-0">
                <table class="table table-sm mb-0">
                    <thead>
                        <tr>
                            <th>Tanggal</th>
                            <th>Pelanggan</th>
                            <th>Produk</th>
                            <th class="text-end">Nominal</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!$recentPay): ?>
                            <tr>
                                <td colspan="4" class="text-muted small p-3">Belum ada pembayaran.</td>
                            </tr>
                            <?php else: foreach ($recentPay as $p): ?>
                                <tr>
                                    <td><?= h(date("d-m-Y H:i", strtotime($p["paid_at"]))) ?></td>
                                    <td><?= h($p["customer"] ?? "-") ?></td>
                                    <td><?= h($p["product"] ?? "-") ?></td>
                                    <td class="text-end">Rp <?= number_format(
                                                                $p["amount"],
                                                                0,
                                                                ",",
                                                                ".",
                                                            ) ?></td>
                                </tr>
                        <?php endforeach;
                        endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Chart.js CDN -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    const ordersLabels = <?= json_encode($ordersDaily["labels"]) ?>;
    const ordersData = <?= json_encode($ordersDaily["data"]) ?>;
    const revLabels = <?= json_encode($revenueDaily["labels"]) ?>;
    const revData = <?= json_encode($revenueDaily["data"]) ?>;

    new Chart(document.getElementById('chartOrders'), {
        type: 'line',
        data: {
            labels: ordersLabels,
            datasets: [{
                label: 'Order',
                data: ordersData,
                tension: .3
            }]
        },
        options: {
            plugins: {
                legend: {
                    display: false
                }
            },
            scales: {
                y: {
                    beginAtZero: true
                }
            }
        }
    });

    new Chart(document.getElementById('chartRevenue'), {
        type: 'line',
        data: {
            labels: revLabels,
            datasets: [{
                label: 'Rp',
                data: revData,
                tension: .3
            }]
        },
        options: {
            plugins: {
                legend: {
                    display: false
                }
            },
            scales: {
                y: {
                    beginAtZero: true
                }
            }
        }
    });
</script>