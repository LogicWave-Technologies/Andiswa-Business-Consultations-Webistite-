# Andiswa Business Consultation website (XAMPP + MySQL, Azure-ready)

The website and mobile app API share the `andiswa_business` MySQL database. Admins can manage website/app settings and quotes, and clients can track projects and updates.

## Features

- PHP session-based admin and client login, with password hashing and CSRF protection.
- Quote requests from the website and mobile app, labelled by source in the admin dashboard.
- Client accounts and temporary passwords generated when quotes are accepted.
- Shared business details and social links for the website and mobile app.
- Chatbot message logging and an upgrade-safe database setup.
- Contact enquiries and client login credentials are sent through PHPMailer over SMTP.

## Run locally with XAMPP

1. Install XAMPP and Composer. Start Apache and MySQL.
2. Copy this project folder into `C:\xampp\htdocs\`.
3. In a terminal opened in the project folder, run `composer install` to install the locked PHPMailer dependency.
4. Open `http://localhost/phpmyadmin` and import `database.sql`, or open `setup.php` to create/upgrade the database and initialize the admin account.
5. Delete `setup.php` after setup.
6. Open the website at `http://localhost/<project-folder>/index.html`.

By default, the site connects to local MySQL as `root` with no password. For custom local settings or Azure, see the configuration section below.

## Configure email

For Gmail, use the ignored local file `config.smtp.local.php` (already created locally; it is not committed). Turn on 2-Step Verification for the sending Google account and create a Google App Password. Put the Gmail address in `username` and `from_email`, and the 16-character App Password in `password`. Do not use your normal Google password. Port `465` with `ssl` is configured because port `587` is filtered on some networks. Contact enquiries go to the business email configured in **Admin → Website & App**, with the visitor's address set as Reply-To.

Alternatively, configure `SMTP_HOST`, `SMTP_PORT`, `SMTP_USERNAME`, `SMTP_PASSWORD`, `SMTP_ENCRYPTION`, `SMTP_FROM_EMAIL`, and `SMTP_FROM_NAME` in the Apache/PHP environment. Environment values override entries in the local file.

SMTP must be configured for emails to be delivered; there is no `mail()` fallback. If sending client credentials fails after accepting a quote, the admin dashboard displays them once so they can be provided to the client. Check the PHP error log for the SMTP failure reason.

## Run with Azure

1. Follow [AZURE_SETUP_GUIDE.md](./AZURE_SETUP_GUIDE.md).
2. Copy `config.local.example.php` to `config.local.php` and enter the Azure database settings.
3. Place the Azure CA certificate `DigiCertGlobalRootG2.crt.pem` in the project folder, as described in the guide.
4. Upgrade/import the database before running the updated mobile app API.

`config.local.php`, `config.smtp.local.php`, and `*.pem` files are excluded from Git. The site also supports database and SMTP settings supplied through environment variables.

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
