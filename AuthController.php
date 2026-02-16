<?php
namespace App\Controllers;

use App\Models\Inspector;
use App\Services\JWTService;

class AuthController {
    private $inspectorModel;

    public function __construct() {
        $this->inspectorModel = new Inspector();
    }

    public function register() {
        $input = json_decode(file_get_contents('php://input'), true);

        // Validate required fields
        $required = ['email', 'password', 'name', 'location'];
        foreach ($required as $field) {
            if (!isset($input[$field]) || empty($input[$field])) {
                http_response_code(400);
                echo json_encode(['error' => "Field '$field' is required"]);
                return;
            }
        }

        // Validate location
        $validLocations = ['UK', 'MEXICO', 'INDIA'];
        if (!in_array($input['location'], $validLocations)) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid location. Must be UK, MEXICO, or INDIA']);
            return;
        }

        // Validate email format
        if (!filter_var($input['email'], FILTER_VALIDATE_EMAIL)) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid email format']);
            return;
        }

        // Check if email already exists
        if ($this->inspectorModel->findByEmail($input['email'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Email already registered']);
            return;
        }

        // Create inspector
        if ($this->inspectorModel->create(
            $input['email'],
            $input['password'],
            $input['name'],
            $input['location']
        )) {
            http_response_code(200);
            echo json_encode(['message' => 'Registered successfully']);
        } else {
            http_response_code(500);
            echo json_encode(['error' => 'Registration failed']);
        }
    }

    public function login() {
        $input = json_decode(file_get_contents('php://input'), true);

        if (!isset($input['email']) || !isset($input['password'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Email and password are required']);
            return;
        }

        $inspector = $this->inspectorModel->findByEmail($input['email']);

        if (!$inspector || !$this->inspectorModel->verifyPassword($input['password'], $inspector['password'])) {
            http_response_code(401);
            echo json_encode(['error' => 'Invalid credentials']);
            return;
        }

        // Generate JWT token
        $payload = [
            'id' => $inspector['id'],
            'email' => $inspector['email'],
            'name' => $inspector['name'],
            'location' => $inspector['location']
        ];

        $token = JWTService::encode($payload);

        http_response_code(200);
        echo json_encode([
            'token' => $token,
            'inspector' => [
                'id' => $inspector['id'],
                'email' => $inspector['email'],
                'name' => $inspector['name'],
                'location' => $inspector['location']
            ]
        ]);
    }
}
