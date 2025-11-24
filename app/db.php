<?php
require_once __DIR__ . "/config.php";
try {
    $pdo = new PDO(
        "mysql:host={$CFG["DB_HOST"]};dbname={$CFG["DB_NAME"]};charset=utf8mb4",
        $CFG["DB_USER"],
        $CFG["DB_PASS"],
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ],
    );
} catch (Throwable $e) {
    http_response_code(500);
    echo "DB connection failed: " . htmlspecialchars($e->getMessage());
    exit();
}
?>
