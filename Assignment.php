<?php
namespace App\Models;

use App\Database;
use PDO;

class Assignment {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    public function create($jobId, $inspectorId, $scheduledAt, $scheduledAtUtc) {
        $stmt = $this->db->prepare(
            "INSERT INTO assignments (job_id, inspector_id, scheduled_at, scheduled_at_utc) 
             VALUES (?, ?, ?, ?)"
        );
        return $stmt->execute([$jobId, $inspectorId, $scheduledAt, $scheduledAtUtc]);
    }

    public function findByJobId($jobId) {
        $stmt = $this->db->prepare("SELECT * FROM assignments WHERE job_id = ?");
        $stmt->execute([$jobId]);
        return $stmt->fetch();
    }

    public function complete($jobId, $assessment) {
        $stmt = $this->db->prepare(
            "UPDATE assignments 
             SET assessment = ?, completed_at = NOW() 
             WHERE job_id = ?"
        );
        return $stmt->execute([$assessment, $jobId]);
    }

    public function findByInspectorId($inspectorId) {
        $stmt = $this->db->prepare("
            SELECT a.*, j.title, j.description, j.status
            FROM assignments a
            INNER JOIN jobs j ON a.job_id = j.id
            WHERE a.inspector_id = ?
            ORDER BY a.scheduled_at_utc DESC
        ");
        $stmt->execute([$inspectorId]);
        return $stmt->fetchAll();
    }
}
