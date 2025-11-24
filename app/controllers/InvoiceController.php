<?php
require_once __DIR__ . "/../db.php";
require_once __DIR__ . "/../helpers.php";
require_once __DIR__ . "/../auth.php";

use Dompdf\Dompdf;
use Dompdf\Options;

class InvoiceController
{
    // Preview HTML (kalau mau lihat sebelum PDF)
    public function show()
    {
        require_login();
        global $pdo;
        $id = (int) ($_GET["id"] ?? 0);
        $data = $this->loadData($id);
        view("invoice_template", $data);
    }

    // Download PDF
    public function pdf()
    {
        require_login();
        global $pdo;

        $id = (int) ($_GET["id"] ?? 0);
        $data = $this->loadData($id);

        // Render HTML pakai view sebagai string
        ob_start();
        extract($data);
        include __DIR__ . "/../views/invoice_template.php";
        $html = ob_get_clean();

        $options = new Options();
        $options->set("isHtml5ParserEnabled", true);
        $options->set("isRemoteEnabled", true); // agar bisa load QR dari URL jika diperlukan

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html, "UTF-8");
        $dompdf->setPaper("A5", "portrait"); // atau A4 landscape
        $dompdf->render();

        $filename = "Nota-Order-" . $data["order"]["id"] . ".pdf";
        $dompdf->stream($filename, ["Attachment" => true]); // download
    }

    // -------- helper: ambil data invoice --------
    private function loadData($orderId)
    {
        global $pdo;
        if ($orderId <= 0) {
            http_response_code(404);
            die("Order invalid");
        }

        // Order + customer + designer
        $stmt = $pdo->prepare("
      SELECT o.*, c.name AS customer_name, c.phone AS customer_phone,
             u.name AS designer_name
      FROM orders o
      LEFT JOIN customers c ON c.id = o.customer_id
      LEFT JOIN users u ON u.id = o.assigned_designer
      WHERE o.id = ? LIMIT 1
    ");
        $stmt->execute([$orderId]);
        $order = $stmt->fetch();
        if (!$order) {
            http_response_code(404);
            die("Order tidak ditemukan");
        }

        // Payments
        $pay = $pdo->prepare(
            "SELECT * FROM payments WHERE order_id=? ORDER BY paid_at",
        );
        $pay->execute([$orderId]);
        $payments = $pay->fetchAll();

        $sum = $pdo->prepare(
            "SELECT COALESCE(SUM(amount),0) FROM payments WHERE order_id=?",
        );
        $sum->execute([$orderId]);
        $totalPaid = (float) $sum->fetchColumn();

        $totalPrice = (float) ($order["total_price"] ?? 0);
        $outstanding = max(0, $totalPrice - $totalPaid);

        // Toko (silakan sesuaikan)
        $store = [
            "name" => "Brotherprint Advertising",
            "address" => "Jl. STM Nomor 19 (bundaran PDAM), Banjarbaru 70714",
            "phone" => "0853-1133-1990",
            "email" => "brotherprint.advertising@gmail.com",
        ];

        // Nomor nota sederhana (boleh diganti pola lain)
        $invoiceNo = "INV-" . date("Ymd") . "-" . $order["id"];

        // QR verifikasi (opsional, online). Data yang diencode bebas.
        $verifyUrl = BASE_URL . "/?r=orders/detail&id=" . $order["id"];
        $qrSrc =
            "https://chart.googleapis.com/chart?chs=160x160&cht=qr&chl=" .
            urlencode($verifyUrl) .
            "&choe=UTF-8";

        return compact(
            "order",
            "payments",
            "totalPaid",
            "totalPrice",
            "outstanding",
            "store",
            "invoiceNo",
            "verifyUrl",
            "qrSrc",
        );
    }
}
