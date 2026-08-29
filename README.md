# MediCore — Hospital Management System

A web-based Hospital Management System with four role-specific dashboards: **Admin, Doctor, Receptionist, and Patient**. Built as the CSC 3215 (Web Technologies) group project at AIUB.

**Tech Stack:** HTML, CSS, JavaScript, PHP, AJAX, MySQL

---

## Team & Module Assignment

| Member | Student ID | Role in Team | Assigned Module |
|---|---|---|---|
| Shafiqul Islam Nayem | 23-54388-3 | Group Leader | Project setup, Authentication system, Doctor Module |
| Fahmidun Nobi Muhid | 23-54944-3 | Member | Admin Module |
| Tajmun Nahr Tisha | 23-54386-3 | Member | Patient Module |
| Abdur Rahman | 23-54954-3 | Member | Receptionist Module |

### 1. Nayem — Core Setup + Doctor Module
- Database schema design (`schema.sql`) — shared with everyone on day 1
- Authentication system: login, logout, registration, password reset (shared by all roles)
- Session management & role-based access control
- Doctor Dashboard
- View Appointments
- View Patient Medical Profile & History
- Request Lab Tests
- View Lab Test Results
- Create Prescriptions

### 2. Muhid — Admin Module
- Admin Dashboard
- Manage Doctors
- Manage Receptionists
- Manage Ward & Bed allocation

### 3. Tisha — Patient Module
- Patient Dashboard
- Book Appointment
- View Prescriptions
- View My Bills
- Profile management (view/edit) — shared component, reused across all roles

### 4. Abdur Rahman — Receptionist Module
- Receptionist Dashboard
- Manage Appointments
- View Patient Profile
- Generate Billing / Invoice

> **Note:** Common features (login, dashboard shell, profile edit) should be built once by whoever needs it first, then reused by others via `include()` in PHP rather than rebuilt per module. Coordinate this in your group chat before starting.

---

## Recommended Folder Structure

```
medicore/
├── config/
│   └── db.php              # DB connection (use config.sample.php as template, don't commit real db.php)
├── includes/
│   ├── header.php
│   ├── footer.php
│   └── auth_check.php
├── assets/
│   ├── css/
│   ├── js/
│   └── images/
├── admin/
├── doctor/
├── receptionist/
├── patient/
├── ajax/                    # AJAX endpoint scripts (return JSON)
├── database/
│   └── schema.sql           # Full DB schema + sample data — everyone imports this locally
├── index.php
└── README.md
```

---

## GitHub Workflow (4 people, 4 computers)

### Step 1 — One-time setup (each person, once)

1. Everyone installs [XAMPP](https://www.apachefriends.org/) (Apache + MySQL + PHP) if not already installed.
2. Group Leader (Nayem) creates the GitHub repo and adds all 4 members as collaborators.
3. Everyone clones the project **directly inside their XAMPP `htdocs` folder**:
   ```bash
   cd C:\xampp\htdocs        # Windows
   # cd /Applications/XAMPP/xamppfiles/htdocs   # Mac
   git clone https://github.com/<leader-username>/medicore.git
   cd medicore
   ```
4. Start **Apache** and **MySQL** from the XAMPP Control Panel.
5. Everyone imports `database/schema.sql` into their **own local MySQL via phpMyAdmin** (`http://localhost/phpmyadmin` → Import) — the database is NOT shared over GitHub, only the schema/structure is. Each person works with their own local copy of the database.
6. Everyone copies `config/config.sample.php` to `config/db.php` and fills in their local DB credentials. On default XAMPP, MySQL user is `root` with an **empty password** — so it's the same for everyone, but `db.php` still stays in `.gitignore` since it may get changed locally.
7. Access the project in browser at: `http://localhost/medicore/`

### Step 2 — Branching strategy

- `main` — always working/stable code only. Nobody pushes directly here.
- Each person works on their **own feature branch**, named by module:
  ```bash
  git checkout -b feature/doctor-module      # Nayem
  git checkout -b feature/admin-module       # Muhid
  git checkout -b feature/patient-module     # Tisha
  git checkout -b feature/receptionist-module # Abdur Rahman
  ```

### Step 3 — Daily workflow (repeat this every session)

```bash
git checkout feature/your-module-name
git pull origin main --rebase     # get latest shared code (auth, includes) before you start
# ... do your work ...
git add .
git commit -m "doctor: add prescription creation form"
git push origin feature/your-module-name
```

**Commit message convention:** `<module>: <short description>`
Examples: `admin: add doctor management page`, `patient: fix appointment booking validation`

### Step 4 — Merging into `main`

1. Go to GitHub → open a **Pull Request** from your feature branch into `main`.
2. At least one other teammate reviews it (quick read-through is fine for a class project).
3. Merge the PR once it's confirmed not to break shared files (`config/`, `includes/`).
4. After merging, everyone else runs:
   ```bash
   git checkout feature/your-module-name
   git pull origin main --rebase
   ```
   to bring the newly merged code into their own branch.

### Step 5 — Handling conflicts

- Conflicts will mostly happen in shared files: `includes/header.php`, `config/db.php` (ignored anyway), or `database/schema.sql`.
- If schema.sql changes (someone adds a new table/column), **announce it in the group chat immediately** and everyone re-imports it locally.
- For code conflicts, whoever is more familiar with that specific file resolves it, then pushes and notifies the group.

### Golden rules
- Never push directly to `main`.
- Never commit `config/db.php`, `node_modules/`, or any file with real passwords.
- Pull before you start working, every single time.
- Keep PRs small — one module feature per PR, not the whole module at once.
- Test your PHP page locally before pushing (check for syntax errors: `php -l yourfile.php`).

---

## Setup Instructions (for a new member/computer)

1. Install XAMPP and start **Apache** + **MySQL** from the Control Panel.
2. Clone the repo into `htdocs`:
   ```bash
   cd C:\xampp\htdocs
   git clone https://github.com/<leader-username>/medicore.git
   cd medicore
   cp config/config.sample.php config/db.php
   ```
3. Edit `config/db.php` with your local DB settings (default XAMPP: host `localhost`, user `root`, password empty).
4. Open `http://localhost/phpmyadmin`, create a database (e.g. `medicore`), and import `database/schema.sql`.
5. Open the project in your browser:
   ```
   http://localhost/medicore/
   ```