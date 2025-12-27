<?php
function label_status($st)
{
    $map = [
        'admin' => 'Admin',
        'design' => 'Design',
        'vendor' => 'Vendor',
        'ready' => 'Ready',
        'picked' => 'Picked'
    ];
    return $map[$st] ?? ucfirst($st);
}
?>

<div class="container py-4">
    <div class="d-flex align-items-center justify-content-between mb-3">
        <div>
            <h2 class="mb-0">Laporan Order Bulanan</h2>
            <div class="text-muted">Bulan <?= (int)$month ?> Tahun <?= (int)$year ?> (berdasarkan created_at)</div>
        </div>

        <form method="get" class="d-flex gap-2 align-items-center">
            <input type="hidden" name="r" value="dashboard/orderBulanan">
            <select name="month" class="form-select">
                <?php for ($m = 1; $m <= 12; $m++): ?>
                    <option value="<?= $m ?>" <?= ((int)$month === $m ? 'selected' : '') ?>><?= $m ?></option>
                <?php endfor; ?>
            </select>
            <input type="number" name="year" class="form-control" style="width:120px"
                value="<?= (int)$year ?>">
            <button class="btn btn-primary">Tampilkan</button>
        </form>
        <a class="btn btn-outline-dark"
            href="<?= h(url('dashboard/orderBulananPdf')) ?>&month=<?= (int)$month ?>&year=<?= (int)$year ?>">
            Export PDF
        </a>

    </div>

    <div class="row g-3 mb-4">
        <!-- Jumlah order -->
        <div class="col-md-4">
            <div class="card shadow-sm">
                <div class="card-body">
                    <div class="text-muted">Jumlah Order</div>
                    <div style="font-size:30px; font-weight:700;"><?= (int)$totalOrder ?></div>
                </div>
            </div>
        </div>

        <!-- Rekap status -->
        <div class="col-md-8">
            <div class="card shadow-sm">
                <div class="card-body">
                    <div class="text-muted mb-2">Status Order</div>
                    <div class="d-flex flex-wrap gap-2">
                        <?php foreach ($byStatus as $st => $cnt): ?>
                            <span class="badge bg-secondary">
                                <?= h(label_status($st)) ?>: <?= (int)$cnt ?>
                            </span>
                        <?php endforeach; ?>
                    </div>
                    <div class="text-muted mt-2" style="font-size:12px;">
                        Distribusi dihitung dari order yang dibuat pada bulan tersebut.
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Grafik -->
    <div class="card shadow-sm">
        <div class="card-body">
            <h5 class="mb-3">Grafik Distribusi Status</h5>
            <canvas id="statusChart" height="120"></canvas>
            <div class="card shadow-sm mt-4">
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between mb-3">
                        <h5 class="mb-0">Detail Order Bulan Ini</h5>
                        <div class="text-muted" style="font-size:12px;">
                            Menampilkan <?= count($orders ?? []) ?> order (maks 200)
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-striped align-middle">
                            <thead>
                                <tr>
                                    <th style="width:70px;">#</th>
                                    <th>Tanggal</th>
                                    <th>Pelanggan</th>
                                    <th>Produk</th>
                                    <th style="width:80px;">Qty</th>
                                    <th>Status</th>
                                    <th>Deadline</th>
                                    <th style="width:140px;">Total</th>
                                    <th style="width:110px;"></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($orders)): ?>
                                    <tr>
                                        <td colspan="9" class="text-center text-muted py-4">
                                            Tidak ada order pada bulan ini.
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($orders as $o): ?>
                                        <tr>
                                            <td><strong>#<?= (int)$o['id'] ?></strong></td>
                                            <td><?= !empty($o['created_at']) ? date('d-m-Y', strtotime($o['created_at'])) : '-' ?></td>
                                            <td><?= h($o['customer'] ?? '-') ?></td>
                                            <td><?= h($o['product'] ?? '-') ?></td>
                                            <td><?= (int)($o['quantity'] ?? 0) ?></td>
                                            <td>
                                                <span class="badge bg-<?= status_badge_class($o['status'] ?? '') ?>">
                                                    <?= h(label_status($o['status'] ?? '')) ?>
                                                </span>
                                            </td>
                                            <td><?= !empty($o['deadline']) ? date('d-m-Y', strtotime($o['deadline'])) : '-' ?></td>
                                            <td>Rp <?= number_format((float)($o['total_price'] ?? 0), 0, ',', '.') ?></td>
                                            <td>
                                                <a class="btn btn-sm btn-primary"
                                                    href="<?= h(url('orders/detail')) ?>&id=<?= (int)$o['id'] ?>">
                                                    Detail
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    const labels = <?= json_encode($labels ?? []) ?>;
    const data = <?= json_encode($data ?? []) ?>;

    const ctx = document.getElementById('statusChart').getContext('2d');
    new Chart(ctx, {
        type: 'bar',
        data: {
            labels,
            datasets: [{
                label: 'Jumlah Order',
                data
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: {
                    display: false
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        precision: 0
                    }
                }
            }
        }
    });
</script>