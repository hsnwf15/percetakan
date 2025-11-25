<?php
require_once __DIR__ . "/../db.php";
require_once __DIR__ . "/../helpers.php";
require_once __DIR__ . "/../auth.php";

class VendorController
{
    // Create/Update single vendor job per order (UPSERT)
    public function save()
    {
        require_login();
        csrf_check();
        global $pdo;
        $orderId = (int) ($_POST["order_id"] ?? 0);
        if ($orderId <= 0) {
            die("order invalid");
        }

        // cek akses: designer hanya boleh jika assigned; admin/owner bebas
        $me = current_user();
        $row = $pdo->prepare("SELECT assigned_designer FROM orders WHERE id=?");
        $row->execute([$orderId]);
        $order = $row->fetch();
        if (!$order) {
            http_response_code(404);
            die("Order tidak ditemukan");
        }
        if (
            ($me["role"] ?? "") === "designer" &&
            (int) $order["assigned_designer"] !== (int) $me["id"]
        ) {
            http_response_code(403);
            die("Forbidden");
        }

        $vendor = trim($_POST["vendor_code"] ?? "");
        $status = $_POST["status"] ?? "sent";
        $catatan = trim($_POST["return_info"] ?? "");

        // validasi sederhana
        $allowedVendors = ["Mitra A", "Mitra B", "Mitra C", "Lainnya"];
        if (!in_array($vendor, $allowedVendors, true)) {
            $vendor = "Lainnya";
        }
        $allowedStatus = ["sent", "in_progress", "done"];
        if (!in_array($status, $allowedStatus, true)) {
            $status = "sent";
        }

        // upsert
        $pdo->prepare(
            "
      INSERT INTO vendor_jobs (order_id, vendor_code, status, return_info)
      VALUES (?,?,?,?)
      ON DUPLICATE KEY UPDATE vendor_code=VALUES(vendor_code), status=VALUES(status), return_info=VALUES(return_info)
    ",
        )->execute([$orderId, $vendor, $status, $catatan]);

        // otomatis ubah status order:
        // - kalau 'done' → set orders.status='ready'
        // - kalau belum pernah ke vendor (status order masih 'design') → pindahkan ke 'vendor'
        $cur = $pdo->prepare("SELECT status FROM orders WHERE id=?");
        $cur->execute([$orderId]);
        $os = $cur->fetchColumn();

        if ($status === "done") {
            $pdo->prepare(
                "UPDATE orders SET status='ready' WHERE id=?",
            )->execute([$orderId]);
            flash("Vendor selesai. Status order pindah ke 'ready'.");
        } else {
            if ($os === "design") {
                $pdo->prepare(
                    "UPDATE orders SET status='vendor' WHERE id=?",
                )->execute([$orderId]);
            }
            flash("Data vendor disimpan.");
        }

        redirect("?r=orders/detail&id=" . $orderId . "&tab=vendor");
    }
}
