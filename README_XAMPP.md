# Andiswa Business Consultation — XAMPP + MySQL version

## What was changed
- Removed the website light/dark mode toggle and theme switching code.
- Replaced the browser-only admin login with a PHP session-based login.
- Added one login page for both administrators and clients.
- Added MySQL database tables for users, quotes, projects, project updates and settings.
- Quote requests are saved to MySQL through PHP/PDO.
- The client receives an on-screen confirmation after submitting a quote.
- When an admin accepts a quote, a client account is automatically created and temporary login credentials are generated.
- The system emails credentials through configured SMTP, or PHP `mail()` when SMTP is not configured. If email delivery fails, the admin dashboard shows the generated credentials once so they can be provided to the client.
- Clients can log in, view quote status, view project progress, and read project updates.
- Admins can review quotes, accept/reject/update them, create projects and update progress.

## XAMPP installation
1. Install XAMPP and start **Apache** and **MySQL** in the XAMPP Control Panel.
2. Copy this entire project folder into `C:\xampp\htdocs\`.
3. Install Composer, open a terminal in the project folder, and run `composer install` to install PHPMailer from `composer.lock`.
4. Open `http://localhost/phpmyadmin`.
5. Import `database.sql` into phpMyAdmin. It creates the `andiswa_business` database and all required tables.
6. Open `http://localhost/Andiswa-Business-Consultations-Website-v8/setup.php` (change the folder name in the URL if you renamed the folder).
7. The setup page creates the first admin account:
   - Username: `admin`
   - Password: `Admin@2026`
8. Delete `setup.php` after the first successful setup.
9. Open the website at `http://localhost/Andiswa-Business-Consultations-Website-v8/index.html`.

## Important database settings
The default XAMPP MySQL settings are already configured in `config.php`:
- Host: `127.0.0.1`
- Database: `andiswa_business`
- Username: `root`
- Password: blank

If you configured a MySQL password, change `DB_PASS` in `config.php`.

## Quote → account workflow
1. Visitor opens **Get a Quote**.
2. Visitor submits their name, email, service and project description.
3. PHP validates the request and inserts it into the `quotes` table.
4. Visitor is redirected back to the contact page and sees a confirmation message.
5. Admin logs into `admin.php`.
6. Admin changes the quote status to **Accepted**.
7. PHP creates the client account, hashes the password, links the account to the quote, and generates a temporary password.
8. The system attempts to email the username and temporary password.
9. Client logs in at `login.php`, changes the temporary password, and reaches `dashboard.php`.
10. Admin creates a project for the client and updates progress. The client sees those updates in the portal.

## Email configuration
When `SMTP_HOST` is set in the Apache/PHP environment, the app sends account credentials through PHPMailer. Configure these environment variables for Apache:
- `SMTP_HOST`: SMTP server hostname.
- `SMTP_PORT`: SMTP port (defaults to `587`).
- `SMTP_USERNAME` and `SMTP_PASSWORD`: both set for authenticated SMTP, or both left empty.
- `SMTP_ENCRYPTION`: `tls` (default) or `ssl`.
- `SMTP_FROM_EMAIL` and `SMTP_FROM_NAME`: sender address and display name (optional).

Restart Apache after changing its environment. If `SMTP_HOST` is not set, the app uses PHP `mail()` as before. If email delivery fails, the admin dashboard displays the generated credentials once so they can be provided to the client.

## Security notes
- Passwords are stored with `password_hash()` and verified with `password_verify()`.
- PHP sessions are used for authentication.
- POST forms use CSRF tokens.
- Database queries use PDO prepared statements.
- Temporary client passwords are required to be changed after first login.
- Delete `setup.php` after initial installation.
