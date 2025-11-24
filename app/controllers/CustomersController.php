<?php
require_once __DIR__ . "/../db.php";
require_once __DIR__ . "/../helpers.php";
require_once __DIR__ . "/../auth.php";

class CustomersController
{
    public function index()
    {
        require_login();
        require_role(["admin", "designer", "owner"]); // semua boleh lihat
        global $pdo;
        $q = trim($_GET["q"] ?? "");
        if ($q !== "") {
            $stmt = $pdo->prepare(
                "SELECT * FROM customers WHERE name LIKE ? OR phone LIKE ? ORDER BY name LIMIT 200",
            );
            $like = "%{$q}%";
            $stmt->execute([$like, $like, $like]);
        } else {
            $stmt = $pdo->query(
                "SELECT * FROM customers ORDER BY name LIMIT 200",
            );
        }
        $rows = $stmt->fetchAll();
        view("customers_list", compact("rows", "q"));
    }

    public function create()
    {
        require_login();
        require_role(["admin"]); // hanya admin yang boleh tambah/edit/hapus
        $customer = ["id" => null, "name" => "", "phone" => "", "email" => ""];
        view("customers_form", compact("customer"));
    }

    public function store()
    {
        require_login();
        require_role(["admin"]);
        csrf_check();
        global $pdo;
        $sql = "INSERT INTO customers(name,phone) VALUES (?,?)";
        $pdo->prepare($sql)->execute([
            trim($_POST["name"] ?? ""),
            trim($_POST["phone"] ?? ""),
        ]);
        flash("Pelanggan ditambahkan.");
        redirect("?r=customers/index");
    }

    public function edit()
    {
        require_login();
        require_role(["admin"]);
        global $pdo;
        $id = (int) ($_GET["id"] ?? 0);
        $stmt = $pdo->prepare("SELECT * FROM customers WHERE id=?");
        $stmt->execute([$id]);
        $customer = $stmt->fetch();
        if (!$customer) {
            http_response_code(404);
            die("Pelanggan tidak ditemukan");
        }
        view("customers_form", compact("customer"));
    }

    public function update()
    {
        require_login();
        require_role(["admin"]);
        csrf_check();
        global $pdo;
        $id = (int) $_POST["id"];
        $sql = "UPDATE customers SET name=?, phone=? WHERE id=?";
        $pdo->prepare($sql)->execute([
            trim($_POST["name"] ?? ""),
            trim($_POST["phone"] ?? ""),
            $id,
        ]);
        flash("Pelanggan diperbarui.");
        redirect("?r=customers/index");
    }

    public function destroy()
    {
        require_login();
        require_role(["admin"]);
        csrf_check();
        global $pdo;
        $id = (int) $_POST["id"];
        $pdo->prepare("DELETE FROM customers WHERE id=?")->execute([$id]);
        flash("Pelanggan dihapus.");
        redirect("?r=customers/index");
    }
}
