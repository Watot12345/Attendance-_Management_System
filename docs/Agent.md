# AI Agent Behavior Rules
# Library Attendance Monitoring System (LAMS)

## 1. Core Directive

Your job is to keep the project **CONSISTENT, SECURE, TESTABLE, MAINTAINABLE, UNDERSTANDABLE, and STABLE**.

You are NOT merely a code generator. You are a disciplined engineer who verifies before acting.

## 2. Mandatory Pre-Action Protocol

Before writing or modifying ANY code, you MUST:

### 2.1 Read Relevant Documentation
- Read `PRD.md` to understand WHAT the feature should do
- Read `Data.md` to understand the data model
- Read `Architecture.md` to understand HOW it should be built
- Read `Security.md` to understand protection requirements
- Read `Skills.md` to follow established patterns

### 2.2 Inspect Existing Code
- Locate all files related to the feature
- Understand the current implementation
- Identify dependencies and callers
- Note existing patterns (naming, structure, error handling)

### 2.3 Inspect Database/Schema
- Verify table structures match Data.md
- Check for existing indexes
- Verify foreign key constraints
- If schema differs from Data.md, REPORT THE CONFLICT (do not silently fix)

### 2.4 Understand Dependencies
- Check `composer.json` for PHP dependencies
- Check `package.json` for JS dependencies
- Verify required extensions are available
- Do not add new dependencies without justification

## 3. Planning Before Complex Changes

For any change involving:
- More than 2 files
- Database schema changes
- Authentication/authorization logic
- New API endpoints
- Changes to existing business rules

You MUST:
1. State the plan explicitly
2. List all files to be modified
3. Describe the data flow
4. Identify potential risks
5. Wait for approval if the change is destructive or high-risk

## 4. Change Size Principle

**Prefer small changes.**

- One feature per change set
- One bug fix per change set
- Refactor only when necessary for the current task
- Never combine unrelated changes

## 5. Preserve Working Behavior

- Do not break existing features
- Maintain backward compatibility for APIs
- If a behavior change is required, document it explicitly
- Run through the debug process if something breaks

## 6. Tech Stack Adherence

- Use **Native PHP 8.2+** — no frameworks (Laravel, Symfony, CodeIgniter)
- Use **Vanilla JavaScript ES6+** — no React, Vue, Angular, jQuery
- Use **Tailwind CSS** — no Bootstrap, custom CSS files unless absolutely necessary
- Use **MySQL 8.0+** — no PostgreSQL, MongoDB, SQLite
- Use **PDO with prepared statements** — no raw SQL concatenation, no ORM

## 7. Secret Protection

- NEVER hardcode passwords, API keys, or credentials
- NEVER expose `.env` contents in responses or logs
- NEVER commit `.env` to version control
- ALWAYS use `$_ENV` or `config.php` loaded from `.env`

## 8. Destructive Change Protocol

The following require EXPLICIT approval before execution:

- Deleting data (users, attendance records, settings)
- Dropping tables or columns
- Removing features or files
- Changing authentication logic
- Changing role-based access rules
- Modifying database constraints
- Resetting passwords
- Bulk updates to production data

**When requesting approval, explain:**
- What will be changed
- Why it is necessary
- Impact on existing data
- Rollback plan

## 9. Conflict Resolution

Documentation is NOT automatically correct. Existing code is NOT automatically correct.

When sources conflict, reconcile using this hierarchy:

1. **Actual implementation** (database schema, working code)
2. **Product requirements** (PRD.md)
3. **Data consistency** (Data.md)
4. **Architecture consistency** (Architecture.md)
5. **Security requirements** (Security.md)
6. **Migration/compatibility impact**

**Process:**
1. Identify the conflict
2. Show conflicting definitions
3. Inspect actual implementation
4. Determine impact
5. Recommend canonical definition
6. Ask for approval if decision is risky

**NEVER** silently pick whichever source was read last.

### Example Conflict Resolution

**Conflict:**
- Data.md says: `user_id = UUID`
- Architecture.md says: `user_id = BIGINT`
- Database shows: `user_id = INT UNSIGNED`

**Resolution:**
- Evidence: Database uses `INT UNSIGNED AUTO_INCREMENT`
- Decision: Canonical type is `INT UNSIGNED AUTO_INCREMENT`
- Reason: Actual implementation is highest evidence. Changing to UUID would require full migration with breaking changes.
- Action: Update Data.md and Architecture.md to match database. Do NOT change schema.

## 10. Code Quality Rules

### 10.1 PHP
- `declare(strict_types=1);` in every file
- Type hints for all function parameters and returns
- Prepared statements for ALL database queries
- `htmlspecialchars()` for ALL output
- Never use `$_GET`/`$_POST` directly without validation
- Never use `eval()`, `exec()`, `system()`, `passthru()`

### 10.2 JavaScript
- Use `const` and `let`; never `var`
- Use `fetch()` for AJAX
- Validate responses before using data
- Never use `innerHTML` with untrusted data; use `textContent` or DOM methods

### 10.3 SQL
- Never concatenate user input into queries
- Always use named parameters (`:param`)
- Validate dynamic identifiers (table/column names) against whitelists

### 10.4 HTML
- Always escape dynamic content
- Always include CSRF tokens in forms
- Use semantic HTML elements

## 11. Testing Expectation

After implementing a feature:
- Describe how to test it
- List expected success cases
- List expected failure cases
- Note any edge cases

You do not need to write automated tests (MVP scope), but you MUST verify logic mentally and describe test scenarios.

## 12. Documentation Updates

When code changes affect:
- API behavior → Update Architecture.md API contracts
- Data model → Update Data.md
- Security rules → Update Security.md
- User workflows → Update PRD.md if requirements changed

## 13. Error Handling Standard

All exceptions MUST be caught and handled:
```php
try {
    // operation
} catch (PDOException $e) {
    error_log("[DB ERROR] " . $e->getMessage());
    Response::error("Database error occurred", 500);
} catch (Exception $e) {
    error_log("[ERROR] " . $e->getMessage());
    Response::error("An unexpected error occurred", 500);
}
```

Never expose:
- SQL queries
- File paths
- Stack traces
- Environment variables

## 14. Git Commit Guidelines

When generating code that will be committed:
- Use descriptive commit messages
- Format: `[Area] Brief description`
- Examples:
  - `[Auth] Add login rate limiting`
  - `[Attendance] Fix tardy threshold calculation`
  - `[DB] Add index on attendance_records(record_date)`

## 15. Never Do

- NEVER add technology "because it's popular"
- NEVER introduce microservices, Docker, Kubernetes, Redis, queues, or WebSockets without concrete justification
- NEVER ignore security requirements for convenience
- NEVER skip validation because "it's internal"
- NEVER assume database state matches documentation — VERIFY
- NEVER make breaking changes without approval
- NEVER leave `TODO`, `FIXME`, or `HACK` in committed code without explanation
- NEVER use `echo` for debugging in production-bound code
