<?php
function view($name, $data = [])
{
    // render child view ke buffer
    extract($data);
    ob_start();
    include __DIR__ . "/views/{$name}.php";
    $__child = ob_get_clean();

    // tampilkan layout + child
    include __DIR__ . "/views/layout.php";
}
function redirect($path)
{
    $url = str_starts_with($path, "http")
        ? $path
        : BASE_URL . "/" . ltrim($path, "/");
    header("Location: " . $url);
    exit();
}
function url($route)
{
    return BASE_URL . "/?r=" . $route;
}
function flash($msg = null)
{
    if ($msg === null) {
        $m = $_SESSION["__flash"] ?? null;
        unset($_SESSION["__flash"]);
        return $m;
    }
    $_SESSION["__flash"] = $msg;
}
function csrf_token()
{
    if (empty($_SESSION["csrf"])) {
        $_SESSION["csrf"] = bin2hex(random_bytes(16));
    }
    return $_SESSION["csrf"];
}
function csrf_field()
{
    $t = csrf_token();
    echo '<input type="hidden" name="_token" value="' .
        htmlspecialchars($t) .
        '">';
}
function csrf_check()
{
    if (($_POST["_token"] ?? "") !== ($_SESSION["csrf"] ?? "")) {
        http_response_code(419);
        die("CSRF token mismatch");
    }
}
function h($s)
{
    return htmlspecialchars((string) $s, ENT_QUOTES, "UTF-8");
}

if (!function_exists("status_badge_class")) {
    function status_badge_class($s)
    {
        return [
            "admin" => "secondary",
            "design" => "primary",
            "vendor" => "warning",
            "ready" => "success",
            "picked" => "dark",
        ][$s] ?? "secondary";
    }
}

if (!function_exists("idr")) {
    function idr($n)
    {
        return "Rp " . number_format((float) $n, 0, ",", ".");
    }
}

function chart_bar_data_uri(array $labels, array $values, int $w = 900, int $h = 320): string
{
    if (!function_exists('imagecreatetruecolor')) {
        return '';
    }

    // Normalisasi input
    $n = min(count($labels), count($values));
    $labels = array_slice($labels, 0, $n);
    $values = array_map('floatval', array_slice($values, 0, $n));

    // Buat canvas
    $im = imagecreatetruecolor($w, $h);

    // Warna
    $bg   = imagecolorallocate($im, 255, 255, 255);
    $axis = imagecolorallocate($im, 60, 60, 60);
    $grid = imagecolorallocate($im, 220, 220, 220);
    $bar  = imagecolorallocate($im, 60, 110, 113); // #3C6E71 vibe
    $txt  = imagecolorallocate($im, 30, 30, 30);

    imagefill($im, 0, 0, $bg);

    // Padding area chart
    $padL = 60;
    $padR = 20;
    $padT = 20;
    $padB = 60;
    $chartW = $w - $padL - $padR;
    $chartH = $h - $padT - $padB;

    // Nilai max (hindari 0 biar sumbu tetap ada)
    $max = max($values ?: [0]);
    if ($max <= 0) $max = 1;

    // Grid horizontal (5 garis)
    $gridLines = 5;
    for ($i = 0; $i <= $gridLines; $i++) {
        $y = (int)($padT + ($chartH * $i / $gridLines));
        imageline($im, $padL, $y, $padL + $chartW, $y, $grid);

        // label y (dibulatkan)
        $val = (int)round($max * (1 - $i / $gridLines));
        imagestring($im, 2, 5, $y - 7, (string)$val, $txt);
    }

    // Axis
    imageline($im, $padL, $padT, $padL, $padT + $chartH, $axis);
    imageline($im, $padL, $padT + $chartH, $padL + $chartW, $padT + $chartH, $axis);

    // Bar
    $count = max(1, $n);
    $gap = 8;
    $barW = (int)max(6, floor(($chartW - ($gap * ($count - 1))) / $count));

    for ($i = 0; $i < $n; $i++) {
        $x1 = (int)($padL + ($i * ($barW + $gap)));
        $x2 = $x1 + $barW;

        $vh = (int)round(($values[$i] / $max) * $chartH);
        $y2 = $padT + $chartH;
        $y1 = $y2 - $vh;

        imagefilledrectangle($im, $x1, $y1, $x2, $y2, $bar);

        // Label x (hari)
        $lab = (string)$labels[$i];
        imagestring($im, 2, $x1 + 2, $padT + $chartH + 8, $lab, $txt);
    }

    // Output PNG ke memory
    ob_start();
    imagepng($im);
    $png = ob_get_clean();

    imagedestroy($im);

    return 'data:image/png;base64,' . base64_encode($png);
}
