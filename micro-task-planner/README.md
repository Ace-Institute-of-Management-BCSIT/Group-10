# Micro Task Planner

> Beat procrastination, one step at a time.

## Overview

Micro Task Planner helps users break big goals into bite-sized mini-tasks, track progress with visual rings, and stay consistent with streak tracking.

## Tech Stack

- **Backend:** PHP 8+ with PDO/MySQLi
- **Database:** MySQL
- **Frontend:** Vanilla JS, CSS custom properties, SVG progress rings
- **AI:** Anthropic Claude API (mini-task suggestions)

## Project Structure

```
micro-task-planner/
├── index.php              # Entry point — landing + sign in / sign up
├── database.sql           # DB schema (run once)
├── README.md
│
├── assets/
│   ├── css/
│   │   ├── style.css      # Main stylesheet
│   │   └── dark-mode.css  # Dark mode overrides
│   └── js/
│       ├── progress.js    # SVG progress ring renderer
│       ├── tasks.js       # Task interactions, modal, AJAX
│       └── notify.js      # Toast notifications + dark mode toggle
│
├── pages/
│   ├── dashboard.php      # Main task dashboard
│   ├── analytics.php      # Productivity charts & stats
│   └── settings.php       # User profile & password settings
│
└── includes/
    ├── db.php             # PDO connection singleton
    ├── auth.php           # Session helpers & auth guards
    ├── functions.php      # Shared utility functions
    ├── sidebar.php        # Shared sidebar partial
    ├── logout.php         # Logout handler
    ├── suggestions.php    # AJAX: AI mini-task suggestions
    ├── add_mini.php       # AJAX: add mini-task
    ├── toggle_mini.php    # AJAX: toggle mini-task completion
    ├── task_progress.php  # AJAX: get task progress percent
    └── delete_task.php    # AJAX: delete a task
```

## Setup Instructions

### 1. Database

```bash
mysql -u root -p < database.sql
```

### 2. Configure DB credentials

Edit `includes/db.php`:
```php
define('DB_USER', 'your_db_user');
define('DB_PASS', 'your_db_password');
```

### 3. (Optional) Enable AI suggestions

Set the `ANTHROPIC_API_KEY` environment variable on your server, or add it directly in `includes/suggestions.php`. Without it, the app falls back to rule-based suggestions automatically.

```bash
export ANTHROPIC_API_KEY=sk-ant-...
```

### 4. Run with PHP dev server

```bash
cd micro-task-planner
php -S localhost:8000
```

Then open `http://localhost:8000`

## User Flow

```
Landing (index.php)
  └─→ Sign Up (index.php?page=signup)
        └─→ Sign In (index.php?page=signin)
              └─→ Dashboard (pages/dashboard.php)
                    ├─→ + New Task (modal)
                    │     └─→ AI mini-task suggestions
                    ├─→ Analytics (pages/analytics.php)
                    └─→ Settings (pages/settings.php)
```

## Features

- **Landing page** with hero preview card
- **Auth** — sign up / sign in with session management
- **Dashboard** — task list with progress rings, filtering, search
- **New Task Modal** — AI-powered mini-task suggestions, priority, due date
- **Mini-tasks** — inline add, check off, auto progress tracking
- **Analytics** — completion rate, donut chart, weekly bar chart, streak
- **Dark mode** — persisted via localStorage
- **Settings** — update name, email, password
