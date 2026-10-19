# Andiswa Business Consultation website (XAMPP + MySQL, Azure-ready)

## What is new in this version – one shared database for the website and the app

The website and the mobile app (through its API) now use **one** MySQL database, `andiswa_business`, on XAMPP or on Azure.

- **Admin → Website & App (new tab).** Edit the phone, email, address, hours, tagline, Home page text, the four numbers and the social links. The website and the app both read these from the `settings` table, so one save updates both.
- **Quotes show where they came from.** A new `quotes.source` column holds `website` or `app`. The admin shows a **Website** or **Mobile app** label on each quote, and the dashboard counts app quotes.
- **The Contact page shows the real details.** It used to show placeholder text (44 Fox Street, Johannesburg). Now it reads them from the database. The footers and the Home page tagline do the same, through `site_settings.php` + `Projects_Js/site-settings.js`.
- **The chatbot log works.** `chat_log.php` saves chatbot messages in the `chat_messages` table. Before this, it tried to post to an API address that did not exist.
- **`database.sql` matches the real database.** It now has all 10 tables (it was missing `invoices`, `notifications`, `services`, `chat_messages` and `cancellation_requests`) and the "Cancelled" statuses. Keys are written inside each table, so it works on Azure.
- **`setup.php` can upgrade an existing database** (with `db_upgrade.php`) without deleting data. Running it twice is safe.
- **Azure-ready.** `andiswa_business_azure.sql` is your current data, ready to import into Azure. `config.php` switches on encryption for Azure servers by itself, and it can read its settings from environment variables when the site itself is hosted on Azure.
- **Full instructions:** `AZURE_SETUP_GUIDE.md`.

| File | Status |
|---|---|
| `admin.php` | Updated: Website & App tab, Website/Mobile app labels, "Cancelled" status, status checks |
| `config.php` | Updated: environment variables, automatic Azure encryption, `setting()` helpers |
| `contact.php` | Updated: real contact details and social links from the database |
| `submit_quote.php` | Updated: saves `source = website`, same checks as the app's API |
| `database.sql` | Updated: full schema (10 tables), Azure-safe |
| `setup.php` | Updated: also upgrades older databases |
| `db_upgrade.php` | New: adds missing columns to an older database |
| `site_settings.php` | New: studio details as JSON for the .html pages |
| `Projects_Js/site-settings.js` | New: fills the details into the .html pages |
| `chat_log.php` | New: saves chatbot messages |
| `Projects_Js/api.js` | Updated: uses `site_settings.php` and `chat_log.php`; uses `localStorage` as the fallback |
| `contact.html` | Now just forwards to `contact.php` |
| `andiswa_business_azure.sql` | New: your data, ready for Azure |
| `AZURE_SETUP_GUIDE.md` | New: step by step guide for Azure |
| all .html pages | Footer details and social links come from the database (the old text stays as a fallback) |

**The API changed a little too** (in the app project): `QuoteEntity` has a `Source` property and `StudioDto` sends `FacebookUrl` and `YouTubeUrl`.
Upgrade the database (run `setup.php` once, or import `andiswa_business_azure.sql`) **before** you run the new API. Otherwise saving a quote from the app fails with "Unknown column 'source'".

## Run it on your PC (XAMPP)

1. Start **Apache** and **MySQL** in the XAMPP Control Panel.
2. Copy this whole folder into `C:\xampp\htdocs\`.
3. Open `http://localhost/Andiswa-Business-Consultations-Website/setup.php`.
4. Choose an admin password (at least 10 characters) and click **Create tables and admin account**.
   This creates the `andiswa_business` database, all tables, the app settings and the admin account.
   If the database already exists, it only adds what is missing (for example the new `source` column). Your data and the admin password stay as they are.
5. **Delete `setup.php`** afterwards.
6. Open the website: `http://localhost/Andiswa-Business-Consultations-Website/index.html`
7. Admin login: `http://localhost/Andiswa-Business-Consultations-Website/login.php` with username `admin` and the password you chose.

No `config.local.php` file = the website uses the normal XAMPP database (`root`, no password).

## Run it with Azure

1. Follow `AZURE_SETUP_GUIDE.md` (in this folder). Parts 1–3 create the database and copy your data.
2. Copy `config.local.example.php`, rename the copy to `config.local.php`, and fill in your Azure server name, username and password.
3. Save the certificate `DigiCertGlobalRootG2.crt.pem` in this folder (download link in the Azure guide).
4. Log in with your usual admin password. Your data, including the admin account, was copied in Part 3.

## Quote → account workflow (unchanged)

1. A visitor sends a quote request from the website **or the mobile app**.
2. It is saved in the `quotes` table with status **New**.
3. The admin logs in to `admin.php` and changes the status to **Accepted**.
4. PHP creates the client account and a temporary password, and tries to email it. If XAMPP email is not set up, the admin page shows the details once.
5. The client logs in at `login.php`, changes the temporary password and sees their projects in `dashboard.php`.
6. The admin creates a project and updates its progress. The client sees the updates.

## Security notes

- Passwords are stored with `password_hash()` and checked with `password_verify()`.
- Forms use CSRF tokens. Database queries use prepared statements.
- **Never upload `config.local.php` to GitHub.** It holds your database password.
- Delete `setup.php` after the first setup.
