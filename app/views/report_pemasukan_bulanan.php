<?php
$month = (int)($_GET['month'] ?? date('m'));
$year  = (int)($_GET['year'] ?? date('Y'));
?>

<!DOCTYPE html>
<html>

<head>
    <title>Laporan Pemasukan Bulanan</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body>

    <div class="container mt-4">

        <div class="d-flex justify-content-between align-items-start flex-wrap gap-3 mb-3">
            <div>
                <h1 class="mb-1">Laporan Pemasukan Bulanan</h1>
                <div class="text-muted">
                    Bulan <?= (int)$month ?> Tahun <?= (int)$year ?>
                </div>
            </div>

            <form class="d-flex gap-2 align-items-center"
                method="get" action="">
                <input type="hidden" name="r" value="dashboard/pemasukanBulanan">

                <select class="form-select" name="month" style="width: 90px;">
                    <?php for ($m = 1; $m <= 12; $m++): ?>
                        <option value="<?= $m ?>" <?= ((int)$month === $m ? 'selected' : '') ?>>
                            <?= str_pad((string)$m, 2, '0', STR_PAD_LEFT) ?>
                        </option>
                    <?php endfor; ?>
                </select>

                <input class="form-control" type="number" name="year"
                    value="<?= (int)$year ?>" style="width: 110px;"
                    min="2000" max="2100">

                <button class="btn btn-primary">Tampilkan</button>
            </form>
            <a href="<?= url("report/incomePdf&month=$month&year=$year") ?>"
                class="btn btn-outline-dark">
                Export PDF
            </a>
        </div>

        <!-- SUMMARY -->
        <div class="row mb-4">
            <div class="col-md-6">
                <div class="card shadow-sm">
                    <div class="card-body">
                        <h6 class="text-muted">Total Pemasukan</h6>
                        <h3 class="text-success">
                            Rp <?= number_format($totalIncome ?? 0, 0, ',', '.') ?>
                        </h3>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card shadow-sm">
                    <div class="card-body">
                        <h6 class="text-muted">Jumlah Transaksi</h6>
                        <h3><?= count($details ?? []) ?></h3>
                    </div>
                </div>
            </div>
        </div>

        <!-- GRAFIK -->
        <div class="card mb-4 shadow-sm">
            <div class="card-body">
                <h6 class="mb-3">Grafik Pemasukan Harian</h6>
                <canvas id="incomeChart" height="100"></canvas>
            </div>
        </div>

        <!-- TABEL DETAIL -->
        <div class="card shadow-sm">
            <div class="card-body">
                <h6 class="mb-3">Detail Pemasukan per Order</h6>

                <table class="table table-sm table-bordered">
                    <thead class="table-light">
                        <tr>
                            <th>Tanggal</th>
                            <th>Order</th>
                            <th>Pelanggan</th>
                            <th>Total Order</th>
                            <th>Dibayar</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($details as $d): ?>
                            <tr>
                                <td><?= date('d-m-Y', strtotime($d['paid_at'])) ?></td>
                                <td>#<?= $d['order_id'] ?></td>
                                <td><?= h($d['customer'] ?? '-') ?></td>
                                <td>Rp <?= number_format($d['total_price'], 0, ',', '.') ?></td>
                                <td>Rp <?= number_format($d['amount'], 0, ',', '.') ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>

            </div>
        </div>

    </div>

    <script>
        new Chart(document.getElementById('incomeChart'), {
            type: 'bar',
            data: {
                labels: <?= json_encode($labels) ?>,
                datasets: [{
                    label: 'Pemasukan (Rp)',
                    data: <?= json_encode($data) ?>,
                    backgroundColor: '#3C6E71'
                }]
            },
            options: {
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: value => 'Rp ' + value.toLocaleString('id-ID')
                        }
                    }
                }
            }
        });
    </script>

</body>

</html>