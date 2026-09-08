<?php
/**
 * Sample Controller — includes/controllers/UserController.php
 * Demonstrates both normal view rendering and API/AJAX endpoints.
 */

class UserController {
    /**
     * GET /users — Render the user management list view
     */
    public function index(): void {
        $page_title = 'User Management';
        include dirname(__DIR__) . '/views/users/list.php';
    }

    /**
     * GET /users/create — Render create user form view
     */
    public function create(): void {
        $page_title = 'Create User';
        include dirname(__DIR__) . '/views/users/create.php';
    }

    /**
     * POST /users/store — Handle standard HTML form submission
     */
    public function store(): void {
        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $role = trim($_POST['role'] ?? 'Student');

        // Validation & DB insert would happen here...

        // Redirect back to user list
        header('Location: ' . url('users'));
        exit;
    }

    /**
     * POST /api/users/delete — Handle AJAX delete request
     */
    public function apiDelete(): void {
        header('Content-Type: application/json');

        $input = json_decode(file_get_contents('php://input'), true);
        $userId = $input['id'] ?? null;

        if (!$userId) {
            http_response_code(400);
            echo json_encode(['status' => 'error', 'message' => 'User ID is required.']);
            exit;
        }

        // DB deletion logic would happen here...

        echo json_encode([
            'status'  => 'success',
            'message' => "User #{$userId} deleted successfully."
        ]);
        exit;
    }
}
