<?php
require_once __DIR__ . "/../db.php";
require_once __DIR__ . "/../helpers.php";
require_once __DIR__ . "/../auth.php";

class FinanceController
{
    public function save()
    {
        require_login();
        csrf_check();
        global $pdo;

        $orderId = (int) ($_POST["order_id"] ?? 0);
        $amount = (float) ($_POST["amount"] ?? 0);
        $method = trim($_POST["method"] ?? "dp"); // dp / pelunasan / cash / transfer, dll
        $paidAt = $_POST["paid_at"] ?? date("Y-m-d H:i:s");

        if ($orderId <= 0 || $amount <= 0) {
            flash("Data pembayaran tidak lengkap.");
            redirect("?r=orders/detail&id=" . $orderId . "&tab=finance");
        }

        // INSERT ke payments sesuai skema kamu
        $pdo->prepare(
            "INSERT INTO payments (order_id, amount, method, paid_at) VALUES (?,?,?,?)",
        )->execute([$orderId, $amount, $method, $paidAt]);

        // Update status pembayaran di orders:
        // - jika method 'pelunasan' → set 'lunas'
        // - selain itu → minimal 'dp'
        $status = strtolower($method) === "pelunasan" ? "lunas" : "dp";
        $pdo->prepare("UPDATE orders SET payment_status=? WHERE id=?")->execute(
            [$status, $orderId],
        );

        flash("Pembayaran tersimpan.");
        redirect("?r=orders/detail&id=" . $orderId . "&tab=finance");
    }
}
