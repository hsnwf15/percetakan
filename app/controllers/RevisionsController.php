<?php
require_once __DIR__ . "/../db.php";
require_once __DIR__ . "/../helpers.php";
require_once __DIR__ . "/../auth.php";

class RevisionsController
{
    // ============ Upload revisi (auto Rev-1/2/3) ============
    public function upload()
    {
        require_login();
        csrf_check();
        global $pdo;

        $orderId = (int) ($_POST["order_id"] ?? 0);
        if ($orderId <= 0) {
            die("order invalid");
        }

        // cek hak akses: desainer hanya boleh upload pada order yg ditugaskan
        $me = current_user();
        $row = $pdo->prepare(
            "SELECT assigned_designer, status FROM orders WHERE id=?",
        );
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

        // validasi file
        if (
            !isset($_FILES["file"]) ||
            $_FILES["file"]["error"] !== UPLOAD_ERR_OK
        ) {
            flash("Upload gagal: file tidak valid");
            redirect("?r=orders/detail&id=" . $orderId);
        }
        $allowed = [
            "pdf",
            "ai",
            "eps",
            "svg",
            "cdr",
            "jpg",
            "jpeg",
            "png",
            "psd",
        ];
        $name = $_FILES["file"]["name"];
        $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
        if (!in_array($ext, $allowed, true)) {
            flash("Tipe file tidak diizinkan");
            redirect("?r=orders/detail&id=" . $orderId);
        }

        // nomor revisi berikutnya
        $next = (int) $pdo
            ->query(
                "SELECT IFNULL(MAX(rev_no),0)+1 AS n FROM design_revisions WHERE order_id={$orderId}",
            )
            ->fetchColumn();

        // simpan file
        $root = __DIR__ . "/../../public/uploads/{$orderId}";
        if (!is_dir($root)) {
            @mkdir($root, 0775, true);
        }
        $safeBase = "REV_" . $next . "_" . time();
        $fname = $safeBase . "." . $ext;
        $dest = $root . "/" . $fname;
        if (!move_uploaded_file($_FILES["file"]["tmp_name"], $dest)) {
            flash("Gagal menyimpan file");
            redirect("?r=orders/detail&id=" . $orderId);
        }
        // path publik:
        $publicPath = "/uploads/{$orderId}/{$fname}";

        // catat revisi
        $note = trim($_POST["note"] ?? "");
        $stmt = $pdo->prepare(
            "INSERT INTO design_revisions (order_id, rev_no, file_path, note, created_by) VALUES (?,?,?,?,?)",
        );
        $stmt->execute([$orderId, $next, $publicPath, $note, $me["id"]]);

        // kalau status masih 'admin' → pindah ke 'design'
        if ($order["status"] === "admin") {
            $pdo->prepare(
                "UPDATE orders SET status='design' WHERE id=?",
            )->execute([$orderId]);
        }

        flash("Revisi ke-{$next} diunggah.");
        redirect("?r=orders/detail&id=" . $orderId);
    }

    // ============ Approve final (kunci ke vendor) ============
    public function approveFinal()
    {
        require_login();
        csrf_check();
        global $pdo;

        $orderId = (int) ($_POST["order_id"] ?? 0);
        if ($orderId <= 0) {
            die("order invalid");
        }

        // cek hak akses: admin/owner bebas, designer hanya jika assigned
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

        // harus ada minimal 1 revisi
        $last = (int) $pdo
            ->query(
                "SELECT IFNULL(MAX(rev_no),0) FROM design_revisions WHERE order_id={$orderId}",
            )
            ->fetchColumn();
        if ($last <= 0) {
            flash("Belum ada revisi untuk di-approve");
            redirect("?r=orders/detail&id=" . $orderId);
        }

        // set / update approval
        $pdo->prepare(
            "INSERT INTO design_approvals (order_id, approved_rev)
                   VALUES (?,?)
                   ON DUPLICATE KEY UPDATE approved_rev=VALUES(approved_rev), approved_at=NOW()",
        )->execute([$orderId, $last]);

        // lanjut ke vendor
        $pdo->prepare("UPDATE orders SET status='vendor' WHERE id=?")->execute([
            $orderId,
        ]);

        flash(
            "Final disetujui (Revisi ke-{$last}). Status pindah ke 'vendor'.",
        );
        redirect("?r=orders/detail&id=" . $orderId);
    }
}
