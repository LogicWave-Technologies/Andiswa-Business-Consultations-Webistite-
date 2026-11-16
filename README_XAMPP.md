# Andiswa Business Consultation website (PHP + published API)

The website's quote, account, admin project, and client-portal flows use the published Andiswa Business API. The PHP website still needs a web server, but those flows do not need a local MySQL database. The website settings editor and chatbot log remain MySQL-backed.

## Features

- PHP session-based UI with login handled by the published API.
- Quote requests, client accounts, admin project actions, and client dashboards use the API.
- Website settings editing and chatbot message logging remain MySQL-backed.
- Contact enquiries and client login credentials are sent through PHPMailer over SMTP.

## Run locally with XAMPP

1. Install XAMPP and Composer. Start Apache. Start MySQL only if you need the settings editor or chatbot logging.
2. Copy this project folder into `C:\xampp\htdocs\`.
3. In a terminal opened in the project folder, run `composer install` to install the locked PHPMailer dependency.
4. Configure the optional MySQL database only for the settings editor and chatbot logging. Admin/client accounts and quotes are managed by the API.
5. Open the website at `http://localhost/<project-folder>/index.html`.

The default API base is `https://andiswa-business-api-buc0hwgybmhnabhs.southafricanorth-01.azurewebsites.net/api`. Set `API_BASE_URL` to override it. Use an account already present in the API database to log in; there is no local default admin account.

## Configure email

Follow the [PHPMailer email guide](./README.md#phpmailer-email-guide) for Gmail App Password setup, the ignored `config.smtp.local.php` file, SMTP environment-variable overrides, testing, and troubleshooting. Configure the file in the project directory actually served by Apache; it is not committed and must not contain credentials in the tracked example. The application uses PHPMailer over SMTP and has no `mail()` fallback.

## Existing MySQL-backed features

Follow [AZURE_SETUP_GUIDE.md](./AZURE_SETUP_GUIDE.md) only if you need to connect the settings editor or chatbot logging to MySQL. The quote, account, admin project, and client-portal flows use the published API.

`config.local.php`, `config.smtp.local.php`, and `*.pem` files are excluded from Git. SMTP settings supplied through environment variables are also supported.

## Quote and client workflow

1. A visitor submits a quote request from the website or mobile app.
2. An admin reviews and accepts the quote.
3. The API creates a client account when a quote is accepted and returns one-time credentials for new accounts. The website emails those credentials.
4. The client logs in, changes the temporary password, and views projects and progress updates.
5. The admin creates projects and posts updates for the client.

## Security and setup notes

- API-managed passwords are stored as bcrypt hashes and checked by the API.
- PHP forms use CSRF tokens; remaining MySQL queries use prepared statements.
- Never upload `config.local.php` or database credentials to GitHub.
- Remove `setup.php` from any deployment where the optional MySQL database is initialized.
