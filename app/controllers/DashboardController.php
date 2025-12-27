<?php
require_once __DIR__ . "/../db.php";
require_once __DIR__ . "/../helpers.php";
require_once __DIR__ . "/../auth.php";

use Dompdf\Dompdf;
use Dompdf\Options;

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

    public function pemasukanBulanan()
    {
        require_login();
        global $pdo;

        $month = (int)($_GET['month'] ?? date('m'));
        $year  = (int)($_GET['year'] ?? date('Y'));

        $start = sprintf('%04d-%02d-01', $year, $month);
        $end   = date('Y-m-d', strtotime("$start +1 month"));

        $stmt = $pdo->prepare("
    SELECT COALESCE(SUM(p.amount),0) AS total
    FROM payments p
    JOIN orders o ON o.id = p.order_id
    WHERE MONTH(p.paid_at) = ?
      AND YEAR(p.paid_at) = ?
");
        $stmt->execute([$month, $year]);
        $totalIncome = (float)$stmt->fetchColumn();


        // $totalIncome = 0;
        // foreach ($rows as $r) {
        //     $totalIncome += (float)$r['amount'];
        // }

        /** =======================
         * 2. DATA GRAFIK HARIAN
         * ======================= */
        $stmt = $pdo->prepare("
  SELECT DATE(p.paid_at) AS tanggal, SUM(p.amount) AS total
  FROM payments p
  WHERE MONTH(p.paid_at)=? AND YEAR(p.paid_at)=?
  GROUP BY DATE(p.paid_at)
  ORDER BY tanggal
");
        $stmt->execute([$month, $year]);
        $grafik = $stmt->fetchAll();

        $labels = [];
        $data = [];
        foreach ($grafik as $g) {
            $labels[] = date('d', strtotime($g['tanggal']));
            $data[] = (int)$g['total'];
        }

        /** =======================
         * 3. DETAIL PEMASUKAN
         * ======================= */
        $stmt = $pdo->prepare("
  SELECT o.id AS order_id, c.name AS customer, o.total_price, p.amount, p.paid_at
  FROM payments p
  JOIN orders o ON o.id=p.order_id
  LEFT JOIN customers c ON c.id=o.customer_id
  WHERE MONTH(p.paid_at)=? AND YEAR(p.paid_at)=?
  ORDER BY p.paid_at DESC
");
        $stmt->execute([$month, $year]);
        $details = $stmt->fetchAll();

        view('report_pemasukan_bulanan', compact(
            'month',
            'year',
            'totalIncome',
            'labels',
            'data',
            'details'
        ));
    }

    public function incomePdf()
    {
        require_login();
        global $pdo;

        $month = (int)($_GET['month'] ?? date('m'));
        $year  = (int)($_GET['year'] ?? date('Y'));

        $stmt = $pdo->prepare("
  SELECT o.id AS order_id, c.name AS customer, p.amount, p.paid_at
  FROM payments p
  JOIN orders o ON o.id=p.order_id
  LEFT JOIN customers c ON c.id=o.customer_id
  WHERE MONTH(p.paid_at)=? AND YEAR(p.paid_at)=?
  ORDER BY p.paid_at ASC
");
        $stmt->execute([$month, $year]);
        $details = $stmt->fetchAll();

        $stmt = $pdo->prepare("
  SELECT COALESCE(SUM(p.amount),0) total
  FROM payments p
  WHERE MONTH(p.paid_at)=? AND YEAR(p.paid_at)=?
");
        $stmt->execute([$month, $year]);
        $totalIncome = (float)$stmt->fetchColumn();

        // =======================
        // Grafik Pemasukan Harian (untuk PDF) -> PNG base64
        // =======================
        $stmt = $pdo->prepare("
            SELECT DATE(p.paid_at) AS tanggal, SUM(p.amount) AS total
            FROM payments p
            WHERE MONTH(p.paid_at)=? AND YEAR(p.paid_at)=?
            GROUP BY DATE(p.paid_at)
            ORDER BY tanggal
        ");
        $stmt->execute([$month, $year]);
        $grafik = $stmt->fetchAll();

        $labels = [];
        $values = [];
        foreach ($grafik as $g) {
            $labels[] = date('d', strtotime($g['tanggal'])); // 01..31
            $values[] = (float)($g['total'] ?? 0);
        }

        // Jika bulan ini tidak ada data, tetap bikin grafik kosong 1 bar supaya sumbu muncul
        if (count($labels) === 0) {
            $labels = ['01'];
            $values = [0];
        }

        $chartUri = chart_bar_data_uri($labels, $values);

        // Render HTML view ke variable
        ob_start();
        include __DIR__ . '/../views/report_income_pdf.php';
        $html = ob_get_clean();

        // Dompdf
        $options = new Options();
        $options->set('isRemoteEnabled', true);

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        $dompdf->stream(
            "Laporan_Pemasukan_{$month}_{$year}.pdf",
            ["Attachment" => true]
        );
    }
    public function orderBulanan()
    {
        require_login();
        global $pdo;

        $month = (int)($_GET['month'] ?? date('m'));
        $year  = (int)($_GET['year'] ?? date('Y'));

        $start = sprintf('%04d-%02d-01', $year, $month);
        $end   = date('Y-m-d', strtotime("$start +1 month"));

        // 1) Total order bulan itu (berdasarkan created_at)
        $stmt = $pdo->prepare("
        SELECT COUNT(*)
        FROM orders
        WHERE created_at >= ? AND created_at < ?
    ");
        $stmt->execute([$start, $end]);
        $totalOrder = (int)$stmt->fetchColumn();

        // 2) Rekap status order bulan itu
        $stmt = $pdo->prepare("
        SELECT status, COUNT(*) AS total
        FROM orders
        WHERE created_at >= ? AND created_at < ?
        GROUP BY status
        ORDER BY total DESC
    ");
        $stmt->execute([$start, $end]);
        $byStatusRaw = $stmt->fetchAll();

        // Biar urut & konsisten
        $statusList = ['admin', 'design', 'vendor', 'ready', 'picked'];
        $byStatus = array_fill_keys($statusList, 0);

        foreach ($byStatusRaw as $r) {
            $st = $r['status'];
            $cnt = (int)$r['total'];
            if (!isset($byStatus[$st])) $byStatus[$st] = 0; // jaga2 status lain
            $byStatus[$st] += $cnt;
        }

        // 3) Data grafik distribusi status
        $labels = array_keys($byStatus);
        $data   = array_values($byStatus);

        // 4) Detail order bulan itu (untuk tabel)
        $stmt = $pdo->prepare("
  SELECT
    o.id,
    o.created_at,
    c.name AS customer,
    o.product,
    o.quantity,
    o.status,
    o.deadline,
    o.total_price
  FROM orders o
  LEFT JOIN customers c ON c.id = o.customer_id
  WHERE o.created_at >= ? AND o.created_at < ?
  ORDER BY o.created_at DESC
  LIMIT 200
");
        $stmt->execute([$start, $end]);
        $orders = $stmt->fetchAll();

        view('report_order_bulanan', compact(
            'month',
            'year',
            'totalOrder',
            'byStatus',
            'labels',
            'data',
            'orders'
        ));
    }
    public function orderBulananPdf()
    {
        require_login();
        global $pdo;

        $month = (int)($_GET['month'] ?? date('m'));
        $year  = (int)($_GET['year'] ?? date('Y'));

        $start = sprintf('%04d-%02d-01', $year, $month);
        $end   = date('Y-m-d', strtotime("$start +1 month"));

        // --- Summary: total order
        $stmt = $pdo->prepare("
      SELECT COUNT(*) 
      FROM orders
      WHERE created_at >= ? AND created_at < ?
    ");
        $stmt->execute([$start, $end]);
        $totalOrder = (int)$stmt->fetchColumn();

        // --- Distribusi status
        $stmt = $pdo->prepare("
      SELECT status, COUNT(*) c
      FROM orders
      WHERE created_at >= ? AND created_at < ?
      GROUP BY status
    ");
        $stmt->execute([$start, $end]);
        $raw = $stmt->fetchAll();

        $byStatus = [
            'admin' => 0,
            'design' => 0,
            'vendor' => 0,
            'ready' => 0,
            'picked' => 0
        ];
        foreach ($raw as $r) {
            $s = $r['status'];
            $byStatus[$s] = (int)$r['c'];
        }

        // --- Detail orders untuk tabel
        $stmt = $pdo->prepare("
      SELECT o.id, o.created_at, c.name AS customer, o.product, o.quantity, 
             o.status, o.deadline, o.total_price
      FROM orders o
      LEFT JOIN customers c ON c.id=o.customer_id
      WHERE o.created_at >= ? AND o.created_at < ?
      ORDER BY o.created_at DESC
      LIMIT 200
    ");
        $stmt->execute([$start, $end]);
        $orders = $stmt->fetchAll();

        // --- Bikin grafik statis (PNG base64)
        $chartBase64 = $this->makeStatusBarChartBase64($byStatus);

        // --- Render HTML view ke string
        $monthName = $this->bulanIndo($month);

        ob_start();
        include __DIR__ . '/../views/report_order_bulanan_pdf.php';
        $html = ob_get_clean();

        // --- Dompdf
        $options = new \Dompdf\Options();
        $options->set('isRemoteEnabled', false); // offline OK, kita pakai base64
        $dompdf = new \Dompdf\Dompdf($options);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();
        $dompdf->stream("Laporan_Order_{$month}_{$year}.pdf", ["Attachment" => true]);
    }

    // helper nama bulan indo
    private function bulanIndo($m)
    {
        $arr = [1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
        return $arr[(int)$m] ?? '';
    }
    private function makeStatusBarChartBase64(array $byStatus)
    {
        $labels = ['admin', 'design', 'vendor', 'ready', 'picked'];
        $values = [];
        foreach ($labels as $k) $values[] = (int)($byStatus[$k] ?? 0);

        $w = 700;
        $h = 260;
        $img = imagecreatetruecolor($w, $h);

        $white = imagecolorallocate($img, 255, 255, 255);
        $black = imagecolorallocate($img, 0, 0, 0);
        $gray  = imagecolorallocate($img, 220, 220, 220);

        imagefilledrectangle($img, 0, 0, $w, $h, $white);

        // area chart
        $padL = 60;
        $padR = 20;
        $padT = 20;
        $padB = 60;
        $cw = $w - $padL - $padR;
        $ch = $h - $padT - $padB;

        // sumbu
        imageline($img, $padL, $padT, $padL, $padT + $ch, $black);
        imageline($img, $padL, $padT + $ch, $padL + $cw, $padT + $ch, $black);

        $max = max($values);
        if ($max < 1) $max = 1;

        // grid y (5 garis)
        for ($i = 0; $i <= 5; $i++) {
            $y = $padT + (int)($ch * ($i / 5));
            imageline($img, $padL, $y, $padL + $cw, $y, $gray);
            $val = (int)round($max * (1 - $i / 5));
            imagestring($img, 2, 5, $y - 7, (string)$val, $black);
        }

        // bar
        $barCount = count($labels);
        $gap = 18;
        $barW = (int)(($cw - ($gap * ($barCount + 1))) / $barCount);
        $x = $padL + $gap;

        // warna bar per status (mirip badge kamu)
        $colors = [
            'admin'  => imagecolorallocate($img, 108, 117, 125),
            'design' => imagecolorallocate($img, 13, 110, 253),
            'vendor' => imagecolorallocate($img, 255, 193, 7),
            'ready'  => imagecolorallocate($img, 25, 135, 84),
            'picked' => imagecolorallocate($img, 33, 37, 41),
        ];

        foreach ($labels as $i => $k) {
            $v = $values[$i];
            $bh = (int)round(($v / $max) * $ch);
            $y1 = $padT + $ch;
            $y0 = $y1 - $bh;

            imagefilledrectangle($img, $x, $y0, $x + $barW, $y1, $colors[$k] ?? $black);
            imagerectangle($img, $x, $y0, $x + $barW, $y1, $black);

            // label bawah
            imagestring($img, 2, $x, $padT + $ch + 8, $k, $black);
            // angka di atas bar
            imagestring($img, 2, $x + (int)($barW / 2) - 4, max($y0 - 14, 2), (string)$v, $black);

            $x += $barW + $gap;
        }

        ob_start();
        imagepng($img);
        $pngData = ob_get_clean();
        imagedestroy($img);

        return base64_encode($pngData);
    }
    public function feeDesainerBulanan()
    {
        require_login();
        global $pdo;

        $month = (int)($_GET['month'] ?? date('m'));
        $year  = (int)($_GET['year'] ?? date('Y'));

        $start = sprintf('%04d-%02d-01', $year, $month);
        $end   = date('Y-m-d', strtotime("$start +1 month"));

        // =========================
        // A) Rekap per desainer
        // =========================
        $stmt = $pdo->prepare("
        SELECT
            u.id,
            u.name,
            COUNT(o.id) AS total_order,
            COALESCE(SUM(o.designer_fee),0) AS total_fee,
            COALESCE(AVG(o.designer_fee),0) AS avg_fee
        FROM orders o
        JOIN users u ON u.id = o.assigned_designer
        WHERE u.role='designer'
          AND o.created_at >= ? AND o.created_at < ?
          AND o.designer_fee IS NOT NULL
          AND o.designer_fee > 0
        GROUP BY u.id, u.name
        ORDER BY total_fee DESC
    ");
        $stmt->execute([$start, $end]);
        $rows = $stmt->fetchAll();

        // data grafik batang: fee per desainer
        $labels = [];
        $dataFee = [];
        foreach ($rows as $r) {
            $labels[] = $r['name'];
            $dataFee[] = (int)$r['total_fee'];
        }

        // =========================
        // B) Rekap mingguan dalam bulan tsb
        // (week-of-month: 1..5/6)
        // =========================
        $stmt = $pdo->prepare("
        SELECT
          (WEEK(o.created_at, 1) - WEEK(?, 1) + 1) AS week_in_month,
          COUNT(*) AS total_order,
          COALESCE(SUM(o.designer_fee),0) AS total_fee
        FROM orders o
        WHERE o.created_at >= ? AND o.created_at < ?
          AND o.designer_fee IS NOT NULL
          AND o.designer_fee > 0
        GROUP BY week_in_month
        ORDER BY week_in_month ASC
    ");
        $stmt->execute([$start, $start, $end]);
        $weekly = $stmt->fetchAll();

        // total bulan (buat card ringkas)
        $totalFeeMonth = 0;
        $totalOrderMonth = 0;
        foreach ($rows as $r) {
            $totalFeeMonth += (float)$r['total_fee'];
            $totalOrderMonth += (int)$r['total_order'];
        }

        view('report_fee_desainer_bulanan', compact(
            'month',
            'year',
            'start',
            'end',
            'rows',
            'labels',
            'dataFee',
            'weekly',
            'totalFeeMonth',
            'totalOrderMonth'
        ));
    }
}
