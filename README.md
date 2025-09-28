# Project Attendance

Slim-based PHP app for recording check-in/checkout events with geofence validation, simple roles, and basic reports.

### Features
- Check-in and check-out with optional GPS latitude/longitude and accuracy.
- Admin and user roles with basic management pages.
- Working hours, radius, and location settings.
- Exportable attendance summaries.

### Stack
- PHP 8.x, Slim (microframework).
- Composer for dependencies.
- MariaDB/MySQL for persistence.
- Apache with mod_rewrite (XAMPP-friendly).

### Requirements
- PHP 8.1+ with mbstring, pdo_mysql, openssl, curl.
- Composer installed globally.
- MariaDB/MySQL 10.4+.
- Apache with AllowOverride enabled for .htaccess.

### Project structure
```
Project-cnaindo/
├─ app/                 # Application logic (controllers, routes if present)
├─ public/              # Web root (front controller index.php, assets)
├─ includes/            # Shared header/footer partials
├─ vendor/              # Composer packages (generated)
├─ config/              # App configuration, bootstrap, env reading
├─ migrations/          # (Optional) DB migrations
├─ *.php                # Some entry/helper scripts in root (legacy)
├─ project_cnaindo.sql  # Database dump
└─ README.md
```

### Quick start

1) Clone or copy the folder into XAMPP htdocs:
```
C:\xampp\htdocs\Project-cnaindo
```

2) Install PHP dependencies:
```
cd C:\xampp\htdocs\Project-cnaindo
composer install
```

3) Configure environment:

Create: C:\xampp\htdocs\Project-cnaindo\.env
```
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost/Project-cnaindo

DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=project_cnaindo
DB_USERNAME=root
DB_PASSWORD=
# Geofence defaults (can be overridden in settings table)
ALLOWED_LAT=-6.34311300
ALLOWED_LON=107.09773700
RADIUS_METERS=100
WORK_START=09:00:00
WORK_END=17:00:00
```

4) Import database:

Option A — phpMyAdmin
- Create a database named project_cnaindo.
- Import project_cnaindo.sql.

Option B — CLI
```
mysql -u root -p -e "CREATE DATABASE project_cnaindo CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;"
mysql -u root -p project_cnaindo < project_cnaindo.sql
```

5) Point Apache to public/:

Option A — set DocumentRoot to:
```
C:/xampp/htdocs/Project-cnaindo/public
```

Option B — keep current DocumentRoot and add a root .htaccess redirect.

Create: C:\xampp\htdocs\Project-cnaindo\.htaccess
```
RewriteEngine on
RewriteRule ^$ public/ [L]
RewriteRule (.*) public/$1 [L]
```

6) Enable front controller rewrite inside public:

Create/modify: C:\xampp\htdocs\Project-cnaindo\public\.htaccess
```
RewriteEngine On
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^ index.php [QSA,L]
```

7) Start the app:
- Start Apache and MySQL in XAMPP.
- Visit one of:
  - http://localhost/Project-cnaindo  (if using the root redirect)
  - http://localhost                 (if DocumentRoot is set to public)

### Default data
- Database: project_cnaindo
- Tables: users, attendance, settings
- The SQL dump contains sample users and records for testing.

### Common paths

- Web entry point:
  - C:\xampp\htdocs\Project-cnaindo\public\index.php
- Shared partials:
  - C:\xampp\htdocs\Project-cnaindo\includes\header.php
  - C:\xampp\htdocs\Project-cnaindo\includes\footer.php

### Development tips
- Avoid serving the repository root; serve public/ only.
- Keep Composer dependencies out of version control; run `composer install` after cloning.
- Keep secrets out of Git by using .env and never committing it.

### Git basics

Create: C:\xampp\htdocs\Project-cnaindo\.gitignore
```
/vendor/
/node_modules/
/.env
.env.*
/logs/
/tmp/
.DS_Store
Thumbs.db
```

Initial commit and push:
```
git init
git add .
git commit -m "Initial commit"
git branch -M main
git remote add origin <REMOTE_URL>
git push -u origin main
```

### Scripts (optional)

Add to composer.json for convenience:
```
"scripts": {
  "start": "php -S localhost:8080 -t public",
  "post-install-cmd": [],
  "post-update-cmd": []
}
```

Run the built-in server:
```
composer start
```

### Troubleshooting
- If routes 404, ensure AllowOverride All is enabled for the project directory and that both .htaccess files are present.
- If `vendor/autoload.php` is missing, run `composer install`.
- If the app shows DB errors, verify .env matches the created database and credentials.

### License
Add an appropriate license if the code is to be shared publicly.
