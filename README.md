# MINI PROJECT — Dashboard Attendance (PHP skeleton)

This is a minimal PHP skeleton for the Attendance Dashboard demo. It is intended to be run under XAMPP (Apache + MySQL).

Quick setup

1. Copy this folder to your XAMPP `htdocs` (already in place).
2. Import `migrations.sql` into MySQL (phpMyAdmin or mysql client). This will create `project_cnaindo` and the tables.
3. Edit `config.php` if MySQL credentials differ.
4. Start Apache + MySQL in XAMPP and visit: http://localhost/Project-cnaindo/

Notes
- Basic features: register/login, record attendance (with browser geolocation), admin list of users and recent attendance.
- This is a minimal, demo-first implementation. For production: add CSRF protection, input sanitization, prepared statements (already used), HTTPS, password reset, roles UI, and tests.

Optional: Install Composer and use Slim framework scaffold
-----------------------------------------------------
If you'd like to migrate to a framework (Slim) and use Composer-managed libraries, follow these steps on Windows with XAMPP:

1. Download and install Composer: https://getcomposer.org/Composer-Setup.exe
2. Ensure Composer uses your XAMPP PHP (usually C:\\xamppp\\php\\php.exe) during installation.
3. In PowerShell, from the project folder, run:

```powershell
cd C:\\xamppp\\htdocs\\Project-cnaindo
composer install
```

4. After install, you can run the optional Slim front controller at `public/index.php`. Configure Apache to use `public` as document root or test via built-in PHP server (for dev only):

```powershell
& 'C:\\xamppp\\php\\php.exe' -S localhost:8080 -t public
```

Note: The current legacy PHP pages (register.php, login.php, attendance.php) will continue to work without Composer. The Slim scaffold is optional and provided for migration.

Admin note:
- You can create an admin via `create_admin.php` (visit /Project-cnaindo/create_admin.php) or update the `users` table directly. Remove `create_admin.php` after use.


Deliverables added:
- PHP files: index.php, register.php, login.php, logout.php, attendance.php, admin.php, auth.php, db.php, config.php
- migrations.sql
- README.md
