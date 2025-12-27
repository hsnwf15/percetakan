<div class="d-flex justify-content-between align-items-start flex-wrap gap-3 mb-3">
    <div>
        <h1 class="mb-1">Laporan Fee Desainer Bulanan</h1>
        <div class="text-muted">Bulan <?= (int)$month ?> Tahun <?= (int)$year ?> (berdasarkan created_at)</div>
    </div>

    <form class="d-flex gap-2 align-items-center" method="get" action="">
        <input type="hidden" name="r" value="dashboard/feeDesainerBulanan">
        <select class="form-select" name="month" style="width: 90px;">
            <?php for ($m = 1; $m <= 12; $m++): ?>
                <option value="<?= $m ?>" <?= ((int)$month === $m ? 'selected' : '') ?>>
                    <?= str_pad((string)$m, 2, '0', STR_PAD_LEFT) ?>
                </option>
            <?php endfor; ?>
        </select>
        <input class="form-control" type="number" name="year" value="<?= (int)$year ?>"
            style="width:110px;" min="2000" max="2100">
        <button class="btn btn-primary">Tampilkan</button>
    </form>
    <a class="btn btn-outline-secondary"
        href="<?= h(url('dashboard/feeDesainerPdf')) ?>&month=<?= (int)$month ?>&year=<?= (int)$year ?>">
        Export PDF
    </a>
</div>

<div class="row g-3 mb-3">
    <div class="col-md-6">
        <div class="card p-3">
            <div class="text-muted">Total Fee Bulan Ini</div>
            <div style="font-size: 28px; font-weight: 700;">
                Rp <?= number_format($totalFeeMonth, 0, ',', '.') ?>
            </div>
            <div class="text-muted">Total Order (yang ada fee): <?= (int)$totalOrderMonth ?></div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card p-3">
            <div class="text-muted">Periode</div>
            <div><b><?= date('d-m-Y', strtotime($start)) ?></b> s/d <b><?= date('d-m-Y', strtotime($end . ' -1 day')) ?></b></div>
        </div>
    </div>
</div>

<!-- Grafik Batang Fee per Desainer -->
<div class="card p-3 mb-3">
    <h5 class="mb-2">Grafik Batang Fee per Desainer</h5>
    <canvas id="feeBar" height="120"></canvas>
</div>

<!-- Rekap Fee per Desainer -->
<div class="card p-3 mb-3">
    <h5 class="mb-2">Rekap Fee per Desainer</h5>

    <div class="table-responsive">
        <table class="table table-bordered align-middle">
            <thead class="table-light">
                <tr>
                    <th style="width:60px;">#</th>
                    <th>Desainer</th>
                    <th style="width:140px;">Total Order</th>
                    <th style="width:180px;">Total Fee</th>
                    <th style="width:160px;">Rata-rata Fee</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($rows)): ?>
                    <tr>
                        <td colspan="5" class="text-muted text-center">Tidak ada data fee di bulan ini.</td>
                    </tr>
                <?php else: ?>
                    <?php $no = 1;
                    foreach ($rows as $r): ?>
                        <tr>
                            <td><?= $no++ ?></td>
                            <td><?= h($r['name']) ?></td>
                            <td><?= (int)$r['total_order'] ?></td>
                            <td>Rp <?= number_format((float)$r['total_fee'], 0, ',', '.') ?></td>
                            <td>Rp <?= number_format((float)$r['avg_fee'], 0, ',', '.') ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Rekap Mingguan -->
<div class="card p-3">
    <h5 class="mb-2">Rekap Mingguan (dalam bulan ini)</h5>

    <div class="table-responsive">
        <table class="table table-bordered align-middle">
            <thead class="table-light">
                <tr>
                    <th style="width:120px;">Minggu ke-</th>
                    <th style="width:160px;">Total Order</th>
                    <th style="width:200px;">Total Fee</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($weekly)): ?>
                    <tr>
                        <td colspan="3" class="text-muted text-center">Belum ada rekap mingguan.</td>
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
</div>

<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    const feeLabels = <?= json_encode($labels) ?>;
    const feeData = <?= json_encode($dataFee) ?>;

    const ctx = document.getElementById('feeBar');
    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: feeLabels,
            datasets: [{
                label: 'Total Fee (Rp)',
                data: feeData
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
                        callback: (v) => 'Rp ' + new Intl.NumberFormat('id-ID').format(v)
                    }
                }
            }
        }
    });
</script>