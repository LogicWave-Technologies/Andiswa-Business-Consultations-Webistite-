# Putting the Andiswa database on Azure (website + app share it)

LogicWave Technologies · server `logicwave-andiswa-business-consultation` · resource group `logicwave-rg`

This guide moves your `andiswa_business` database from XAMPP to **Azure Database for MySQL – Flexible Server**.
After that, the website and the mobile app both use that one database.

```
 Website (PHP)  ──────────────┐
 XAMPP or Azure App Service   │
                              ▼
                 Azure Database for MySQL   ← one database: andiswa_business
                              ▲
 Mobile app ──► API (ASP.NET Core on Azure App Service)
```

**What is shared:**

| Table | Website | App (through the API) |
|---|---|---|
| `settings` | Contact page, footers, **Admin → Website & App** | `GET /api/studio` (Home and Contact pages) |
| `quotes` | Contact form | `POST /api/quotes` (Contact page) |
| everything else | admin, client portal, chatbot log | not used yet |

Time needed: about 45 minutes. Do the parts in order.

---

## Part 1 – Create the database server

1. Go to <https://portal.azure.com> and sign in with your **Azure for Students** account.
2. Search for **Azure Database for MySQL flexible servers** → **+ Create** → **Flexible server**.
3. **Basics** tab:

   | Field | What to choose |
   |---|---|
   | Subscription | Azure for Students |
   | Resource group | **Create new** → `logicwave-rg` |
   | Server name | `logicwave-andiswa-business-consultation` |
   | Region | **South Africa North**. If Azure says the region is not allowed for your subscription, pick another one it allows. |
   | MySQL version | **8.0** |
   | Workload type | **Dev/Test** (do NOT leave it on Standard: that picks an expensive General Purpose server with high availability) |
   | Compute + storage | Click **Configure server** → **Burstable**, **Standard_B1ms**, storage **20 GiB**, High availability **Disabled** → **Save**. The cost panel should show about USD 15–20 a month, which the student free offer covers. |
   | Authentication method | **MySQL authentication only** |
   | Admin username | e.g. `logicwaveadmin` |
   | Password | a strong password. **Write it down.** |

   > The free offer covers a B1ms server for 750 hours a month and 32 GB storage for 12 months. Anything bigger costs money.

4. **Networking** tab:
   - Connectivity method: **Public access (allowed IP addresses)**
   - Tick **Allow public access from any Azure service within Azure to this server** (the API needs this).
   - Click **+ Add current client IP address** (your PC needs this for XAMPP and Workbench).
   - Each teammate who runs the website or Workbench on their own PC must add their own IP address here too. Never add the range `0.0.0.0 – 255.255.255.255`: that opens the database to the whole internet.
5. **Review + create** → **Create**. It takes about 5–10 minutes.
6. When it is done, open the server and copy the **Server name** from **Overview**.
   Yours is `logicwave-andiswa-business-consultation.mysql.database.azure.com`.

Leave the server parameters as they are. Azure forces encrypted connections (`require_secure_transport = ON`), and our files are ready for that.

---

## Part 2 – Download the certificate

Azure only accepts encrypted connections. PHP needs a certificate file to switch encryption on.

1. Download **DigiCert Global Root G2**: <https://cacerts.digicert.com/DigiCertGlobalRootG2.crt.pem>
2. Save it in the website folder, next to `config.php`, with the name `DigiCertGlobalRootG2.crt.pem`.

`.gitignore` already stops `.pem` files from going to GitHub.

---

## Part 3 – Copy your data to Azure

Use the file **`andiswa_business_azure.sql`** in the website folder.
Do **not** use the normal phpMyAdmin export. On Azure it fails with *"Multiple primary key defined"*, because Azure can switch on a setting (`sql_generate_invisible_primary_key`) that adds its own hidden key.
`andiswa_business_azure.sql` is your export from 14 October with that problem fixed. It also adds the new `source` column. All your users, quotes, projects, invoices and chats are kept, and the admin password stays the same.

> If you added new data in XAMPP **after 14 October**, export again from phpMyAdmin, then run `setup.php` once against Azure after the import (see Part 4, step 4). Or ask for the conversion to be redone with the new export.

### Using MySQL Workbench (easiest)

1. Install **MySQL Workbench** (free) if you do not have it.
2. **+** (new connection):
   - Hostname: your server name from Part 1
   - Port: `3306`
   - Username: `logicwaveadmin` (your admin username, nothing added)
   - **SSL** tab → Use SSL: **Require**
3. Connect and type the password.
4. **File → Open SQL Script…** → choose `andiswa_business_azure.sql` → click the ⚡ (Execute) button.
5. Check that it worked. Run:
   ```sql
   SELECT COUNT(*) FROM andiswa_business.users;   -- should be 2
   SELECT COUNT(*) FROM andiswa_business.quotes;  -- should be 1
   ```

Run the file only once. If it stops halfway, delete the database (`DROP DATABASE andiswa_business;`) and run it again.

---

## Part 4 – Connect the website to Azure

1. In the website folder, copy `config.local.example.php` and name the copy **`config.local.php`**.
2. Fill in your server name, admin username and password (see the example inside).
3. Open `http://localhost/Andiswa-Business-Consultations-Website/login.php` and log in as `admin` with your **old XAMPP admin password**. It was copied to Azure with the data.
4. Open **Website & App** in the admin menu. At the top it should say
   *Database server: logicwave-andiswa-business-consultation.mysql.database.azure.com · encrypted connection*.
5. Optional: run `setup.php` once. On a database that is already up to date it changes nothing, and it never deletes data. Then delete `setup.php`.

Every teammate who runs the website needs their own `config.local.php` and the `.pem` file. Share the password privately (WhatsApp, not GitHub).

---

## Part 5 – Connect the API (and the app) to Azure

### 5a. Test on your PC first

In Visual Studio, right-click the **AndiswaConsultationApp.Api** project → **Manage User Secrets** and paste this (with your details):

```json
{
  "ConnectionStrings": {
    "AndiswaDb": "Server=logicwave-andiswa-business-consultation.mysql.database.azure.com;Port=3306;Database=andiswa_business;User=logicwaveadmin;Password=YOUR-PASSWORD;SslMode=Required;"
  },
  "Database": {
    "ServerType": "MySql",
    "ServerVersion": "8.0.36"
  }
}
```

User Secrets stay on your PC and never go to GitHub. Run the API and open `http://localhost:5080/api/health`. You should see `{"status":"ok","database":"connected"}`.

### 5b. Put the API on Azure

1. Right-click the API project → **Publish** → **Azure** → **Azure App Service (Windows)** → **Create new**. Use resource group `logicwave-rg` and the **Free (F1)** plan → **Publish**.
2. In the portal, open the new App Service → **Settings → Environment variables**:
   - **Connection strings** tab → **+ Add**: Name `AndiswaDb`, Value = the same connection string as above, Type **MySQL**.
   - **App settings** tab → **+ Add**: `Database__ServerType` = `MySql` and `Database__ServerVersion` = `8.0.36` (two underscores).
   - **Apply**, then **Overview → Restart**.
3. Open `https://YOUR-API-NAME.azurewebsites.net/api/health` and check that it says `connected`.

### 5c. Point the app at the Azure API

In the app project, `Services/ApiConfig.cs`:

```csharp
public const string AzureApiUrl = "https://YOUR-API-NAME.azurewebsites.net/api/";
public static bool UseAzure { get; set; } = true;
```

---

## Part 6 – Check that the sharing works

1. **App → website:** send a message from the app's Contact page. In the website **Admin → Quotes** it appears with a blue **Mobile app** label.
2. **Website → app:** in **Admin → Website & App**, change the phone number and save. The website Contact page shows it straight away. In the app, pull down to refresh the Home or Contact page.
3. Send a quote from the website Contact page. It shows with a grey **Website** label.

---

## If something goes wrong

| Message | What it means / what to do |
|---|---|
| `SQLSTATE[HY000] [2002] Connection timed out` | Your IP address is not in the firewall. Portal → your server → **Networking** → **+ Add current client IP address** → **Save**. Your IP changes when you switch between home Wi-Fi, campus and mobile data. |
| `Connections using insecure transport are prohibited` | Encryption is off. Website: check that the `.pem` file is next to `config.php`. API: add `SslMode=Required;` to the connection string. |
| `the Azure certificate file was not found` | The `.pem` file is missing or has another name (Part 2). |
| `Access denied for user` | Wrong username or password. On Flexible Server the username is just `logicwaveadmin`, without `@servername`. |
| `Multiple primary key defined` while importing | You used the phpMyAdmin export. Use `andiswa_business_azure.sql`. |
| API error `Unknown column 'source'` | The database is older than this update. Run `setup.php` once against it, or import `andiswa_business_azure.sql`. |
| `/api/health` shows `503` | The connection string or the App settings in 5b are wrong, or **Allow public access from any Azure service** is not ticked. |
| Region not allowed when creating the server | Student subscriptions only allow some regions. Choose another region. |

---

## Saving your free credit

- Portal → your MySQL server → **Overview → Stop** when you are not working on it (for example during holidays). **Start** it again before a demo. Azure starts a stopped server again by itself after 30 days.
- The free offer is for **one** B1ms server. Don't create a second one.
- After marking, you can delete the whole resource group `logicwave-rg` to remove everything.

## Optional – hosting the PHP website on Azure too

If you also put the website on Azure App Service (PHP), don't upload `config.local.php`. Under **Environment variables → App settings**, add instead:
`DB_HOST`, `DB_USER`, `DB_PASS`, `DB_NAME` (= `andiswa_business`), and `BASE_URL` (= your website address).
Upload `DigiCertGlobalRootG2.crt.pem` with the site. `config.php` switches encryption on by itself for any `*.mysql.database.azure.com` server.
