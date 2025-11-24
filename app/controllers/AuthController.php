<?php
require_once __DIR__ . "/../db.php";
require_once __DIR__ . "/../helpers.php";
require_once __DIR__ . "/../auth.php";

class AuthController
{
    public function loginForm()
    {
        if (current_user()) {
            redirect("?r=orders/index");
        }
        view("login", []);
    }
    public function login()
    {
        csrf_check();
        global $pdo;
        $email = trim($_POST["email"] ?? "");
        $pass = $_POST["password"] ?? "";
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email=? LIMIT 1");
        $stmt->execute([$email]);
        $u = $stmt->fetch();
        if ($u && password_verify($pass, $u["password"])) {
            $_SESSION["user"] = [
                "id" => $u["id"],
                "name" => $u["name"],
                "role" => $u["role"],
            ];
            redirect("?r=orders/index");
        }
        flash("Email atau password salah.");
        redirect("?r=auth/loginForm");
    }
    public function logout()
    {
        $_SESSION = [];
        session_destroy();
        redirect("?r=auth/loginForm");
    }
}
?>
