<div class="d-flex justify-content-between align-items-center mb-3">
    <h3>Daftar Order</h3>
    <a href="<?= h(
                    url("orders/create"),
                ) ?>" class="btn btn-success">+ Order Baru</a>
</div>

<?php
$me    = current_user();
$role  = $me['role'] ?? '';
$scope = $scope ?? 'all';
?>

<?php if ($role === 'designer'): ?>
    <form method="get" class="row g-2 mb-3 align-items-center">
        <input type="hidden" name="r" value="orders/index">

        <!-- Filter scope -->
        <div class="col-auto">
            <select name="scope" class="form-select" onchange="this.form.submit()">
                <option value="mine" <?= $scope === 'mine' ? 'selected' : '' ?>>
                    Order tugas saya
                </option>
                <option value="all" <?= $scope === 'all' ? 'selected' : '' ?>>
                    Semua order
                </option>
            </select>
        </div>

        <!-- Kalau kamu sudah punya filter status, search, dll
         tambahkan input2 itu di form yang sama supaya ikut kebawa -->
        <?php if (isset($status)): ?>
            <input type="hidden" name="status" value="<?= h($status) ?>">
        <?php endif; ?>
    </form>
<?php endif; ?>


<form class="row g-2 mb-3" method="get" action="<?= h(BASE_URL) ?>/">
    <input type="hidden" name="r" value="orders/index">
    <div class="col-auto">
        <select name="status" class="form-select" onchange="this.form.submit()">
            <option value="">Semua Status</option>
            <?php foreach (["admin", "design", "vendor", "ready", "picked"] as $s): ?>
                <option value="<?= $s ?>" <?= $status === $s
                                                ? "selected"
                                                : "" ?>><?= $s ?></option>
            <?php endforeach; ?>
        </select>
    </div>
</form>

<?php if (!function_exists("pay_badge_class")) {
    function pay_badge_class($s)
    {
        return [
            "belum" => "secondary",
            "dp" => "warning",
            "lunas" => "success",
        ][$s] ?? "secondary";
    }
} ?>

<table class="table table-sm table-striped">
    <thead>
        <tr>
            <th>#</th>
            <th>Pelanggan</th>
            <th>Produk</th>
            <th>Qty</th>
            <th>Channel</th>
            <th>Deadline</th>
            <th>Status</th>
            <th>Bayar</th>
            <th></th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($rows as $i => $r): ?>
            <tr>
                <td><?= h($r["id"]) ?></td>
                <td><?= h($r["customer"] ?? "-") ?></td>
                <td><?= h($r["product"]) ?></td>
                <td><?= h($r["quantity"]) ?></td>
                <td><?= h($r["channel"]) ?></td>
                <td><?= h(date("d-m-Y H:i", strtotime($r["deadline"]))) ?></td>
                <td><span class="badge bg-<?= status_badge_class($r["status"]) ?>">
                        <?= h($r["status"]) ?>
                    </span></td>
                <td>
                    <span class="badge bg-<?= pay_badge_class(
                                                $r["payment_status"] ?? "belum",
                                            ) ?>">
                        <?= strtoupper($r["payment_status"] ?? "belum") ?>
                    </span>
                </td>
                <td>
                    <a class="btn btn-sm btn-primary" href="<?= h(
                                                                url("orders/detail"),
                                                            ) ?>&id=<?= $r["id"] ?>">Detail</a>
                </td>

            </tr>
        <?php endforeach; ?>
    </tbody>
</table>