<?php
namespace App\Models;

use App\Database;
use PDO;

class Job {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    public function create($title, $description) {
        $stmt = $this->db->prepare(
            "INSERT INTO jobs (title, description, status) VALUES (?, ?, 'available')"
        );
        return $stmt->execute([$title, $description]);
    }

    public function findAll() {
        $stmt = $this->db->query("SELECT * FROM jobs ORDER BY created_at DESC");
        return $stmt->fetchAll();
    }

    public function findById($id) {
        $stmt = $this->db->prepare("SELECT * FROM jobs WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    public function updateStatus($id, $status) {
        $stmt = $this->db->prepare("UPDATE jobs SET status = ? WHERE id = ?");
        return $stmt->execute([$status, $id]);
    }

    public function getWithAssignment($id) {
        $stmt = $this->db->prepare("
            SELECT j.*, 
                   a.id as assignment_id,
                   a.inspector_id,
                   a.scheduled_at,
                   a.scheduled_at_utc,
                   a.completed_at,
                   a.assessment,
                   i.name as inspector_name,
                   i.email as inspector_email,
                   i.location as inspector_location
            FROM jobs j
            LEFT JOIN assignments a ON j.id = a.job_id
            LEFT JOIN inspectors i ON a.inspector_id = i.id
            WHERE j.id = ?
        ");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }
}
