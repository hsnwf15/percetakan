<?php
// Basic config
if (session_status() === PHP_SESSION_NONE) {
    // Set custom session path yang bisa ditulis
    $sessionPath = __DIR__ . '/../storage/sessions';
    if (!is_dir($sessionPath)) {
        mkdir($sessionPath, 0777, true);
    }
    session_save_path($sessionPath);
    session_start();
}

mb_internal_encoding("UTF-8");
date_default_timezone_set("Asia/Makassar");
setlocale(LC_TIME, 'id_ID.UTF-8');

// Adjust these for your local DB
$CFG = [
    "DB_HOST" => "127.0.0.1",
    "DB_NAME" => "percetakan",
    "DB_USER" => "percetakan",
    "DB_PASS" => "percetakan123",
];

// Detect BASE_URL automatically (works for localhost/xampp)
$scheme =
    !empty($_SERVER["HTTPS"]) && $_SERVER["HTTPS"] !== "off" ? "https" : "http";
$host = $_SERVER["HTTP_HOST"] ?? "localhost";
$script = dirname($_SERVER["SCRIPT_NAME"] ?? "/public/index.php");
$base = rtrim($scheme . "://" . $host . $script, "/");
if (!defined("BASE_URL")) {
    define("BASE_URL", $base);
}

// Dev error reporting (disable on prod)
ini_set("display_errors", 1);
error_reporting(E_ALL);
?>
