# PLM Course Enrollment System — How It Works

## Overview

The PLM (Pamantasan ng Lungsod ng Maynila) Course Enrollment System is a web-based application built with PHP and MySQL. It manages the full lifecycle of a student — from application, admission, to enrollment and grading — across four user portals: **Admin**, **Applicant**, **Student**, and **Faculty**.

---

## Tech Stack

- **Backend:** PHP (procedural, session-based auth)
- **Database:** MySQL via MySQLi
- **Frontend:** HTML, CSS, Vanilla JavaScript
- **Icons:** Font Awesome 7
- **Email:** PHPMailer

---

## User Portals

### 1. Applicant Portal
Entry point for new students applying to PLM.

**Flow:**
1. Register an account at `applicant_register.php`
2. Fill out the application form (`applicant_apply.php`) — personal info, program choices, etc.
3. Submit required documents (`applicant_submit.php`)
4. View assigned entrance exam schedule (`applicant_exam.php`)
5. Track application status on the dashboard (`applicant_home.php`)

**Application Statuses:**
- `incomplete` — form not yet fully filled
- `pending` — submitted, awaiting review
- `pending_review` — under active review
- `approved` — accepted
- `rejected` — not accepted
- `enrolled` — converted to student

---

### 2. Admin Portal
Full control over the system. Accessible only to admin accounts.

**Modules:**

| Module | Description |
|---|---|
| Dashboard | Overview stats: students, applicants, faculty, subjects, classes, enrollments |
| Applicants | Review, approve, or reject applicants; convert approved applicants to students |
| Students | Manage student accounts, assign to blocks, update info |
| Blocks | Create and manage section blocks per course, year level, and semester |
| Faculty | Manage faculty accounts and assignments |
| Subjects | Add, edit, delete subjects; batch import via text format |
| Classes | Create class sections, assign faculty, set schedule and room |
| Enrollments | View all enrollments; manual enroll; batch enroll; approve drop requests |
| Announcements | Post announcements targeted to students, faculty, applicants, or all |
| Calendar | Manage academic calendar events |
| Admin Accounts | Manage admin users (superadmin only) |

---

### 3. Student Portal
For enrolled students to manage their academic activities.

**Pages:**

| Page | Description |
|---|---|
| Dashboard | Welcome screen with announcements and calendar |
| Schedule | View enrolled class schedules |
| Enrollment | View enrolled subjects, available classes, and self-enroll |
| Grades | View grades per subject |
| Academics | Program info, college info, curriculum link |
| Profile | View and update personal information |

---

### 4. Faculty Portal
For faculty members to manage their teaching load and grades.

**Pages:**

| Page | Description |
|---|---|
| Dashboard | Faculty info, today's/weekly schedule, calendar, announcements |
| Schedule | Full teaching load view |
| Class List | View students per class |
| Gradebook | Enter and manage student grades |
| Spreadsheet | Grade spreadsheet view |
| Profile | View and update faculty profile |

---

## Database Structure

### Core Tables

| Table | Purpose |
|---|---|
| `admins` | Admin user accounts |
| `applicants` | Applicant records and application status |
| `students` | Enrolled student accounts |
| `faculty` | Faculty member accounts |
| `courses` | Available degree programs (e.g., BSCpE, BSIT) |
| `subjects` | Subject/course catalog linked to a course via `course_id` |
| `classes` | Class sections for a subject (faculty, schedule, room, slots) |
| `blocks` | Student groupings per course, year level, and semester |
| `block_subjects` | Links classes to blocks |
| `enrollments` | Student-to-class enrollment records |
| `grades` | Final grade records per student per subject |
| `grade_entries` | Detailed grade breakdown (class standing, quiz, midterms, finals) |
| `announcements` | System-wide announcements |
| `calendar_events` | Academic calendar events |
| `exam_schedules` | Entrance exam schedules for applicants |

### Key Relationships

```
courses ──< subjects ──< classes ──< enrollments >── students
                                 └──< block_subjects >── blocks >── students
faculty ──< classes
applicants ──(convert)──> students
```

---

## Enrollment Flow

### Admin-Managed Enrollment (Batch)
1. Admin creates **subjects** and links them to a course via `course_id`
2. Admin creates **classes** for each subject (assigns faculty, schedule, room)
3. Admin creates **blocks** (e.g., BSCpE 1-A) and assigns classes to the block
4. Admin uses **Batch Enroll** to enroll all students of a course/year level into classes at once
5. Enrollment status is set to `ongoing`

### Manual Enrollment (Admin)
- Admin goes to **Enrollments → Manual Enroll**
- Selects a student and a class
- Enrollment is created with status `reserved` or `confirmed`

### Self-Enrollment (Student)
1. Student logs in and goes to **Enrollment**
2. Views available classes filtered by their course's `course_id`
3. Clicks **Enroll** on an open class
4. System checks:
   - Class is `open` and has available slots
   - Student is not already enrolled
   - Prerequisites are satisfied (checks `grades` table)
5. Enrollment is created with status `ongoing`
6. Class `enrolled_count` is incremented

### Drop Request
1. Student clicks drop on a `confirmed` or `ongoing` enrollment
2. Status changes to `drop_requested`
3. Admin reviews and approves the drop
4. Status changes to `dropped` and `enrolled_count` is decremented

---

## Enrollment Statuses

| Status | Description |
|---|---|
| `reserved` | Admin-reserved, not yet confirmed by student |
| `confirmed` | Student confirmed the reservation |
| `ongoing` | Actively enrolled (self-enrolled or batch enrolled) |
| `drop_requested` | Student requested to drop |
| `dropped` | Enrollment dropped |
| `completed` | Semester completed |

---

## Subject & Course Relationship

Subjects are linked to courses via `course_id`. The unique constraint on subjects is `(subject_code, course_id)`, which means:

- The same subject code (e.g., `CET 0111`) can exist for multiple courses (BSCpE, BSIT, BSChE)
- Each course has its own copy of shared subjects
- Students only see classes for their specific course during enrollment

### Batch Import Format
```
course_code|subject_code|subject_name|units|lecture_hours|lab_hours|department|year_level|semester|prerequisite
```
Example:
```
BSCpE|CET 0111|Calculus 1|3|3.0|0.0|BSCPE|1|2nd|
BSIT|CET 0111|Calculus 1|3|3.0|0.0|BSIT|1|2nd|
```

---

## Grading System

Grades are stored in `grade_entries` with four components:

| Component | Weight |
|---|---|
| Class Standing | 30% |
| Quiz | 30% |
| Midterms | 20% |
| Finals | 20% |

The `computed_grade` is a generated column calculated automatically by MySQL.

Faculty enter grades via the **Gradebook** page. Final grades are stored in the `grades` table and are used for prerequisite checking during enrollment.

---

## Authentication

Each portal has its own session-based authentication:

| Portal | Session Key | Auth Function |
|---|---|---|
| Admin | `$_SESSION['admin_id']` | `check_admin_login()` |
| Student | `$_SESSION['student_number']` | `check_login()` |
| Faculty | `$_SESSION['faculty_id']` | Manual session check |
| Applicant | `$_SESSION['applicant_id']` | `check_applicant_login()` |

All portals redirect to `login_hub.php` if the session is invalid.

---

## Applicant to Student Conversion

When an applicant is approved and ready to enroll:
1. Admin goes to **Applicants** and clicks **Convert to Student**
2. `convert_to_student.php` creates a new record in the `students` table using the applicant's data
3. The applicant's `application_status` is updated to `enrolled`
4. The student can now log in via the Student Portal

---

## File Structure

```
src/
├── pages/
│   ├── admin/          # Admin portal pages
│   ├── applicants/     # Applicant portal pages
│   ├── faculty/        # Faculty portal pages
│   └── student/        # Student portal pages
├── php/                # Backend handlers and utilities
│   ├── connection.php          # DB connection
│   ├── functions.php           # Student auth helpers
│   ├── admin_functions.php     # Admin auth helpers
│   ├── applicant_functions.php # Applicant auth helpers
│   ├── admin_subjects_batch_import.php  # Batch subject import
│   ├── student_enrollment_action.php    # Student enrollment actions
│   └── ...
├── css/                # Stylesheets per portal
├── js/                 # JavaScript per portal
└── assets/             # Images and icons
db/
└── studentenrollment.sql  # Full database dump
```
