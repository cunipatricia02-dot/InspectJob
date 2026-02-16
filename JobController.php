<?php
namespace App\Controllers;

use App\Models\Job;
use App\Models\Assignment;
use App\Services\JWTService;
use App\Services\TimezoneService;

class JobController {
    private $jobModel;
    private $assignmentModel;

    public function __construct() {
        $this->jobModel = new Job();
        $this->assignmentModel = new Assignment();
    }

    public function list() {
        $user = JWTService::requireAuth();

        $jobs = $this->jobModel->findAll();

        // Convert scheduled times to inspector's timezone for assigned jobs
        foreach ($jobs as &$job) {
            $jobDetails = $this->jobModel->getWithAssignment($job['id']);
            if ($jobDetails['scheduled_at_utc']) {
                $job['scheduled_at_local'] = TimezoneService::convertFromUTC(
                    $jobDetails['scheduled_at_utc'],
                    $user->location
                );
                $job['assignment'] = [
                    'inspector_name' => $jobDetails['inspector_name'],
                    'scheduled_at' => $jobDetails['scheduled_at'],
                    'completed_at' => $jobDetails['completed_at']
                ];
            }
        }

        http_response_code(200);
        echo json_encode(['jobs' => $jobs]);
    }

    public function create() {
        $user = JWTService::requireAuth();

        $input = json_decode(file_get_contents('php://input'), true);

        if (!isset($input['title']) || !isset($input['description'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Title and description are required']);
            return;
        }

        if ($this->jobModel->create($input['title'], $input['description'])) {
            http_response_code(200);
            echo json_encode(['message' => 'Job created successfully']);
        } else {
            http_response_code(500);
            echo json_encode(['error' => 'Failed to create job']);
        }
    }

    public function assign($jobId) {
        $user = JWTService::requireAuth();

        $input = json_decode(file_get_contents('php://input'), true);

        if (!isset($input['scheduled_at'])) {
            http_response_code(400);
            echo json_encode(['error' => 'scheduled_at is required']);
            return;
        }

        // Check if job exists
        $job = $this->jobModel->findById($jobId);
        if (!$job) {
            http_response_code(404);
            echo json_encode(['error' => 'Job not found']);
            return;
        }

        // Check if job is already assigned
        if ($job['status'] !== 'available') {
            http_response_code(400);
            echo json_encode(['error' => 'Job is not available']);
            return;
        }

        try {
            // Convert scheduled time from inspector's timezone to UTC
            $scheduledAtUtc = TimezoneService::convertToUTC($input['scheduled_at'], $user->location);

            // Create assignment
            if ($this->assignmentModel->create($jobId, $user->id, $input['scheduled_at'], $scheduledAtUtc)) {
                // Update job status
                $this->jobModel->updateStatus($jobId, 'assigned');

                http_response_code(200);
                echo json_encode([
                    'message' => 'Job assigned successfully',
                    'scheduled_at_local' => $input['scheduled_at'],
                    'scheduled_at_utc' => $scheduledAtUtc
                ]);
            } else {
                http_response_code(500);
                echo json_encode(['error' => 'Failed to assign job']);
            }
        } catch (\Exception $e) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid date format: ' . $e->getMessage()]);
        }
    }

    public function complete($jobId) {
        $user = JWTService::requireAuth();

        $input = json_decode(file_get_contents('php://input'), true);

        if (!isset($input['assessment'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Assessment is required']);
            return;
        }

        // Check if job exists
        $job = $this->jobModel->findById($jobId);
        if (!$job) {
            http_response_code(404);
            echo json_encode(['error' => 'Job not found']);
            return;
        }

        // Check if job is assigned
        if ($job['status'] !== 'assigned') {
            http_response_code(400);
            echo json_encode(['error' => 'Job is not assigned']);
            return;
        }

        // Check if this inspector is assigned to this job
        $assignment = $this->assignmentModel->findByJobId($jobId);
        if (!$assignment || $assignment['inspector_id'] != $user->id) {
            http_response_code(403);
            echo json_encode(['error' => 'You are not assigned to this job']);
            return;
        }

        // Complete the assignment
        if ($this->assignmentModel->complete($jobId, $input['assessment'])) {
            // Update job status
            $this->jobModel->updateStatus($jobId, 'completed');

            http_response_code(200);
            echo json_encode(['message' => 'Job completed successfully']);
        } else {
            http_response_code(500);
            echo json_encode(['error' => 'Failed to complete job']);
        }
    }
}
