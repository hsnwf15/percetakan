<?php
function dispatch($r)
{
    [$ctl, $act] = array_pad(explode("/", $r, 2), 2, "index");
    $map = [
        "auth" => "AuthController",
        "orders" => "OrdersController",
        "customers" => "CustomersController",
        "rev" => "RevisionsController",
        "vendor" => "VendorController",
        "finance" => "FinanceController",
        "invoice" => "InvoiceController",
        "dashboard" => "DashboardController",
        "report" => "DashboardController",
    ];
    $class = $map[$ctl] ?? "OrdersController";
    require_once __DIR__ . "/controllers/" . $class . ".php";
    $c = new $class();
    if (!method_exists($c, $act)) {
        $act = "index";
    }
    $c->$act();
}
