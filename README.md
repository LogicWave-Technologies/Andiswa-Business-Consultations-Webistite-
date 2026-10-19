# Andiswa Business Consultation website (XAMPP + MySQL, Azure-ready)

The website and mobile app API share the `andiswa_business` MySQL database. Admins can manage website/app settings and quotes, and clients can track projects and updates.

## Features

- PHP session-based admin and client login, with password hashing and CSRF protection.
- Quote requests from the website and mobile app, labelled by source in the admin dashboard.
- Client accounts and temporary passwords generated when quotes are accepted.
- Shared business details and social links for the website and mobile app.
- Chatbot message logging and an upgrade-safe database setup.
- SMTP credential email through PHPMailer, with PHP `mail()` as the fallback when SMTP is not configured.

## Run locally with XAMPP

1. Install XAMPP and Composer. Start Apache and MySQL.
2. Copy this project folder into `C:\xampp\htdocs\`.
3. In a terminal opened in the project folder, run `composer install` to install the locked PHPMailer dependency.
4. Open `http://localhost/phpmyadmin` and import `database.sql`, or open `setup.php` to create/upgrade the database and initialize the admin account.
5. Delete `setup.php` after setup.
6. Open the website at `http://localhost/<project-folder>/index.html`.

By default, the site connects to local MySQL as `root` with no password. For custom local settings or Azure, see the configuration section below.

## Configure email

Set these environment variables in the Apache/PHP environment to send account credentials through SMTP:

- `SMTP_HOST`: SMTP server hostname.
- `SMTP_PORT`: SMTP port; defaults to `587`.
- `SMTP_USERNAME` and `SMTP_PASSWORD`: both set for authenticated SMTP, or both left empty.
- `SMTP_ENCRYPTION`: `tls` (default) or `ssl`.
- `SMTP_FROM_EMAIL` and `SMTP_FROM_NAME`: sender address and display name (optional).

Restart Apache after changing its environment. If `SMTP_HOST` is not set, the site uses PHP `mail()`. If email delivery fails, the admin dashboard displays generated credentials once so they can be provided to the client.

## Run with Azure

1. Follow [AZURE_SETUP_GUIDE.md](./AZURE_SETUP_GUIDE.md).
2. Copy `config.local.example.php` to `config.local.php` and enter the Azure database settings.
3. Place the Azure CA certificate `DigiCertGlobalRootG2.crt.pem` in the project folder, as described in the guide.
4. Upgrade/import the database before running the updated mobile app API.

`config.local.php` and `*.pem` files are excluded from Git. The site also supports database settings supplied through environment variables.

## Quote and client workflow

1. A visitor submits a quote request from the website or mobile app.
2. An admin reviews and accepts the quote.
3. The site creates or updates the client account and emails temporary login credentials.
4. The client logs in, changes the temporary password, and views projects and progress updates.
5. The admin creates projects and posts updates for the client.

## Security and setup notes

- Passwords are stored with `password_hash()` and checked with `password_verify()`.
- Forms use CSRF tokens and database queries use prepared statements.
- Never upload `config.local.php` or database credentials to GitHub.
- Delete `setup.php` after initial setup.
