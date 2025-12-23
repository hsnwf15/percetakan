<h4>
Rekap Fee Desainer Minggu ke-<?= h($weekOfMonth) ?> di Bulan <?= h($bulanIndo) ?> Tahun <?= h($year) ?>
</h4>

<table class="table table-bordered">
    <thead>
        <tr>
            <th>Desainer</th>
            <th>Total Order</th>
            <th>Total Fee (Rp)</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($rows as $r): ?>
        <tr>
            <td><?= h($r['designer_name']) ?></td>
            <td><?= h($r['total_order']) ?></td>
            <td><?= number_format($r['total_fee']) ?></td>
        </tr>
        <?php endforeach; ?>
    </tbody>
</table>
