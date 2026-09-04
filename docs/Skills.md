# Skills & Development Procedures
# Library Attendance Monitoring System (LAMS)

## 1. Frontend Development

### 1.1 Tailwind CSS Workflow
```bash
# Development: watch for changes
npx tailwindcss -i ./assets/css/input.css -o ./assets/css/output.css --watch

# Production: minify
npx tailwindcss -i ./assets/css/input.css -o ./assets/css/output.css --minify
```

**Guidelines:**
- Use utility classes directly in HTML; avoid custom CSS when possible
- Keep components consistent: buttons, cards, forms, tables
- Use `dark:` variants only if explicitly requested
- Responsive breakpoints: `sm:`, `md:`, `lg:` — mobile-first

### 1.2 JavaScript Guidelines
- Use vanilla ES6+ (no jQuery)
- Modularize by feature: `scanner.js`, `dashboard.js`, `calendar.js`
- Use `fetch()` for all AJAX calls
- Always include CSRF token in headers:
```javascript
fetch('/api/endpoint.php', {
    method: 'POST',
    headers: { 'X-CSRF-Token': csrfToken },
    body: JSON.stringify(data)
});
```

### 1.3 QR Scanner Integration
```javascript
// scanner.js
import { Html5Qrcode } from "html5-qrcode";

const scanner = new Html5Qrcode("reader");
scanner.start(
    { facingMode: "environment" },
    { fps: 10, qrbox: { width: 250, height: 250 } },
    (decodedText) => { handleScan(decodedText); },
    (error) => { /* ignore scan errors */ }
);
```

## 2. Backend Development

### 2.1 PHP Guidelines
- Use PHP 8.2+ features: typed properties, named arguments, match expressions
- Strict typing: `declare(strict_types=1);` at top of every PHP file
- PSR-12 style guide (approximate; no formal linter required)
- One class per file, named identically to class

### 2.2 Database Access Pattern
```php
// Database.php - PDO wrapper
class Database {
    private static ?PDO $instance = null;

    public static function getInstance(): PDO {
        if (self::$instance === null) {
            $config = require __DIR__ . '/../../config.php';
            self::$instance = new PDO(
                "mysql:host={$config['db_host']};dbname={$config['db_name']};charset=utf8mb4",
                $config['db_user'],
                $config['db_pass'],
                [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
            );
        }
        return self::$instance;
    }
}
```

### 2.3 Model Pattern
```php
// Example: Attendance.php
class Attendance {
    private PDO $db;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    public function createEntry(int $userId, string $direction): array {
        $stmt = $this->db->prepare(
            "INSERT INTO attendance_records (user_id, record_date, entry_time, direction, method, status) 
             VALUES (:user_id, CURDATE(), CURTIME(), :direction, 'qr_scan', 'present')"
        );
        $stmt->execute(['user_id' => $userId, 'direction' => $direction]);
        return ['id' => $this->db->lastInsertId(), 'status' => 'present'];
    }
}
```

### 2.4 API Endpoint Pattern
```php
// api/attendance/scan.php
<?php
declare(strict_types=1);
require_once __DIR__ . '/../../includes/core/Auth.php';
require_once __DIR__ . '/../../includes/core/Response.php';
require_once __DIR__ . '/../../includes/models/Student.php';
require_once __DIR__ . '/../../includes/models/Attendance.php';

header('Content-Type: application/json');

try {
    $input = json_decode(file_get_contents('php://input'), true);

    // Validation
    if (empty($input['qr_code']) || empty($input['direction'])) {
        Response::error('Missing required fields', 400);
    }

    // Business logic
    $student = (new Student())->findByQrCode($input['qr_code']);
    if (!$student) {
        Response::error('Invalid QR code', 404);
    }

    $attendance = (new Attendance())->createEntry($student['user_id'], $input['direction']);

    Response::success('Attendance recorded', $attendance);

} catch (Exception $e) {
    error_log("[SCAN ERROR] " . $e->getMessage());
    Response::error('An error occurred', 500);
}
```

## 3. Database Development

### 3.1 Schema Changes
1. Create migration SQL file: `/migrations/YYYYMMDD_description.sql`
2. Test on local database
3. Document change in commit message
4. Apply to staging, then production

### 3.2 Indexing Strategy
- Add indexes on foreign keys automatically
- Add composite indexes for frequent query patterns:
  - `(user_id, record_date)` — attendance lookups
  - `(record_date, status)` — daily reports
  - `(status, created_at)` — pending alerts queue
- Use `EXPLAIN` to verify index usage before deployment

### 3.3 Query Optimization
- Avoid `SELECT *`; specify columns
- Use `LIMIT` for paginated results
- Use `COUNT(*)` with `INDEX` for dashboard summaries
- For analytics, consider materialized views (or summary tables) if query time > 3 seconds

## 4. API Development

### 4.1 REST-like Conventions
| Method | Endpoint | Action |
|--------|----------|--------|
| GET | /api/analytics/summary.php | Read data |
| POST | /api/attendance/scan.php | Create record |
| POST | /api/excuses/review.php | Update status |
| POST | /api/exports/attendance.php | Generate file |

### 4.2 Response Format
Always use `Response` helper:
```php
class Response {
    public static function success(string $message, array $data = [], int $code = 200): void {
        http_response_code($code);
        echo json_encode(['success' => true, 'message' => $message, 'data' => $data]);
        exit;
    }

    public static function error(string $message, int $code = 400, array $errors = []): void {
        http_response_code($code);
        echo json_encode(['success' => false, 'message' => $message, 'errors' => $errors]);
        exit;
    }
}
```

## 5. Authentication & Security

### 5.1 Adding a Protected Page
```php
<?php
require_once __DIR__ . '/../includes/core/Auth.php';
Auth::requireRole(['admin', 'staff']); // Redirects to login if not authenticated
?>
```

### 5.2 Form Security Checklist
- [ ] CSRF token included
- [ ] Server-side validation
- [ ] Output escaping for display
- [ ] Rate limiting if applicable
- [ ] Authorization check

## 6. Testing

### 6.1 Manual Testing Checklist
Before marking a feature complete:
- [ ] Test with valid input
- [ ] Test with missing required fields
- [ ] Test with invalid data types
- [ ] Test with duplicate data
- [ ] Test with unauthorized user
- [ ] Test with large dataset (1000+ rows)
- [ ] Test on mobile browser
- [ ] Test with JavaScript disabled (graceful degradation)

### 6.2 Test Data
Use `/scripts/seed_data.php` (to be created) for consistent test data:
- 50 students across 3 grades, 2 sections each
- 5 teachers
- 2 admins
- 30 days of attendance records

## 7. Debugging Methodology

### 7.1 Universal Debug Process
```
REPRODUCE
→ ISOLATE
→ FIX
→ TEST
→ VERIFY
```

### 7.2 Step-by-Step

**REPRODUCE**
- Document exact steps to trigger the bug
- Note browser, user role, data state
- Check if reproducible in incognito / different browser

**ISOLATE**
- Check browser console for JS errors
- Check Network tab for failed requests
- Check PHP error log (`logs/errors/`)
- Add targeted `error_log()` statements
- Use `var_dump()` only in development; never commit
- Verify database state matches expectations

**FIX**
- Make the smallest possible change
- Do not refactor unrelated code
- Ensure fix addresses root cause, not symptom

**TEST**
- Re-run reproduction steps
- Test edge cases around the fix
- Test that related features still work

**VERIFY**
- Confirm fix in staging environment
- Have another person (or AI) review the change
- Update documentation if behavior changed

### 7.3 Common Issues & Quick Checks

| Symptom | Check |
|---------|-------|
| White screen | PHP error log; `display_errors` setting |
| 500 on API | Response format; unhandled exception |
| Data not saving | Prepared statement params; DB connection |
| CSRF error | Session started? Token included? |
| Slow page | Query `EXPLAIN`; missing index; N+1 queries |
| Email not sending | SMTP credentials; cron job running; spam folder |
| QR not scanning | Camera permissions; lighting; QR code format |

## 8. Performance

### 8.1 Frontend
- Minify CSS/JS for production
- Use `defer` or `async` for non-critical scripts
- Lazy-load images if any

### 8.2 Backend
- Enable OPcache in production
- Use prepared statements (also improves performance via query plan caching)
- Paginate all list views (default 25 rows, max 100)
- Cache library settings in PHP array (loaded once per request)

### 8.3 Database
- Enable MySQL slow query log (threshold: 2 seconds)
- Archive attendance records older than 3 years
- Optimize tables monthly: `OPTIMIZE TABLE attendance_records`

## 9. Deployment

### 9.1 Pre-Deployment Checklist
- [ ] All tests pass
- [ ] No `var_dump()`, `die()`, or debug code in committed files
- [ ] `.env` configured for production
- [ ] Tailwind CSS compiled and minified
- [ ] Database migrations applied
- [ ] File permissions correct
- [ ] Cron jobs configured
- [ ] Backup script verified

### 9.2 Deployment Steps
1. `git pull` on production server
2. `composer install --no-dev --optimize-autoloader`
3. Apply any pending database migrations
4. Compile production CSS: `npx tailwindcss -i ... -o ... --minify`
5. Clear any application caches
6. Smoke test critical paths (login, scan, dashboard)

### 9.3 Rollback Plan
- Keep previous release in `/var/www/lams-previous/`
- Database: restore from pre-deployment backup if migration fails
- Switch Apache DocumentRoot to previous release if critical bug found

## 10. Documentation

### 10.1 Code Documentation
- PHPDoc for all classes and public methods
- Inline comments for complex business logic only
- Keep comments current — outdated comments are worse than none

### 10.2 User Documentation
- Maintain `/docs/user-guide.md` for librarian procedures
- Include screenshots for complex workflows
- Document QR scanner setup (camera positioning, lighting)

### 10.3 API Documentation
- Document all endpoints with request/response examples
- Update when endpoints change
