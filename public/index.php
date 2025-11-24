<?php
//composer
$autoload = __DIR__ . "/../vendor/autoload.php";
if (file_exists($autoload)) {
    require $autoload;
}

// Simple front controller & router
require __DIR__ . "/../app/config.php";
require __DIR__ . "/../app/db.php";
require __DIR__ . "/../app/helpers.php";
require __DIR__ . "/../app/auth.php";
require __DIR__ . "/../app/routes.php";

$r = $_GET["r"] ?? "orders/index";
dispatch($r);

?>
