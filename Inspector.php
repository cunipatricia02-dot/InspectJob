<?php
namespace App\Models;

use App\Database;
use PDO;

class Inspector {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    public function create($email, $password, $name, $location) {
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

        $stmt = $this->db->prepare(
            "INSERT INTO inspectors (email, password, name, location) VALUES (?, ?, ?, ?)"
        );

        return $stmt->execute([$email, $hashedPassword, $name, $location]);
    }

    public function findByEmail($email) {
        $stmt = $this->db->prepare("SELECT * FROM inspectors WHERE email = ?");
        $stmt->execute([$email]);
        return $stmt->fetch();
    }

    public function findById($id) {
        $stmt = $this->db->prepare("SELECT id, email, name, location, created_at FROM inspectors WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    public function verifyPassword($password, $hashedPassword) {
        return password_verify($password, $hashedPassword);
    }
}
