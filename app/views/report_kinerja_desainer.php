<?php
// Variabel dari controller:
// $month,$year,$start,$end
// $rows
// $labels,$orderCounts,$avgFees
// $totalOrder,$totalDesignerAktif,$avgOrderPerDesigner,$totalFee
?>

<div class="d-flex justify-content-between align-items-start flex-wrap gap-3 mb-3">
    <div>
        <h1 class="mb-1">Laporan Kinerja Desainer</h1>
        <div class="text-muted">
            Bulan <?= (int)$month ?> Tahun <?= (int)$year ?> (berdasarkan created_at)
        </div>
    </div>

    <form class="d-flex gap-2 align-items-center" method="get" action="">
        <input type="hidden" name="r" value="dashboard/kinerjaDesainer">

        <select class="form-select" name="month" style="width: 90px;">
            <?php for ($m = 1; $m <= 12; $m++): ?>
                <option value="<?= $m ?>" <?= ((int)$month === $m ? 'selected' : '') ?>>
                    <?= str_pad((string)$m, 2, '0', STR_PAD_LEFT) ?>
                </option>
            <?php endfor; ?>
        </select>

        <input class="form-control" type="number" name="year"
            value="<?= (int)$year ?>" style="width:110px;"
            min="2000" max="2100">

        <button class="btn btn-primary">Tampilkan</button>
    </form>
    <a class="btn btn-outline-secondary"
        href="<?= h(url('dashboard/kinerjaDesainerPdf')) ?>&month=<?= (int)$month ?>&year=<?= (int)$year ?>">
        Export PDF
    </a>
</div>

<div class="row g-3 mb-3">
    <div class="col-md-3">
        <div class="card p-3">
            <div class="text-muted">Desainer Aktif</div>
            <div style="font-size: 26px; font-weight: 700;"><?= (int)$totalDesignerAktif ?></div>
            <div class="text-muted">yang punya order di periode ini</div>
        </div>
    </div>

    <div class="col-md-3">
        <div class="card p-3">
            <div class="text-muted">Total Order</div>
            <div style="font-size: 26px; font-weight: 700;"><?= (int)$totalOrder ?></div>
            <div class="text-muted">assigned ke desainer</div>
        </div>
    </div>

    <div class="col-md-3">
        <div class="card p-3">
            <div class="text-muted">Rata-rata Order/Desainer</div>
            <div style="font-size: 26px; font-weight: 700;">
                <?= number_format((float)$avgOrderPerDesigner, 1, ',', '.') ?>
            </div>
            <div class="text-muted">dibagi desainer aktif</div>
        </div>
    </div>

    <div class="col-md-3">
        <div class="card p-3">
            <div class="text-muted">Total Fee (Konteks)</div>
            <div style="font-size: 26px; font-weight: 700;">
                Rp <?= number_format((float)$totalFee, 0, ',', '.') ?>
            </div>
            <div class="text-muted">bukan fokus utama, hanya konteks</div>
        </div>
    </div>
</div>

<div class="row g-3 mb-3">
    <div class="col-md-6">
        <div class="card p-3">
            <h5 class="mb-2">Jumlah Order per Desainer</h5>
            <canvas id="chartOrder" height="140"></canvas>
        </div>
    </div>

    <div class="col-md-6">
        <div class="card p-3">
            <h5 class="mb-2">Rata-rata Fee per Order</h5>
            <canvas id="chartAvgFee" height="140"></canvas>
        </div>
    </div>
</div>

<div class="card p-3">
    <h5 class="mb-2">Perbandingan Antar Desainer</h5>

    <div class="table-responsive">
        <table class="table table-bordered align-middle">
            <thead class="table-light">
                <tr>
                    <th style="width:60px;">#</th>
                    <th>Desainer</th>
                    <th style="width:140px;">Total Order</th>
                    <th style="width:180px;">Total Fee</th>
                    <th style="width:160px;">Rata-rata Fee</th>
                    <th style="width:140px;">Kontribusi Order</th>
                </tr>
            </thead>

            <tbody>
                <?php if (empty($rows)): ?>
                    <tr>
                        <td colspan="6" class="text-muted text-center">Tidak ada data pada periode ini.</td>
                    </tr>
                <?php else: ?>
                    <?php
                    $no = 1;
                    $den = max(1, (int)$totalOrder);
                    ?>
                    <?php foreach ($rows as $r): ?>
                        <?php
                        $order = (int)($r['total_order'] ?? 0);
                        $pct = ($order / $den) * 100;
                        ?>
                        <tr>
                            <td><?= $no++ ?></td>
                            <td><?= h($r['name']) ?></td>
                            <td><?= $order ?></td>
                            <td>Rp <?= number_format((float)($r['total_fee'] ?? 0), 0, ',', '.') ?></td>
                            <td>Rp <?= number_format((float)($r['avg_fee'] ?? 0), 0, ',', '.') ?></td>
                            <td><?= number_format($pct, 1, ',', '.') ?>%</td>
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

    const orderCounts = <?= json_encode($orderCounts) ?>;
    const avgFees = <?= json_encode($avgFees) ?>;

    // Chart 1: Order per desainer
    new Chart(document.getElementById('chartOrder'), {
        type: 'bar',
        data: {
            labels,
            datasets: [{
                label: 'Jumlah Order',
                data: orderCounts
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

    // Chart 2: Rata-rata fee
    new Chart(document.getElementById('chartAvgFee'), {
        type: 'bar',
        data: {
            labels,
            datasets: [{
                label: 'Rata-rata Fee (Rp)',
                data: avgFees
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