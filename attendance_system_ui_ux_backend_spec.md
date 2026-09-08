4. User Roles
4.1 Admin

Admin manages the official student and teacher master records.

Admin functions
Import students through Excel
Import teachers through Excel
Add/edit/deactivate students
Add/edit/deactivate teachers
Manage courses
Manage sections
Manage academic years/terms
Manage class assignments if required
View all attendance
View reports
Manage system settings
4.2 Teacher

Teacher manages the students enrolled in their assigned classes.

Teacher functions
View assigned classes
Create/select Course + Year + Section
Import student roster through Excel
Preview imported students
Validate imported students
View class roster
View total attendance
View total absences
View total tardiness
Start attendance
Generate QR
Monitor live attendance
Close attendance session
Manually correct attendance when authorized
View attendance history
Export class attendance
4.3 Student

Students should NOT need to register themselves freely.

Their account should already exist from the official student master/import process.

Student functions
Login
View profile
View enrolled classes
Scan attendance QR
View attendance result
View attendance history
View attendance calendar
Submit excuse slip if the system supports it
View notifications
5. Teacher Class / Section Setup

The teacher should have a page such as:

My Classes

Example:

Add Class / Import Roster

Teacher clicks:

[ Import Student Roster ]

Show:

6. Excel Roster Format

Recommended Excel columns:

Column	Required	Example
student_number	Yes	2026-00123
full_name	Yes	Juan Dela Cruz
email	Optional	juan@email.com

The Course, Year, and Section should preferably be selected in the UI rather than repeated in every Excel row.

Example
7. Excel Import Validation

Do not immediately insert the Excel data.

Use a preview step.

Step 1 — Upload
Step 2 — Preview
8. Important Improvement: Student Not Found

If the student number does not exist in the Student Master table:

Do NOT automatically create a fake student account from the teacher's roster.

Instead:

This protects the integrity of the attendance database.

9. Duplicate Handling

If the student already belongs to the selected class:

Do not create another enrollment record.

If the student exists but belongs to another section, do not automatically move them.

Instead:

This is safer than allowing a teacher to silently change official student enrollment.

10. Class Roster Page

After importing:

Table:

Student No.	Student Name	Attendance	Absences	Late	Status
2026-00123	Juan Dela Cruz	18	1	2	Active
2026-00124	Maria Santos	19	0	1	Active
2026-00125	Pedro Reyes	15	4	3	Active
Recommended additional columns
Total Sessions
Present
Absent
Late
Excused
Attendance Rate
Last Attendance
Student Status
11. Attendance Rate

Recommended calculation:

Depending on institutional policy, Late can either count as Present or be tracked separately.

Do not hard-code the policy if the school may change it. Make it configurable.

12. Course / Year / Section Filtering

On the teacher dashboard:

After filtering:

The list should update without requiring the teacher to navigate away.

13. Start Attendance Flow

The teacher should NOT generate a QR immediately.

First select the class.

The system should verify:

Teacher is authorized for the class
Class has enrolled students
There is no active attendance session for the same class/date/time
Current date/time is valid
14. Attendance Session

After starting:

15. Dynamic QR Code

Use a temporary/dynamic QR, not a permanent QR.

The QR should identify the attendance session.

Conceptually:

Example:

The QR should NOT contain:

The student account identifies the student.

16. QR Expiration

Recommended:

The QR can refresh while the attendance session remains active.

Example:

The backend should still associate all tokens with the same attendance session.

This makes it harder for students to simply take a screenshot and share it later.

17. Student QR Scan Flow

Student opens:

Backend validates the following.

Validation 1 — Is the student authenticated?
Validation 2 — Is the QR valid?
Validation 3 — Is the attendance session active?
Validation 4 — Does the student belong to the selected class?

Example:

If:

then:

Validation 5 — Has the student already attended?

If already recorded:

Do not create another attendance record.

18. Attendance Status

Recommended logic:

Student scans:

Students who never scan before the session closes:

The exact late policy should be configurable.

19. Automatic Absence Marking

This is an important part of the backend.

When the session closes:

Example:

This should happen automatically.

20. Recommended Improvement: Do Not Create Absences Immediately

Do not mark students absent while the QR session is still active.

Use:

This prevents a student from being marked absent while they are still allowed to scan.

21. Manual Attendance Correction

Teachers should have a controlled option after the session:

Authorized teacher:

Example:

Every manual change should be logged.

22. Attendance Audit Log

Recommended for security.

Create an audit record whenever attendance is manually changed.

Example:

This is especially useful for an academic attendance system.

23. Recommended Database Structure

A clean structure would be:

24. Users

Roles:

25. Students

Student number should be unique.

26. Teachers

Employee number should be unique.

27. Courses

Example:

28. Sections

Example:

29. Classes

A class represents a specific course taught to a specific section.

Example:

This is important because the same section can have many courses.

30. Enrollments

This table connects students to classes.

Unique constraint:

This prevents duplicate enrollment.

31. Attendance Sessions

Status:

32. Attendance

Status:

Verification:

Recommended unique constraint:

This is extremely important.

It guarantees that one student cannot generate multiple attendance records for the same session.

33. Attendance Audit Logs
34. Teacher UI Pages

Recommended pages:

35. Teacher Dashboard

Recommended cards:

Below:

36. Student UI

Student Dashboard:

37. QR Scan Result
Successful
Wrong Section
Expired QR
Already Scanned
38. Excel Import UI/UX Recommendations

Use a 3-step process:

Step 1
Step 2
Step 3

Do not make the teacher guess whether the import succeeded.

39. Import History

Recommended additional feature:

This helps with troubleshooting.

40. Important Enrollment Improvement

Do not let a teacher's Excel file permanently define a student's identity.

The hierarchy should be:

This makes your database much easier to maintain.

41. Recommended Security Rules

The backend should always validate the teacher.

A teacher should only be able to:

View their assigned classes
Import rosters for authorized classes
Start attendance for authorized classes
View attendance for authorized classes
Modify attendance according to their permissions

Never trust values coming from the frontend.

For example, do not rely only on:

sent by JavaScript.

The backend should get the authenticated user's teacher ID and verify ownership.

42. QR Security Rules

The backend should validate:

Only after all validations pass:

43. Optional Security Improvement: Location

If desired, add optional location validation.

When scanning:

Example:

However, this should be optional because GPS accuracy can vary indoors and it can make the capstone unnecessarily complicated.

44. Optional Anti-Screenshot Improvement

Dynamic QR is recommended.

The QR should:

expire quickly
refresh periodically
require an authenticated student
require class enrollment
allow one attendance record per session/student

A screenshot alone should not be sufficient to create valid attendance after the QR expires.

45. Attendance Lifecycle

The final lifecycle should be:

46. Example Complete Scenario
Before Class

Admin has already imported:

Teacher is assigned:

Teacher imports the roster.

Juan becomes enrolled in:

During Class

Teacher:

System creates:

QR appears.

Juan Scans

Juan logs in.

System knows:

QR says:

Backend finds:

Then checks:

Attendance:

Student From Another Section Scans

Pedro belongs to:

He scans the QR for:

Backend:

Result:

No attendance record is created.

Session Ends

Roster:

Attendance:

Backend creates:

Final:

47. Final Recommended Design

The strongest design for this system is:

Key architectural decision

Student identity and class enrollment must be separate.

Do not make:

Instead:

This allows one student to safely belong to multiple subjects/classes without creating duplicate accounts.

48. UI/UX Generation Order

When generating the UI, build in this order:

Login
Admin Dashboard
Admin Student Import
Admin Teacher Import
Teacher Dashboard
My Classes
Add/Import Class Roster
Excel Preview/Validation
Class Roster
Attendance Summary
Start Attendance
Live QR Attendance
Student QR Scanner
QR Scan Result
Live Attendance Monitoring
Session Close / Absence Processing
Attendance History
Reports
Student Dashboard
Student Attendance History

Generate the UI first using realistic sample data.

After the UI is approved, implement the backend using the database relationships and validation rules defined in this document.
"""