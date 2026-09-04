# Security Document
# Library Attendance Monitoring System (LAMS)

## 1. Threat Model

| Threat | Likelihood | Impact | Mitigation |
|--------|-----------|--------|------------|
| SQL Injection | High | Critical | Parameterized queries (PDO prepared statements) |
| XSS | High | High | Output escaping, CSP headers |
| CSRF | Medium | High | CSRF tokens on all state-changing forms |
| Session Hijacking | Medium | High | HTTPS, secure cookie flags, session regeneration |
| Brute Force Login | Medium | High | Rate limiting, account lockout |
| Unauthorized Data Access | Medium | High | RBAC, authorization middleware |
| File Upload Abuse | Medium | Medium | Type validation, size limits, storage outside web root |
| Privilege Escalation | Low | Critical | Strict role checks, no client-side role data |
| Data Exfiltration | Low | High | Least privilege DB user, no direct table access |
| Email Spoofing | Low | Medium | SMTP authentication, SPF/DKIM on domain |

## 2. Authentication

### 2.1 Password Policy
- Minimum 8 characters
- At least one uppercase, one lowercase, one digit
- Password history not enforced (MVP); recommend adding in v2
- Passwords hashed with `password_hash($password, PASSWORD_BCRYPT, ['cost' => 12])`
- Verification with `password_verify()`

### 2.2 Session Management
```php
// On login success
session_regenerate_id(true);
$_SESSION['user_id'] = $user_id;
$_SESSION['role'] = $role;
$_SESSION['last_activity'] = time();
$_SESSION['ip_address'] = $_SERVER['REMOTE_ADDR'];
$_SESSION['user_agent'] = $_SERVER['HTTP_USER_AGENT'];
```

**Session Security Settings (php.ini):**
```ini
session.cookie_httponly = 1
session.cookie_secure = 1
session.cookie_samesite = "Strict"
session.use_strict_mode = 1
session.gc_maxlifetime = 28800  ; 8 hours
```

**Session Validation (on every request):**
- Check `last_activity` > 24 hours → destroy session (idle timeout)
- Check `ip_address` matches current IP → destroy session (optional: allow subnet)
- Check `user_agent` matches → destroy session
- Update `last_activity` on each authenticated request

### 2.3 Login Rate Limiting
- Track failed attempts per IP + email in `login_attempts` table
- After 5 failed attempts: 15-minute lockout
- After 10 failed attempts: 1-hour lockout
- Display generic message: "Invalid credentials or account locked" (do not reveal which)

### 2.4 Password Reset (Future / Admin-Only)
- MVP: Only admins can reset passwords via user management panel.
- Admin reset generates random 12-character password; forces change on next login.
- Log all password resets to `audit_logs`.

## 3. Authorization

### 3.1 Role-Based Access Control (RBAC)
- Roles stored in `users.role` ENUM
- No dynamic role creation (fixed set)
- Authorization check in middleware before every protected route

```php
function requireRole(array $allowedRoles): void {
    if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], $allowedRoles)) {
        http_response_code(403);
        exit(json_encode(['success' => false, 'message' => 'Access denied']));
    }
}
```

### 3.2 Data-Level Authorization
- **Students:** Can only view own attendance (`WHERE user_id = $_SESSION['user_id']`)
- **Parents:** Can only view linked child's attendance (`WHERE parent_user_id = $_SESSION['user_id']`)
- **Teachers:** Can view all student attendance; can submit excuse slips for any student (no section restriction in MVP)
- **Staff/Admin:** Full access

### 3.3 Ownership Verification
Before updating/deleting any record, verify the acting user has permission:
```php
// Example: Before deleting attendance
$record = Attendance::find($id);
if (!$record || !canDeleteAttendance($_SESSION['user_id'], $_SESSION['role'], $record)) {
    abort(403);
}
```

## 4. Input Validation

### 4.1 Server-Side Validation (Primary)
**Never trust client input.** All data validated server-side.

| Data Type | Validation Rule |
|-----------|-----------------|
| Email | `filter_var($email, FILTER_VALIDATE_EMAIL)` |
| Integer IDs | `filter_var($id, FILTER_VALIDATE_INT)` + positive check |
| Dates | `DateTime::createFromFormat('Y-m-d', $date)` + range check |
| Strings | Length check + regex for allowed characters |
| ENUM values | Strict whitelist check against allowed values |
| File uploads | MIME type check + extension whitelist + size limit |

### 4.2 Client-Side Validation (UX Only)
- HTML5 validation attributes for immediate feedback
- JavaScript validation for format checks
- **Always re-validate on server**

### 4.3 Validation Helper
```php
class Validator {
    public static function email(string $email): bool { ... }
    public static function required(string $value): bool { ... }
    public static function maxLength(string $value, int $max): bool { ... }
    public static function inArray($value, array $allowed): bool { ... }
    public static function dateRange(DateTime $from, DateTime $to): bool { ... }
}
```

## 5. SQL Injection Prevention

### 5.1 Mandatory Prepared Statements
**ALL database queries must use PDO prepared statements.**

```php
// CORRECT
$stmt = $pdo->prepare("SELECT * FROM users WHERE email = :email");
$stmt->execute(['email' => $email]);

// FORBIDDEN
$result = $pdo->query("SELECT * FROM users WHERE email = '$email'");
```

### 5.2 Dynamic Queries
If dynamic column/table names are needed (e.g., sorting), whitelist allowed values:
```php
$allowedColumns = ['created_at', 'name', 'email'];
$sortBy = in_array($_GET['sort'], $allowedColumns) ? $_GET['sort'] : 'created_at';
```

### 5.3 Database User Privileges
- Application DB user: `SELECT`, `INSERT`, `UPDATE`, `DELETE` only
- No `DROP`, `ALTER`, `CREATE`, `GRANT` privileges
- Separate admin user for migrations (used only in deployment)

## 6. XSS Prevention

### 6.1 Output Escaping
**All output to HTML must be escaped.**
```php
// Use htmlspecialchars with ENT_QUOTES and UTF-8
echo htmlspecialchars($userInput, ENT_QUOTES, 'UTF-8');
```

### 6.2 Content Security Policy (CSP)
```http
Content-Security-Policy: default-src 'self'; 
  script-src 'self' 'unsafe-inline'; 
  style-src 'self' 'unsafe-inline'; 
  img-src 'self' data: blob:; 
  connect-src 'self'; 
  font-src 'self';
```

*Note: `'unsafe-inline'` required for Tailwind utility classes and inline event handlers in MVP. Evaluate stricter CSP in v2.*

### 6.3 JSON Output
When returning JSON, ensure `Content-Type: application/json` header is set.

## 7. CSRF Protection

### 7.1 Token Generation
```php
// On session start or form render
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
```

### 7.2 Token Validation
All POST/PUT/DELETE requests must include and validate CSRF token:
```php
function validateCsrfToken(): void {
    $token = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    if (!hash_equals($_SESSION['csrf_token'] ?? '', $token)) {
        http_response_code(403);
        exit(json_encode(['success' => false, 'message' => 'Invalid CSRF token']));
    }
}
```

### 7.3 Token Inclusion
- Forms: `<input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">`
- AJAX: `X-CSRF-Token` header

## 8. CORS Policy

**Same-origin only.** No cross-origin API access needed.
```php
// Only if needed in future
header("Access-Control-Allow-Origin: https://trusted-domain.com");
header("Access-Control-Allow-Methods: GET, POST");
header("Access-Control-Allow-Headers: Content-Type, X-CSRF-Token");
```

## 9. Rate Limiting

### 9.1 API Endpoints
| Endpoint | Limit | Window |
|----------|-------|--------|
| Login | 5 attempts | 15 minutes |
| QR Scan | 100 requests | 1 minute |
| CSV Export | 5 requests | 10 minutes |
| Excuse Slip Submit | 10 requests | 1 hour |

### 9.2 Implementation
Track in database or APCu:
```php
function rateLimit(string $key, int $max, int $window): bool {
    $attempts = getAttempts($key); // from cache or DB
    if ($attempts >= $max) return false;
    incrementAttempts($key, $window);
    return true;
}
```

## 10. File Upload Security

### 10.1 Validation Rules
- **Size:** Max 5MB for excuse slips, 2MB for avatars
- **Extension:** Whitelist only `.pdf`, `.jpg`, `.jpeg`, `.png`
- **MIME Type:** Verify with `finfo_file()` — must match extension
- **Filename:** Sanitize to `[a-zA-Z0-9_-].{ext}`
- **Storage:** Outside web root (`/uploads/`), served via PHP proxy

### 10.2 Anti-Malware Measures
- Rename files on upload (do not preserve original name)
- Store with `.txt` extension if MIME type is suspicious (optional)
- No executable file types allowed

### 10.3 Upload Directory Protection
```apache
# .htaccess in /uploads/
Options -Indexes
Deny from all
```

## 11. Secrets Management

### 11.1 Environment Variables
All secrets stored in `.env` file (NOT in source code or version control):
```bash
DB_HOST=localhost
DB_NAME=lams_db
DB_USER=lams_app
DB_PASS=your_secure_password_here

SMTP_HOST=smtp.gmail.com
SMTP_PORT=587
SMTP_USER=alerts@library.edu
SMTP_PASS=your_app_password_here
SMTP_ENCRYPTION=tls

APP_ENV=production
APP_URL=https://library.edu
SESSION_SECRET=random_32_char_string
```

### 11.2 .env Protection
```apache
# .htaccess in project root
<Files .env>
    Order allow,deny
    Deny from all
</Files>
```

### 11.3 Secret Rotation
- Database password: Rotate every 6 months
- SMTP password: Rotate if compromised or every 12 months
- Session secret: Rotate every 6 months (forces re-login)

## 12. SSL/TLS

- **Mandatory HTTPS** in production
- Redirect HTTP to HTTPS via Apache rewrite
- TLS 1.2 minimum; TLS 1.3 preferred
- HSTS header:
```http
Strict-Transport-Security: max-age=31536000; includeSubDomains
```

## 13. Sensitive Data Protection

### 13.1 Data Minimization
- Store only necessary PII (name, email, phone)
- Do not store: full address, SSN, financial data
- QR codes are system-generated random strings — not derived from personal data

### 13.2 Data Encryption at Rest
- MySQL data: Rely on filesystem encryption (LUKS) or MySQL TDE if available
- Backups: Encrypted with GPG before transfer to backup storage

### 13.3 Data Encryption in Transit
- HTTPS for all traffic
- SMTP with TLS/STARTTLS

### 13.4 Password Recovery
- Admin-initiated only (no self-service in MVP)
- Temporary passwords: 12 random characters, forced change on first login

## 14. Error Handling

### 14.1 User-Facing Errors
- Generic messages: "An error occurred. Please try again."
- No stack traces, SQL errors, or file paths exposed to users

### 14.2 Logged Errors
- Full stack traces, SQL queries (with params), user ID, timestamp, IP
- Stored in `logs/errors/` with daily rotation
- Maximum 30 days retention

### 14.3 Exception Handling
```php
try {
    // business logic
} catch (PDOException $e) {
    error_log("[DB ERROR] " . $e->getMessage());
    Response::error("Database error occurred", 500);
} catch (Exception $e) {
    error_log("[ERROR] " . $e->getMessage());
    Response::error("An unexpected error occurred", 500);
}
```

## 15. Audit Logging

### 15.1 Logged Actions
- Login success/failure
- Password changes/resets
- Attendance record create/update/delete
- Excuse slip approve/reject
- User create/update/delete
- Settings changes
- Data exports

### 15.2 Log Format (Database)
See `audit_logs` table in Data.md.

### 15.3 Log Retention
- Database audit logs: 2 years
- Error logs: 30 days
- Cron job logs: 90 days

## 16. Backup & Recovery

### 16.1 Backup Strategy
- **Database:** Daily `mysqldump` at 2:00 AM
- **Uploads:** Daily `rsync` to backup server
- **Code:** Git repository (offsite)
- **Retention:** 7 daily backups, 4 weekly backups, 12 monthly backups

### 16.2 Recovery Procedures
- **Database corruption:** Restore from most recent clean backup
- **Accidental deletion:** Restore from daily backup; use audit logs to identify what was deleted
- **RTO (Recovery Time Objective):** 4 hours
- **RPO (Recovery Point Objective):** 24 hours (daily backups)

## 17. Security Checklist (Pre-Deployment)

- [ ] `.env` file exists and is in `.gitignore`
- [ ] `.env` is protected by `.htaccess`
- [ ] `display_errors = Off` in production `php.ini`
- [ ] HTTPS enforced with valid SSL certificate
- [ ] Database user has minimal privileges
- [ ] All forms include CSRF tokens
- [ ] All outputs use `htmlspecialchars()`
- [ ] All queries use prepared statements
- [ ] File upload directory has `Deny from all`
- [ ] Session cookies have `HttpOnly`, `Secure`, `SameSite=Strict`
- [ ] Rate limiting enabled on login and scan endpoints
- [ ] Cron jobs configured for absence batch and alert processing
- [ ] Backup scripts tested
- [ ] Admin password is strong and unique
