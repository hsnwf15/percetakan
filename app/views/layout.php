<?php $flashMsg = flash();
$u = current_user();
?>
<!doctype html>
<html lang="id">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Percetakan – MVP</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet" />
    <style>
        .container {
            max-width: 1100px
        }

        .nav-link.active {
            font-weight: 700
        }
    </style>
</head>

<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark mb-3">
        <div class="container">
            <a class="navbar-brand" href="<?= h(url("orders/index")) ?>">Percetakan</a>
            <div class="collapse navbar-collapse">
                <ul class="navbar-nav me-auto">
                    <?php if ($u): ?>
                        <li class="nav-item"><a class="nav-link" href="<?= h(
                                                                            url("orders/index"),
                                                                        ) ?>">Orders</a></li>
                        <li class="nav-item"><a class="nav-link" href="<?= h(
                                                                            url("orders/create"),
                                                                        ) ?>">Order Baru</a></li>
                        <li class="nav-item"><a class="nav-link" href="<?= h(
                                                                            url("customers/index"),
                                                                        ) ?>">Pelanggan</a></li>
                        <li class="nav-item"><a class="nav-link" href="<?= h(
                                                                            url("dashboard/index"),
                                                                        ) ?>">Dashboard</a></li>
                    <?php endif; ?>
                </ul>
                <div class="text-end text-white">
                    <?php if ($u): ?>Halo, <?= h($u["name"]) ?> (<?= h(
                                                                        $u["role"],
                                                                    ) ?>) · <a class="text-warning" href="<?= h(
                                            url("auth/logout"),
                                        ) ?>">Keluar</a><?php endif; ?>
                </div>
            </div>
        </div>
    </nav>
    <div class="container">
        <?php if ($flashMsg): ?><div class="alert alert-info"><?= h(
                                                                    $flashMsg,
                                                                ) ?></div><?php endif; ?>
        <main>
            <?php // child views rendered here

            if (isset($__child)) {
                echo $__child;
            } else {
                /* fallback */
            } ?>
        </main>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>