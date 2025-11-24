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

?>
