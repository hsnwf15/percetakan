<div class="d-flex justify-content-between align-items-center mb-3">
    <h3>Daftar Pelanggan</h3>
    <?php if ((current_user()["role"] ?? "") === "admin"): ?>
        <a href="<?= h(
                        url("customers/create"),
                    ) ?>" class="btn btn-success">+ Pelanggan</a>
    <?php endif; ?>
</div>

<form class="row g-2 mb-3" method="get" action="<?= h(BASE_URL) ?>/">
    <input type="hidden" name="r" value="customers/index">
    <div class="col-auto">
        <input class="form-control" name="q" placeholder="Cari nama/telepon/email" value="<?= h(
                                                                                                $q ?? "",
                                                                                            ) ?>">
    </div>
    <div class="col-auto">
        <button class="btn btn-outline-primary">Cari</button>
        <a class="btn btn-outline-secondary" href="<?= h(
                                                        url("customers/index"),
                                                    ) ?>">Reset</a>
    </div>
</form>

<table class="table table-sm table-striped align-middle">
    <thead>
        <tr>
            <th>#</th>
            <th>Nama</th>
            <th>Telepon</th>
            <?php if (
                (current_user()["role"] ?? "") ===
                "admin"
            ): ?><th style="width:160px"></th><?php endif; ?>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($rows as $r): ?>
            <tr>
                <td><?= h($r["id"]) ?></td>
                <td><?= h($r["name"]) ?></td>
                <td><?= h($r["phone"]) ?></td>
                <?php if ((current_user()["role"] ?? "") === "admin"): ?>
                    <td>
                        <a class="btn btn-sm btn-primary" href="<?= h(
                                                                    url("customers/edit&id=" . $r["id"]),
                                                                ) ?>">Edit</a>
                        <form method="post" action="<?= h(
                                                        url("customers/destroy"),
                                                    ) ?>" class="d-inline" onsubmit="return confirm('Hapus pelanggan ini?')">
                            <?php csrf_field(); ?>
                            <input type="hidden" name="id" value="<?= h($r["id"]) ?>">
                            <button class="btn btn-sm btn-outline-danger">Hapus</button>
                        </form>
                    </td>
                <?php endif; ?>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>