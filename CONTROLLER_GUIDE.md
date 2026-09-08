# Controller Usage Guide (Normal Form & AJAX Requests)

This guide explains how to create and use Controllers in the **Attendance Management System** for both **Standard PHP Form Submissions** and **AJAX / `fetch()` Requests**.

---

## 📁 Directory Structure

Controllers live in `includes/controllers/`:

```text
Attendance-_Management_System/
├── includes/
│   ├── core/
│   │   └── Router.php             <-- Registers URLs and maps them to controllers
│   ├── controllers/               <-- Your Controller Classes go here
│   │   ├── AuthController.php
│   │   ├── UserController.php
│   │   └── AttendanceController.php
│   └── views/                     <-- Presentation / HTML templates
│       ├── auth/login.php
│       ├── users/list.php
│       └── attendance/scan.php
├── assets/
│   └── js/
│       └── app.js                 <-- External JS files (uses window.url())
└── index.php                      <-- Front controller entry point
```

---

## 🚦 How to Register a Controller in [Router.php](file:///c:/clients/Attendance-_Management_System/includes/core/Router.php)

In `includes/core/Router.php`, specify the route and point it to `'ControllerName@methodName'`:

```php
private static array $routes = [
    // Standard Pages & Normal Form Handlers
    '/users'                 => 'UserController@index',
    '/users/create'          => 'UserController@create',
    '/users/store'           => 'UserController@store',

    // AJAX / API Endpoints
    '/api/attendance/scan'   => 'AttendanceController@apiScan',
    '/api/users/delete'      => 'UserController@apiDelete',
];
```

---

## 📝 Pattern 1: Standard PHP Form Submission (Page Reload & Redirect)

Use this when you want a standard HTML form submission that processes data on the server and redirects to a new page.

### 1. The View (`includes/views/users/create.php`)
```html
<?php $page_title = 'Create User'; ?>
<?php include __DIR__ . '/../partials/header.php'; ?>

<main class="p-6 bg-surface">
  <h1 class="text-2xl font-bold text-text-primary mb-4">Create New User</h1>

  <!-- Standard Form: Action uses url() helper and method POST -->
  <form action="<?= url('users/store') ?>" method="POST" class="bg-white p-6 rounded-lg shadow-card max-w-md">
    <div class="mb-4">
      <label class="form-label">Full Name</label>
      <input type="text" name="name" required class="form-input" placeholder="e.g. Maria Santos">
    </div>

    <div class="mb-4">
      <label class="form-label">Email Address</label>
      <input type="email" name="email" required class="form-input" placeholder="e.g. maria@bestlink.edu.ph">
    </div>

    <div class="mb-6">
      <label class="form-label">Role</label>
      <select name="role" class="form-input form-select">
        <option value="Teacher">Teacher</option>
        <option value="Student">Student</option>
        <option value="Parent">Parent</option>
      </select>
    </div>

    <div class="flex gap-2 justify-end">
      <a href="<?= url('users') ?>" class="btn btn-secondary">Cancel</a>
      <button type="submit" class="btn btn-primary">Save User</button>
    </div>
  </form>
</main>

<?php include __DIR__ . '/../partials/footer.php'; ?>
```

### 2. The Controller (`includes/controllers/UserController.php`)
```php
<?php
// includes/controllers/UserController.php

class UserController {
    // GET /users - Display user list
    public function index() {
        // 1. Fetch users (e.g. from Database)
        $users = [
            ['id' => 1, 'name' => 'Santos, Dr. Admin', 'role' => 'Admin'],
            ['id' => 2, 'name' => 'Cruz, Mr. B.',      'role' => 'Teacher'],
        ];

        // 2. Load View
        include dirname(__DIR__) . '/views/users/list.php';
    }

    // GET /users/create - Display create form
    public function create() {
        include dirname(__DIR__) . '/views/users/create.php';
    }

    // POST /users/store - Process form submission
    public function store() {
        // 1. Read POST data
        $name  = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $role  = trim($_POST['role'] ?? 'Student');

        // 2. Validation
        if (empty($name) || empty($email)) {
            // Redirect back with error or show message
            header('Location: ' . url('users/create?error=missing_fields'));
            exit;
        }

        // 3. Database operation (Insert record)
        // e.g.: $db->query("INSERT INTO users ...");

        // 4. Redirect to User List on success
        header('Location: ' . url('users?created=1'));
        exit;
    }
}
```

---

## ⚡ Pattern 2: AJAX / Fetch Request (No Page Reload)

Use this for live updates, barcode/RFID scanners, modal actions, and dynamic filtering.

### 1. The View (`includes/views/attendance/scan.php`)
```html
<?php $page_title = 'Live RFID / QR Scan'; ?>
<?php include __DIR__ . '/../partials/header.php'; ?>

<main class="p-6 bg-surface">
  <h1 class="text-2xl font-bold text-text-primary mb-4">Live Scanner</h1>

  <form id="scan-form" class="flex gap-2 max-w-md">
    <input type="text" id="scan-input" class="form-input" placeholder="Scan Barcode / RFID..." autofocus required>
    <button type="submit" class="btn btn-primary">Log Scan</button>
  </form>

  <div id="scan-feedback" class="mt-4"></div>
</main>

<?php include __DIR__ . '/../partials/footer.php'; ?>
<script src="<?= url('assets/js/scan.js') ?>"></script>
```

### 2. The External JavaScript (`assets/js/scan.js`)
```javascript
// assets/js/scan.js
document.getElementById('scan-form').addEventListener('submit', async function(e) {
  e.preventDefault();

  const input = document.getElementById('scan-input');
  const feedback = document.getElementById('scan-feedback');
  const studentId = input.value.trim();
  if (!studentId) return;

  try {
    // 💡 window.url() automatically formats the correct base path
    const response = await fetch(url('api/attendance/scan'), {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-Requested-With': 'XMLHttpRequest'
      },
      body: JSON.stringify({ student_id: studentId })
    });

    const data = await response.json();

    if (data.status === 'success') {
      feedback.innerHTML = `<div class="p-3 bg-emerald-100 text-emerald-800 rounded font-medium">✓ ${data.message}: <strong>${data.student_name}</strong></div>`;
      input.value = '';
    } else {
      feedback.innerHTML = `<div class="p-3 bg-rose-100 text-rose-800 rounded font-medium">⚠ ${data.message}</div>`;
    }
  } catch (err) {
    console.error('Fetch error:', err);
    feedback.innerHTML = `<div class="p-3 bg-rose-100 text-rose-800 rounded">Network error occurred.</div>`;
  }
});
```

### 3. The Controller (`includes/controllers/AttendanceController.php`)
```php
<?php
// includes/controllers/AttendanceController.php

class AttendanceController {
    // POST /api/attendance/scan
    public function apiScan() {
        // 1. Always set JSON Header for API endpoints
        header('Content-Type: application/json');

        // 2. Read incoming JSON body
        $raw = file_get_contents('php://input');
        $body = json_decode($raw, true);
        $studentId = trim($body['student_id'] ?? '');

        if (empty($studentId)) {
            http_response_code(400);
            echo json_encode([
                'status'  => 'error',
                'message' => 'Student ID is required.'
            ]);
            exit;
        }

        // 3. Database lookup & save logic
        $studentName = ($studentId === 'BCP-001') ? 'Juan Dela Cruz' : 'Student ' . $studentId;

        // 4. Return JSON response and exit
        echo json_encode([
            'status'       => 'success',
            'message'      => 'Attendance recorded',
            'student_name' => $studentName,
            'time'         => date('h:i:s A')
        ]);
        exit;
    }
}
```

---

## 🔀 Pattern 3: Dual-Mode Controller Action (Handles BOTH AJAX and Normal Form)

You can write a single controller method that checks if the incoming request is AJAX or a traditional form:

```php
<?php
// includes/controllers/UserController.php

class UserController {
    public function save() {
        $name = trim($_POST['name'] ?? '');
        $isAjax = (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
                   strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') 
                   || (strpos($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json') !== false);

        // Process logic...
        $saved = true;

        if ($isAjax) {
            // Return JSON for AJAX
            header('Content-Type: application/json');
            echo json_encode([
                'status'  => $saved ? 'success' : 'error',
                'message' => $saved ? 'User saved successfully.' : 'Failed to save user.'
            ]);
            exit;
        }

        // Traditional redirect for normal browser form
        if ($saved) {
            header('Location: ' . url('users?saved=1'));
        } else {
            header('Location: ' . url('users/create?error=1'));
        }
        exit;
    }
}
```

---

## 📌 Summary Quick-Reference

| Task | View / JS | Router (`Router.php`) | Controller Action |
| :--- | :--- | :--- | :--- |
| **Render Page** | `<a href="<?= url('users') ?>">` | `'/users' => 'UserController@index'` | `include 'includes/views/users/list.php';` |
| **Normal Form** | `<form action="<?= url('users/store') ?>" method="POST">` | `'/users/store' => 'UserController@store'` | Read `$_POST`, then `header('Location: ' . url('users')); exit;` |
| **AJAX Request** | `fetch(url('api/...'), { method: 'POST' })` | `'/api/...' => 'Controller@method'` | `header('Content-Type: application/json'); echo json_encode(...); exit;` |
