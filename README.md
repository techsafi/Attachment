# STVIAMS — Student Industrial Attachment Management System

**Case Study:** Seme Technical and Vocational College

A simple offline web system built with **PHP** and **MySQL** (XAMPP/LAMPP) for managing student industrial attachments.

## System Objectives

| # | Objective | Module |
|---|-----------|--------|
| 1 | Capture students' details | Students |
| 2 | Generate industrial letters | Industrial Letters |
| 3 | Manage placement approvals | Placements |
| 4 | Assign assessors & submit marks | Assessors & Marks |
| 5 | Submit logbooks & recommendation letters | Upload (student) / Submissions (staff) |
| 6 | Reports & completion certificates | Reports / Certificates |

## Requirements

- XAMPP or LAMPP (Apache + MySQL + PHP 7.4+)
- Web browser (works fully offline on localhost)

## Installation

1. Copy this folder to `htdocs/Attachment` (already there if using XAMPP).
2. Start **Apache** and **MySQL** from the XAMPP control panel.
3. Open: `http://localhost/Attachment/install.php`
4. Click **Install Database**.
5. Open: `http://localhost/Attachment/`
6. (Optional) Delete or rename `install.php` after setup.

## Default Login Accounts

| Username | Password | Role |
|----------|----------|------|
| admin | password | Administrator |
| coordinator | password | Coordinator |
| assessor1 | password | Assessor |

Create student logins via **Users** (admin) and link each account to a student record.

## Typical Workflow

1. **Admin/Coordinator** — Register students and companies.
2. Create a **placement** request → **Approve** it.
3. **Generate** introduction/placement/release letters (printable).
4. **Assign assessor** to student.
5. **Assessor** enters marks (practical, logbook, attitude).
6. **Student** uploads logbook and recommendation letter.
7. **Coordinator** reviews submissions and prints **reports**.
8. Issue **completion certificate** for eligible students.

## Database

- Database name: `stviams`
- Config: `config/database.php` (default: root, no password)

## Project Structure

```
Attachment/
├── config/          Database connection
├── database/        SQL schema
├── includes/        Auth, layout, helpers
├── assets/          CSS (offline, no CDN)
├── uploads/         Student files
├── index.php        Login
├── install.php      One-time setup
└── *.php            Feature pages
```

## School Project Notes

- Plain PHP (no frameworks) for easy explanation in documentation.
- Session-based login with four roles: admin, coordinator, assessor, student.
- All styling is local (`assets/style.css`) — no internet required after install.

---

*Seme Technical and Vocational College — Industrial Attachment Office*
