# BSI Cards - Installation Guide

This guide explains how to install the project and set up the MySQL database, including cPanel steps for creating the database, database user, and password.

## Prerequisites

- PHP and web server configured for Laravel
- MySQL or MariaDB available
- Access to your hosting file manager/SSH
- cPanel access (for shared hosting)
- Project files uploaded to your hosting account

## 1) Upload the project

Upload this project to your hosting path (for example `public_html` or a subfolder).

Make sure these paths exist:

- Project root: contains `artisan`, `composer.json`, `install/`
- SQL file: `install/bsicards.sql`
- Installer page: `install/index.php`

## 2) Create MySQL database and user in cPanel

1. Log in to **cPanel**.
2. Open **MySQL Databases**.
3. Under **Create New Database**:
   - Enter a database name (example: `bsicards`)
   - Click **Create Database**
4. Under **MySQL Users** -> **Add New User**:
   - Enter username (example: `bsicards_user`)
   - Enter a strong password
   - Confirm password
   - Click **Create User**
5. Under **Add User To Database**:
   - Select the new user
   - Select the new database
   - Click **Add**
6. Grant privileges:
   - Check **ALL PRIVILEGES**
   - Click **Make Changes**

### Important cPanel naming note

cPanel usually prefixes database/user names with your cPanel account name.

Examples:

- Database may become `cpanelname_bsicards`
- User may become `cpanelname_bsicards_user`

Use the **full prefixed values** in installation settings.

## 3) Configure `.env`

If `.env` is missing, copy from `.env.example` first.

Set at least:

```env
APP_URL=https://your-domain.com
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=your_full_database_name
DB_USERNAME=your_full_database_user
DB_PASSWORD=your_database_password
```

> On many cPanel hosts, `DB_HOST` remains `localhost` or `127.0.0.1`. Use the value provided by your host.

## 4) Run the web installer

Open in browser:

- `https://your-domain.com/install/index.php`

Enter:

- Website URL
- Database name
- Database username
- Database password

The installer will:

- Validate database connection
- Update `.env` values
- Import `install/bsicards.sql`
- Show clear errors if connection/import fails

## 5) Finalize Laravel setup (if needed)

If you have shell access, run:

```bash
php artisan optimize:clear
php artisan storage:link
```

If your host uses cron, configure Laravel scheduler (optional but recommended):

```bash
* * * * * php /home/USERNAME/path-to-project/artisan schedule:run >> /dev/null 2>&1
```

## Troubleshooting

### Database connection failed

- Confirm `DB_HOST`, `DB_PORT`, DB name, username, and password
- Confirm user is assigned to the database
- Confirm **ALL PRIVILEGES** are granted
- Use full cPanel-prefixed database/user names

### SQL import failed

- Confirm `install/bsicards.sql` exists and is readable
- Ensure the DB user has create/alter/insert privileges
- Retry after recreating an empty database

### 500 error after setup

- Clear cache:

```bash
php artisan optimize:clear
```

- Check logs in `storage/logs/laravel.log`

## Security recommendation

After successful installation, restrict access to `install/index.php` (or remove/rename installer files) so it cannot be reused in production.

