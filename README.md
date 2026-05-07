# 🎓 Harinode - ARISE
### Academic Registration, Information, and Student Enrollment

> A centralized university portal system developed for **Pamantasan ng Lungsod ng Maynila (PLM)** — streamlining admissions, enrollment, grading, and academic management through a unified web platform.

---

## 📋 Table of Contents

- [Overview](#overview)
- [Features](#features)
- [System Architecture](#system-architecture)
- [Modules](#modules)
- [Technologies Used](#technologies-used)
- [Database Structure](#database-structure)
- [Getting Started](#getting-started)
- [User Portals](#user-portals)
- [Screenshots](#screenshots)
- [Team](#team)

---

## Overview

Traditional university processes often require visiting multiple offices, submitting numerous documents, and manually managing transactions — making the experience time-consuming and stressful for both students and administrators.

**Harinode - ARISE** addresses these challenges through a single online platform where:

- 📝 **Applicants** can submit admission requirements and track their application
- 🎓 **Students** can manage enrollment and monitor academic progress
- 👩‍🏫 **Faculty** can oversee class lists, encode grades, and manage schedules
- 🛠️ **Administrators** can monitor institutional operations and manage all records

One of its key features is **prerequisite checking**, which ensures students meet required course conditions before enrollment, maintaining academic integrity and standards.

---

## Features

| Feature | Description |
|---|---|
| **User Authentication** | Role-based login with email verification and password reset |
| **Applicant Management** | Online application forms, document submission, and exam scheduling |
| **Course Enrollment** | Subject selection with prerequisite and co-requisite validation |
| **Prerequisite Checking** | Automated engine that evaluates academic records before allowing enrollment |
| **Grade Management** | Faculty grade encoding with auto-computed weighted final grades |
| **Drop Requests** | Students can request subject drops; admins approve or reject |
| **Block Assignment** | Admin assigns students to block sections per course and year level |
| **Admin Dashboard** | Live stat cards and real-time activity feed refreshed every 30 seconds |
| **Academic Calendar** | Calendar and list view of academic events managed by admins |
| **Announcements** | Role-targeted announcements posted by administrators |
| **Dark / Light Mode** | Toggle available across all portals for accessibility |
| **Data Export** | Grade history and class lists exportable as CSV |

---

## System Architecture

The system follows a **browser-based MVC-inspired client-server architecture** deployed via XAMPP or WAMP on a local server environment.

```
┌─────────────────────────────────────────────────────┐
│                  Presentation Layer                  │
│         HTML  ·  CSS  ·  JavaScript (Fetch API)      │
└─────────────────────┬───────────────────────────────┘
                      │ HTTP Requests
┌─────────────────────▼───────────────────────────────┐
│               Application Logic Layer                │
│     PHP Handlers  ·  Session Management  ·  SMTP     │
│  (auth, enrollment, prerequisite checking, grades)   │
└─────────────────────┬───────────────────────────────┘
                      │ MySQLi Queries
┌─────────────────────▼───────────────────────────────┐
│                    Data Layer                        │
│         MySQL · 19 Tables · Relational Model         │
└─────────────────────────────────────────────────────┘
```

**Key architectural decisions:**
- Framework-free development (Vanilla PHP, CSS, and JavaScript) for deeper application of core programming concepts
- Internal JSON API endpoints consumed by the frontend via the Fetch API
- Prepared statements (`mysqli_prepare`, `mysqli_stmt_bind_param`) throughout to prevent SQL injection
- Passwords secured with `password_hash()` and `password_verify()`
- Admin activity logging on all CRUD operations

---

## Modules

| Module | Responsibility |
|---|---|
| **User Authentication & Access Control** | Login, registration, email verification, password recovery, role-based access |
| **Applicant Management** | Application forms, document tracking, exam scheduling |
| **Student Enrollment Management** | Course enrollment, add/drop requests, curriculum viewing |
| **Prerequisite Checking** | Validates academic history before permitting enrollment |
| **Course & Academic Management** | Subjects, classes, blocks, schedules, and academic records |
| **Faculty Management** | Class lists, grade encoding, gradebook, and faculty schedules |
| **Administrative Management** | CRUD for applicants, students, personnel, and academic data |
| **Database & Data Management** | Relational storage, retrieval, and integrity enforcement |
| **Communication & Notifications** | Announcements, calendar events, email alerts |
| **Reporting & Monitoring** | Dashboards, enrollment summaries, activity logs, grade reports |

---

## Technologies Used

### Languages
- **HTML** — Page structure and content
- **CSS** — Interface styling (modular per-page architecture)
- **JavaScript** — Client-side interactivity and async requests via Fetch API
- **PHP** — Server-side logic and request handling
- **SQL** — Database queries and relational data management

### Libraries & Tools
- **PHPMailer** — Email verification and automated notifications via SMTP
- **Font Awesome** — Icon library for interface elements
- **Google Fonts** — Typography

### Database
- **MySQL** — Relational database (accessed via raw `mysqli`)

### Development Tools
- **XAMPP / WAMP** — Local server environments
- **Visual Studio Code** — Primary code editor
- **Git & GitHub** — Version control and collaboration
- **Trello** — Project management and task tracking
- **Canva** — UI mockups and documentation visuals
- **Google Sheets** — QA test case documentation
- **Google Drive** — Cloud backup of source files and documentation

---

## Database Structure

The database consists of **19 tables** with enforced relational integrity through foreign keys.

```
admins          → admin_logs, announcements, calendar_events, exam_schedules
applicants      → exam_schedules, students (upon conversion)
students        → enrollments, grade_entries, grade_history, grades, password_resets
courses         → subjects
subjects        → classes, grades
classes         → enrollments, grade_entries, grade_history, block_subjects
faculty         → classes, grade_history
blocks          → students, block_subjects
enrollments     → grade_entries
```

**Notable design decisions:**
- `grade_entries.computed_grade` is a **MySQL-generated column** that automatically calculates the weighted final grade: `Class Standing (30%) + Quiz (30%) + Midterms (20%) + Finals (20%)`
- `grade_history` serves as a **permanent, immutable record** of finalized grades — once submitted, grades cannot be modified through the grading interface
- `system_settings` stores global configuration values (current semester, school year, enrollment open/closed status) with no foreign key dependencies

---

## Getting Started

### Prerequisites
- [XAMPP](https://www.apachefriends.org/) or [WAMP](https://www.wampserver.com/) installed
- PHP 7.4 or higher
- MySQL 5.7 or higher

### Installation

1. **Clone the repository**
   ```bash
   git clone https://github.com/your-org/harinode-arise.git
   ```

2. **Move to your local server directory**
   ```bash
   # For XAMPP
   mv harinode-arise /path/to/xampp/htdocs/

   # For WAMP
   mv harinode-arise /path/to/wamp/www/
   ```

3. **Import the database**
   - Open `phpMyAdmin` at `http://localhost/phpmyadmin`
   - Create a new database (e.g., `harinode_db`)
   - Import the provided `.sql` file from the `/database` directory

4. **Configure the database connection**
   - Open `src/config/db.php` (or equivalent config file)
   - Update the database credentials:
     ```php
     $host = 'localhost';
     $db   = 'harinode_db';
     $user = 'root';
     $pass = '';
     ```

5. **Configure PHPMailer (optional, for email features)**
   - Update SMTP credentials in the mailer config file

6. **Start your local server and visit:**
   ```
   http://localhost/harinode-arise/
   ```

---

## User Portals

### 🛠️ Admin Portal
After login, admins land on the **Dashboard Overview** showing:
- Stat cards: Total Students, Applicants, Active Faculty, Subjects, Classes
- Recent applicants table with statuses
- Live system activity feed (auto-refreshes every 30 seconds)
- Quick action shortcuts

Sidebar access: **Applicants → Student Records → Academic Records → Personnel → Communications**

---

### 👩‍🏫 Faculty Portal
After login, faculty land on their **Dashboard** showing:
- Faculty information (Employee ID, position, department, employment status)
- Today's class schedule
- Monthly calendar view

Sidebar access: **Schedule → Class List → Spreadsheet → Gradebook → Grade History → Profile**

---

### 🎓 Student Portal
After login, students land on their **Dashboard** showing:
- Student information (program, year level, registration and enrollment status)
- Today's class schedule
- Monthly calendar view

Sidebar access: **Schedule → Enrollment → Grades → My Subjects → Academics → Profile**

---

### 📝 Applicant Portal
After login, applicants land on the **Application Dashboard** showing:
- Current application status and LRN
- Application timeline (Submitted → Documents → Exam → Final Decision)
- Quick action buttons for form, documents, and exam schedule

Sidebar access: **Application Form → Submit Documents → Exam Schedule**

---

## Screenshots

> - Landing page with portal selection
<img width="1365" height="630" alt="image" src="https://github.com/user-attachments/assets/183eb194-43f3-4723-b39d-7403a137e563" />


> - Admin dashboard with live activity feed
<img width="1365" height="631" alt="image" src="https://github.com/user-attachments/assets/135348a7-55fc-4338-8e5f-abfdc74c243a" />


> - Student enrollment page with prerequisite checking
<img width="1365" height="630" alt="image" src="https://github.com/user-attachments/assets/604a72af-6a47-484e-9be2-81d777d0ae09" />


> - Faculty grade spreadsheet and gradebook


> - Applicant application timeline
<img width="1365" height="629" alt="image" src="https://github.com/user-attachments/assets/070d87ca-9308-41c3-8fd7-caef1d8688d3" />


---

## Team

**BSCpE 2-1 — Group 6**
Pamantasan ng Lungsod ng Maynila
College of Engineering and Technology, Computer Engineering Department

| Name | Role |
|---|---|
| Blanco, Gabrielle Nicole S. | UI/UX Designer, QA, Documentation |
| Dela Cruz, Judah Isaiah N. | Main Front-End Developer |
| Lumabi, Kevin G. | Project Manager, QA, Documentation |
| Mendoza, Venedict E. | Front-End, Documentation |
| Muncada, John Louie L. | Backend Developer, System Architect |

**Instructor:** Ryan Justine K. Mondero
**Date Submitted:** April 27, 2026

---

## Declaration of AI Use

AI tools (Amazon Q and Claude) assisted in portions of backend and frontend development — primarily for code generation from developer-designed logic, debugging support, and exploring implementation approaches. All core system architecture, database design, feature decisions, and final implementations were directed and executed by the development team.

---

*Harinode - ARISE · Pamantasan ng Lungsod ng Maynila · 2026*
