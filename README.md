Commit 1 · File 3 of 3 — README.md
Create this file at C:/xampp/htdocs/trustgov/README.md
markdown# TrustGov — Barangay Budget Transparency Portal

A public-facing web application where barangay officials post budgets, projects, and expenses, and citizens can view and submit feedback. Built to demonstrate REST API integration using PHP and MySQL on XAMPP.

---

## Tech Stack

| Layer      | Technology                                                             |
|------------|------------------------------------------------------------------------|
| Backend    | Pure PHP REST API (no frameworks)                                      |
| Database   | MySQL via PDO with prepared statements                                 |
| Frontend   | HTML + Tailwind CSS CDN + Vanilla JavaScript                           |
| UI Extras  | Chart.js, SweetAlert2, DataTables, Font Awesome, Animate.css, Toastify |
| Auth       | PHP Sessions (stores user id, role, barangay)                          |
| Server     | XAMPP on Windows                                                       |

---

## Folder Structure
```
trustgov/
├── api/
│   ├── config/         # PDO database connection
│   ├── users/          # User and role management endpoints
│   ├── budgets/        # Budget registry endpoints
│   ├── projects/       # Project tracker endpoints
│   ├── expenses/       # Expense logs endpoints
│   ├── feedback/       # Citizen feedback endpoints
│   └── analytics/      # Aggregated analytics endpoints
├── assets/
│   ├── css/            # Custom theme stylesheet
│   ├── js/             # Shared JS: auth, theme, api helpers
│   └── img/            # Logo and images
├── pages/              # All authenticated HTML pages
├── index.html          # Landing / redirect page
├── login.html          # Login and register page
├── trustgov_db.sql     # Full database schema and sample data
└── README.md
```

---

## Setup Instructions (XAMPP on Windows)

1. **Clone or copy** the `trustgov` folder into:
```
   C:/xampp/htdocs/trustgov
```

2. **Start XAMPP** — make sure both **Apache** and **MySQL** are running.

3. **Import the database:**
   - Open `http://localhost/phpmyadmin`
   - Click **Import**
   - Select `trustgov_db.sql`
   - Click **Go**

4. **Open the app:**
```
   http://localhost/trustgov/login.html
```

---

## Sample Credentials

| Role     | Email                  | Password    |
|----------|------------------------|-------------|
| Admin    | admin@trustgov.ph      | password123 |
| Official | official@trustgov.ph   | password123 |
| Official | official2@trustgov.ph  | password123 |
| Citizen  | citizen1@trustgov.ph   | password123 |
| Citizen  | citizen2@trustgov.ph   | password123 |

---

## API Endpoint Reference

### Users
| Method | Endpoint                      | Access       | Description          |
|--------|-------------------------------|--------------|----------------------|
| GET    | /api/users/read.php           | Admin        | Get all users        |
| GET    | /api/users/read_one.php?id=x  | Any          | Get single user      |
| POST   | /api/users/register.php       | Public       | Register new user    |
| POST   | /api/users/login.php          | Public       | Login and get session|
| PUT    | /api/users/update.php         | Any          | Update user profile  |
| DELETE | /api/users/delete.php         | Admin        | Delete a user        |

### Budgets
| Method | Endpoint                        | Access        | Description         |
|--------|---------------------------------|---------------|---------------------|
| GET    | /api/budgets/read.php           | Public        | Get all budgets     |
| GET    | /api/budgets/read_one.php?id=x  | Public        | Get single budget   |
| POST   | /api/budgets/create.php         | Official      | Create budget       |
| PUT    | /api/budgets/update.php         | Official      | Update budget       |
| DELETE | /api/budgets/delete.php         | Admin         | Delete budget       |

### Projects
| Method | Endpoint                           | Access    | Description              |
|--------|------------------------------------|-----------|--------------------------|
| GET    | /api/projects/read.php             | Public    | Get all projects         |
| GET    | /api/projects/read.php?budget_id=x | Public    | Filter by budget         |
| GET    | /api/projects/read_one.php?id=x    | Public    | Get single project       |
| POST   | /api/projects/create.php           | Official  | Create project           |
| PUT    | /api/projects/update.php           | Official  | Update project           |
| DELETE | /api/projects/delete.php           | Admin     | Delete project           |

### Expenses
| Method | Endpoint                              | Access    | Description            |
|--------|---------------------------------------|-----------|------------------------|
| GET    | /api/expenses/read.php                | Public    | Get all expenses       |
| GET    | /api/expenses/read.php?project_id=x   | Public    | Filter by project      |
| GET    | /api/expenses/read_one.php?id=x       | Public    | Get single expense     |
| POST   | /api/expenses/create.php              | Official  | Log new expense        |
| PUT    | /api/expenses/update.php              | Official  | Update expense         |
| DELETE | /api/expenses/delete.php              | Admin     | Delete expense         |

### Feedback
| Method | Endpoint                              | Access          | Description            |
|--------|---------------------------------------|-----------------|------------------------|
| GET    | /api/feedback/read.php                | Public          | Get all feedback       |
| GET    | /api/feedback/read.php?project_id=x   | Public          | Filter by project      |
| GET    | /api/feedback/read_one.php?id=x       | Public          | Get single feedback    |
| POST   | /api/feedback/create.php              | Citizen         | Submit feedback        |
| PUT    | /api/feedback/update.php              | Official, Admin | Update status          |
| DELETE | /api/feedback/delete.php              | Admin           | Delete feedback        |

### Analytics
| Method | Endpoint                               | Access | Description                  |
|--------|----------------------------------------|--------|------------------------------|
| GET    | /api/analytics/summary.php             | Public | Totals: budget, spent, etc.  |
| GET    | /api/analytics/budget_utilization.php  | Public | % spent per category         |
| GET    | /api/analytics/project_status.php      | Public | Project count per status     |
| GET    | /api/analytics/top_feedback.php        | Public | Most upvoted and flagged      |
| GET    | /api/analytics/expense_trend.php       | Public | Monthly expense totals       |

---

## Module Overview

| Module | Description |
|--------|-------------|
| Users | Registration, login, role-based access (admin, official, citizen) |
| Budgets | Budget allocations per barangay, year, and category |
| Projects | Projects linked to budgets with status tracking |
| Expenses | Itemized expense logs per project |
| Feedback | Citizen comments with upvoting and suspicious flagging |
| Analytics | Aggregated charts and summary data for the dashboard |

---

## API Response Format

All endpoints return JSON in this consistent format:
```json
{
  "status": "success",
  "message": "Records retrieved.",
  "data": [ ... ]
}