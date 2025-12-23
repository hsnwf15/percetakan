<?php
require_once __DIR__ . "/../db.php";
require_once __DIR__ . "/../helpers.php";
require_once __DIR__ . "/../auth.php";

class DashboardController
{
    public function index()
    {
        require_login();
        global $pdo;

        // ------------- KPI kartu ringkas -------------
        // hitung status
        $st = $pdo
            ->query("SELECT status, COUNT(*) c FROM orders GROUP BY status")
            ->fetchAll();
        $byStatus = [];
        foreach ($st as $row) {
            $byStatus[$row["status"]] = (int) $row["c"];
        }

        $active =
            ($byStatus["admin"] ?? 0) +
            ($byStatus["design"] ?? 0) +
            ($byStatus["vendor"] ?? 0);
        $ready = $byStatus["ready"] ?? 0;
        $picked = $byStatus["picked"] ?? 0;

        // omzet 7 hari terakhir
        $rev7 = $pdo
            ->query(
                "
      SELECT COALESCE(SUM(amount),0) s
      FROM payments
      WHERE paid_at >= DATE_SUB(CURDATE(), INTERVAL 6 DAY)
    ",
            )
            ->fetchColumn();
        $rev7 = (float) $rev7;

        // outstanding (piutang): sum per order (total_price - total paid, min 0)
        $rows = $pdo
            ->query(
                "
      SELECT o.id, o.total_price, COALESCE(SUM(p.amount),0) paid
      FROM orders o
      LEFT JOIN payments p ON p.order_id = o.id
      GROUP BY o.id
    ",
            )
            ->fetchAll();
        $outstanding = 0.0;
        $totalInvoice = 0.0;
        $totalPaidAll = 0.0;
        foreach ($rows as $r) {
            $tp = (float) ($r["total_price"] ?? 0);
            $pd = (float) $r["paid"];
            $totalInvoice += $tp;
            $totalPaidAll += $pd;
            if ($tp > 0 && $pd < $tp) {
                $outstanding += $tp - $pd;
            }
        }

        // ------------- Grafik: orders per hari (7 hari) -------------
        $ord7 = $pdo
            ->query(
                "
      SELECT DATE(created_at) d, COUNT(*) c
      FROM orders
      WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 6 DAY)
      GROUP BY DATE(created_at)
    ",
            )
            ->fetchAll();
        $ordersDaily = $this->fillDaily7($ord7, "c");

        // ------------- Grafik: revenue per hari (7 hari) -------------
        $revDailyRaw = $pdo
            ->query(
                "
SELECT DATE(paid_at) d, SUM(amount) s
      FROM payments
      WHERE paid_at >= DATE_SUB(CURDATE(), INTERVAL 6 DAY)
      GROUP BY DATE(paid_at)
    ",
            )
            ->fetchAll(); // curly brace typo avoided in PHP; ensure correct bracket.

        // (Perbaikan: kadang IDE menyelipkan } – kita buat ulang query benar)
        $revDailyRaw = $pdo
            ->query(
                "
      SELECT DATE(paid_at) d, SUM(amount) s
      FROM payments
      WHERE paid_at >= DATE_SUB(CURDATE(), INTERVAL 6 DAY)
      GROUP BY DATE(paid_at)
    ",
            )
            ->fetchAll();
        $revenueDaily = $this->fillDaily7($revDailyRaw, "s");

        // ------------- Deadline terdekat (10) -------------
        $near = $pdo
            ->query(
                "
      SELECT o.id, c.name AS customer, o.product, o.quantity, o.deadline, o.status
      FROM orders o
      LEFT JOIN customers c ON c.id=o.customer_id
      WHERE o.status IN ('admin','design','vendor')
        AND o.deadline IS NOT NULL
      ORDER BY o.deadline ASC
      LIMIT 10
    ",
            )
            ->fetchAll();

        // ------------- Pembayaran terakhir (10) -------------
        $recentPay = $pdo
            ->query(
                "
      SELECT p.*, o.product, c.name AS customer
      FROM payments p
      LEFT JOIN orders o ON o.id = p.order_id
      LEFT JOIN customers c ON c.id = o.customer_id
      ORDER BY p.paid_at DESC
      LIMIT 10
    ",
            )
            ->fetchAll();

        // ===============================
        // Card: Fee Desainer Minggu Ini
        // ===============================
        $stmt = $pdo->query("
        SELECT 
            u.id,
            u.name,
            COUNT(o.id) AS total_order,
            SUM(o.designer_fee) AS total_fee
        FROM orders o
        JOIN users u ON u.id = o.assigned_designer
        WHERE 
            u.role = 'designer'
            AND o.designer_fee > 0
            AND YEARWEEK(o.created_at, 1) = YEARWEEK(CURDATE(), 1)
        GROUP BY u.id, u.name
        ORDER BY total_fee DESC
    ");
        $designerFees = $stmt->fetchAll();

        view(
            "dashboard",
            compact(
                "active",
                "ready",
                "picked",
                "rev7",
                "outstanding",
                "ordersDaily",
                "revenueDaily",
                "near",
                "recentPay",
                "totalInvoice",
                "totalPaidAll",
                "designerFees",
            ),
        );
    }

    private function fillDaily7($rows, $key)
    {
        // hasilkan data 7 titik: hari ini mundur 6 hari
        $map = [];
        foreach ($rows as $r) {
            $map[$r["d"]] = (float) $r[$key];
        }
        $labels = [];
        $data = [];
        for ($i = 6; $i >= 0; $i--) {
            $d = date("Y-m-d", strtotime("-{$i} days"));
            $labels[] = date("d M", strtotime($d));
            $data[] = isset($map[$d]) ? $map[$d] : 0;
        }
        return ["labels" => $labels, "data" => $data];
    }

    public function feeWeekly()
    {
        require_login();
        global $pdo;

        $me = current_user();

        // Ambil minggu & tahun (default: minggu ini)
        $week = (int)($_GET['week'] ?? date('W'));
        $year = (int)($_GET['year'] ?? date('Y'));

        // Filter role
        $where = "";
        $params = [$year, $week];

        if (($me['role'] ?? '') === 'designer') {
            $where = "AND o.assigned_designer = ?";
            $params[] = $me['id'];
        }

        $sql = "
        SELECT
            u.id AS designer_id,
            u.name AS designer_name,
            COUNT(o.id) AS total_order,
            SUM(o.designer_fee) AS total_fee
        FROM orders o
        JOIN users u ON u.id = o.assigned_designer
        WHERE u.role = 'designer'
          AND o.status IN ('vendor','ready','picked')
          AND YEAR(o.design_done_at) = ?
          AND WEEK(o.design_done_at, 1) = ?
          $where
        GROUP BY u.id, u.name
        ORDER BY total_fee DESC
    ";

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll();

        // Ambil tanggal dari week & year
        $dto = new DateTime();
        $dto->setISODate($year, $week);

        // Nama bulan & tahun
        $monthName = $dto->format('F'); // December
        $monthYear = $dto->format('Y');

        // Hitung minggu ke-n dalam bulan
        $dayOfMonth = (int)$dto->format('j');
        $weekOfMonth = (int)ceil($dayOfMonth / 7);

        // Ubah nama bulan ke Bahasa Indonesia
        $bulanMap = [
            'January' => 'Januari',
            'February' => 'Februari',
            'March' => 'Maret',
            'April' => 'April',
            'May' => 'Mei',
            'June' => 'Juni',
            'July' => 'Juli',
            'August' => 'Agustus',
            'September' => 'September',
            'October' => 'Oktober',
            'November' => 'November',
            'December' => 'Desember',
        ];

        $bulanIndo = $bulanMap[$monthName] ?? $monthName;


        view("report_fee_weekly", compact(
            "rows",
            "week",
            "year",
            "weekOfMonth",
            "bulanIndo"
        ));
    }
}
