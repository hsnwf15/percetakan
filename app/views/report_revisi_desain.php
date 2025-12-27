<div class="d-flex justify-content-between align-items-start flex-wrap gap-3 mb-3">
    <div>
        <h1 class="mb-1">Laporan Revisi Desain</h1>
        <div class="text-muted">Bulan <?= (int)$month ?> Tahun <?= (int)$year ?> (berdasarkan design_revisions.created_at)</div>
    </div>

    <form class="d-flex gap-2 align-items-center" method="get" action="">
        <input type="hidden" name="r" value="dashboard/revisiDesain">
        <select class="form-select" name="month" style="width: 90px;">
            <?php for ($m = 1; $m <= 12; $m++): ?>
                <option value="<?= $m ?>" <?= ((int)$month === $m ? 'selected' : '') ?>>
                    <?= str_pad((string)$m, 2, '0', STR_PAD_LEFT) ?>
                </option>
            <?php endfor; ?>
        </select>
        <input class="form-control" type="number" name="year" value="<?= (int)$year ?>" style="width:110px;" min="2000" max="2100">
        <button class="btn btn-primary">Tampilkan</button>
    </form>
</div>

<div class="row g-3 mb-3">
    <div class="col-md-6">
        <div class="card p-3">
            <div class="text-muted">Total Revisi</div>
            <div style="font-size:28px; font-weight:700;"><?= (int)$totalRevisi ?></div>
            <div class="text-muted">jumlah seluruh revisi pada periode</div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card p-3">
            <div class="text-muted">Rata-rata Revisi per Order</div>
            <div style="font-size:28px; font-weight:700;"><?= number_format((float)$avgRevisiPerOrder, 2, ',', '.') ?></div>
            <div class="text-muted">rata-rata untuk order yang punya revisi</div>
        </div>
    </div>
</div>

<div class="card p-3 mb-3">
    <h5 class="mb-2">Grafik Jumlah Revisi per Desainer</h5>
    <canvas id="revDesignerChart" height="140"></canvas>
</div>

<div class="card p-3">
    <h5 class="mb-2">Order dengan Revisi Terbanyak</h5>
    <div class="table-responsive">
        <table class="table table-bordered align-middle">
            <thead class="table-light">
                <tr>
                    <th style="width:70px;">Order</th>
                    <th>Pelanggan</th>
                    <th>Produk</th>
                    <th style="width:130px;">Tanggal Order</th>
                    <th style="width:120px;">Total Revisi</th>
                    <th style="width:110px;">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($topOrders)): ?>
                    <tr>
                        <td colspan="6" class="text-muted text-center">Tidak ada data revisi pada periode ini.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($topOrders as $o): ?>
                        <tr>
                            <td>#<?= (int)$o['order_id'] ?></td>
                            <td><?= h($o['customer'] ?? '-') ?></td>
                            <td><?= h($o['product'] ?? '-') ?></td>
                            <td><?= !empty($o['created_at']) ? date('d-m-Y', strtotime($o['created_at'])) : '-' ?></td>
                            <td><b><?= (int)$o['total_revisi'] ?></b></td>
                            <td>
                                <a class="btn btn-sm btn-outline-primary" href="<?= h(url('orders/detail')) ?>&id=<?= (int)$o['order_id'] ?>">
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

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    const labels = <?= json_encode($labels) ?>;
    const data = <?= json_encode($data) ?>;

    new Chart(document.getElementById('revDesignerChart'), {
        type: 'bar',
        data: {
            labels,
            datasets: [{
                label: 'Jumlah Revisi',
                data
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: {
                    display: true
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