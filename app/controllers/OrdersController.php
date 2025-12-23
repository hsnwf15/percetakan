<?php
require_once __DIR__ . "/../db.php";
require_once __DIR__ . "/../helpers.php";
require_once __DIR__ . "/../auth.php";

class OrdersController
{
    public function index()
    {
        require_login();
        global $pdo;

        $me   = current_user();
        $role = $me["role"] ?? "";

        // Filter status (sudah ada sebelumnya)
        $status = $_GET["status"] ?? "";

        // Filter scope: 'mine' (tugas saya) atau 'all' (semua)
        // Default:
        // - designer  → 'mine'
        // - role lain → 'all'
        $scope = $_GET["scope"] ?? (($role === "designer") ? "mine" : "all");

        $whereParts = [];
        $args       = [];

        // filter status
        if ($status !== "") {
            $whereParts[] = "o.status = ?";
            $args[]       = $status;
        }

        // filter scope untuk designer
        if ($role === "designer" && $scope === "mine") {
            $whereParts[] = "o.assigned_designer = ?";
            $args[]       = $me["id"];
        }

        $whereSql = "";
        if (!empty($whereParts)) {
            $whereSql = "WHERE " . implode(" AND ", $whereParts);
        }

        $sql = "
        SELECT o.*, c.name AS customer
        FROM orders o
        LEFT JOIN customers c ON c.id = o.customer_id
        $whereSql
        ORDER BY o.created_at DESC
        LIMIT 200
    ";

        $stmt = $pdo->prepare($sql);
        $stmt->execute($args);
        $rows = $stmt->fetchAll();

        view("orders_list", compact("rows", "status", "scope"));
    }


    public function create()
    {
        require_login();
        global $pdo;

        // Ambil semua customer lengkap dengan phone
        $rows = $pdo
            ->query("SELECT id, name, phone FROM customers ORDER BY name")
            ->fetchAll(PDO::FETCH_ASSOC);

        // Group per nama → agar 1 nama bisa punya lebih dari 1 nomor telepon
        $customers = [];  // key = name, value = ['id' => ..., 'phones' => [...]]
        foreach ($rows as $row) {
            $name  = $row['name'];
            $phone = trim($row['phone'] ?? '');

            if (!isset($customers[$name])) {
                $customers[$name] = [
                    'id'     => $row['id'],   // ambil salah satu id per nama
                    'phones' => [],
                ];
            }

            if ($phone !== '' && !in_array($phone, $customers[$name]['phones'], true)) {
                $customers[$name]['phones'][] = $phone;
            }
        }

        // Desainer tetap
        $designers = $pdo
            ->query(
                "SELECT id,name FROM users WHERE role='designer' ORDER BY name"
            )
            ->fetchAll(PDO::FETCH_ASSOC);

        view("orders_form", compact("customers", "designers"));
    }

    public function store()
    {
        require_login();
        csrf_check();
        global $pdo;

        // HYBRID: baca nama pelanggan dari input teks, link ke customers.id
        $name = trim($_POST["customer_name"] ?? "");
        $customerId = (int) ($_POST['customer_id'] ?? 0);
        $customerPhone = trim($_POST['customer_phone'] ?? '');

        if (!$customerId && $name !== '') {
            // coba cari pelanggan existing (exact match; default collation MySQL biasanya case-insensitive)
            $chk = $pdo->prepare(
                "SELECT id FROM customers WHERE name = ? LIMIT 1",
            );
            $chk->execute([$name]);
            $foundId = $chk->fetchColumn();

            if ($foundId) {
                // nama sudah ada → pakai id tersebut
                $customerId = (int)$foundId;

                // kalau ada phone yang diisi dan kamu ingin mengupdate
                if ($customerPhone !== '') {
                    $upd = $pdo->prepare("UPDATE customers SET phone = ? WHERE id = ?");
                    $upd->execute([$customerPhone, $customerId]);
                }
            } else {
                // benar-benar customer baru → insert name + phone
                $ins = $pdo->prepare("INSERT INTO customers (name, phone) VALUES (?, ?)");
                $ins->execute([
                    $name,
                    $customerPhone !== '' ? $customerPhone : null,
                ]);
                $customerId = (int)$pdo->lastInsertId();
            }
        } elseif ($customerId && $customerPhone !== '') {
            // kalau pilih dari autocomplete tapi phone di DB masih kosong/beda,
            // boleh diupdate juga (opsional)
            $upd = $pdo->prepare("
                UPDATE customers
                SET phone = ?
                WHERE id = ?
            ");
            $upd->execute([$customerPhone, $customerId]);
        }

        $sql = "INSERT INTO orders
            (customer_id, product, spec, quantity, total_price, channel, deadline, assigned_designer, dp_amount, notes)
        VALUES (?,?,?,?,?,?,?,?,?,?)";

        $pdo->prepare($sql)->execute([
            $customerId ?: null,
            $_POST["product"],
            $_POST["spec"] ?? null,
            (int) ($_POST["quantity"] ?? 1),
            $_POST["total_price"] !== "" ? (float) $_POST["total_price"] : null,
            $_POST["channel"],
            $_POST["deadline"] ?: null,
            $_POST["assigned_designer"] ?: null,
            (float) ($_POST["dp_amount"] ?? 0),
            $_POST["notes"] ?? null,
        ]);

        $orderId = (int) $pdo->lastInsertId();

        // === KONVERSI DP ke payments ===
        $dp = (float) ($_POST["dp_amount"] ?? 0);
        if ($dp > 0) {
            // skema payments kamu: order_id, amount, method, paid_at
            $pdo->prepare(
                "INSERT INTO payments (order_id, amount, method, paid_at)
                 VALUES (?,?,?,NOW())",
            )->execute([$orderId, $dp, "dp"]);

            // set status pembayaran order → DP
            $pdo->prepare(
                "UPDATE orders SET payment_status='dp' WHERE id=?",
            )->execute([$orderId]);
        }

        flash("Order dibuat.");
        redirect("?r=orders/index");
    }

    public function detail()
    {
        require_login();
        global $pdo;
        $id = (int) ($_GET["id"] ?? 0);

        $stmt = $pdo->prepare("
            SELECT o.*, c.name AS customer_name, u.name AS designer_name
            FROM orders o
            LEFT JOIN customers c ON c.id = o.customer_id
            LEFT JOIN users u ON u.id = o.assigned_designer
            WHERE o.id=? LIMIT 1
        ");
        $stmt->execute([$id]);
        $order = $stmt->fetch();
        if (!$order) {
            http_response_code(404);
            die("Order tidak ditemukan");
        }

        $me   = current_user();
        $role = $me['role'] ?? '';

        $isAssignedDesigner = (
            $role === 'designer' &&
            (int)($order['assigned_designer'] ?? 0) === (int)($me['id'] ?? 0)
        );

        // admin & owner tetap boleh edit semua
        $canEdit = in_array($role, ['admin', 'owner'], true) || $isAssignedDesigner;


        // data revisi + user uploader
        $revs = $pdo->prepare("
    SELECT r.*, u.name AS uploader
    FROM design_revisions r LEFT JOIN users u ON u.id = r.created_by
    WHERE r.order_id=? ORDER BY r.rev_no ASC
  ");
        $revs->execute([$id]);
        $revisions = $revs->fetchAll();

        // approval
        $ap = $pdo->prepare(
            "SELECT * FROM design_approvals WHERE order_id=? LIMIT 1",
        );
        $ap->execute([$id]);
        $approval = $ap->fetch();

        // ... setelah $approval
        $vj = $pdo->prepare(
            "SELECT * FROM vendor_jobs WHERE order_id=? LIMIT 1",
        );
        $vj->execute([$id]);
        $vendorJob = $vj->fetch();

        // pembacaaan data keuangan
        $qPay = $pdo->prepare(
            "SELECT * FROM payments WHERE order_id=? ORDER BY paid_at",
        );
        $qPay->execute([$id]);
        $payments = $qPay->fetchAll();

        $sum = $pdo->prepare(
            "SELECT COALESCE(SUM(amount),0) FROM payments WHERE order_id=?",
        );
        $sum->execute([$id]);
        $totalPaid = (float) $sum->fetchColumn();
        $outstanding = ($order["total_price"] ?? 0) - $totalPaid;

        $statuses = ["admin", "design", "vendor", "ready", "picked"];
        // Ambil riwayat status vendor
        $logsStmt = $pdo->prepare("
    SELECT vsl.*, u.name AS user_name
    FROM vendor_status_logs vsl
    LEFT JOIN users u ON u.id = vsl.changed_by
    WHERE vsl.order_id = ?
    ORDER BY vsl.changed_at DESC
");
        $logsStmt->execute([$order["id"]]);   // atau $id / $orderId, sesuaikan dengan variabelmu
        $vendorLogs = $logsStmt->fetchAll(PDO::FETCH_ASSOC);
        view(
            "order_detail",
            compact(
                "order",
                "statuses",
                "revisions",
                "approval",
                "vendorJob",
                "payments",
                "totalPaid",
                "outstanding",
                "vendorLogs",
                'canEdit'
            ),
        );
    }

    public function updateStatus()
    {
        require_login();
        csrf_check();
        global $pdo;

        $id = (int)($_POST['id'] ?? 0);
        $status = $_POST['status'] ?? 'design';
        $newFee = (int)($_POST['designer_fee'] ?? 0);

        if ($id <= 0) {
            die("Order tidak valid");
        }

        $allowed = ['admin', 'design', 'vendor', 'ready', 'picked'];
        if (!in_array($status, $allowed, true)) {
            die("Status tidak valid");
        }

        // Ambil order
        $stmt = $pdo->prepare("SELECT status, assigned_designer FROM orders WHERE id=?");
        $stmt->execute([$id]);
        $order = $stmt->fetch();

        if (!$order) {
            http_response_code(404);
            die("Order tidak ditemukan");
        }

        $me = current_user();

        // Hak akses desainer
        if (
            ($me['role'] ?? '') === 'designer' &&
            (int)$order['assigned_designer'] !== (int)$me['id']
        ) {
            http_response_code(403);
            die("Forbidden");
        }

        // Desainer hanya boleh ubah fee saat status design
        if (($me['role'] ?? '') === 'designer') {
            if ($order['status'] !== 'design') {
                die("Fee hanya bisa diubah saat tahap desain");
            }

            if ($newFee < 5000 || $newFee > 20000) {
                die("Fee desain harus antara 5.000 – 20.000");
            }
        }

        // UPDATE SEKALIGUS
        $pdo->prepare("
        UPDATE orders
        SET status = ?, designer_fee = ?
        WHERE id = ?
    ")->execute([
            $status,
            $newFee,
            $id
        ]);

        flash("Status & fee berhasil diperbarui");
        redirect("?r=orders/detail&id=" . $id);
    }
}
