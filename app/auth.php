<?php
function current_user()
{
    return $_SESSION["user"] ?? null;
}
function require_login()
{
    if (!current_user()) {
        redirect("?r=auth/loginForm");
    }
}
function require_role($roles)
{
    require_login();
    $u = current_user();
    if (!in_array($u["role"] ?? "guest", (array) $roles, true)) {
        http_response_code(403);
        die("Forbidden");
    }
}
?>
