<?php
// STEP: copy this file, rename the copy to  config.local.php  and fill in your Azure details.
// config.local.php is listed in .gitignore, so your password is NOT uploaded to GitHub.
// NEVER put the real password in this example file.
//
// The mobile app's API uses the SAME database. Its connection string uses these same
// four values (see AZURE_SETUP_GUIDE.md, Part 5).
//
// No config.local.php at all = the website uses XAMPP (localhost, root, no password).

define('DB_HOST', 'logicwave-andiswa-business-consultation.mysql.database.azure.com');   // LogicWave Azure server
define('DB_PORT', 3306);
define('DB_NAME', 'andiswa_business');
define('DB_USER', 'logicwaveadmin');                              // the admin username you chose (no @servername)
define('DB_PASS', 'YOUR-ADMIN-PASSWORD');

// The certificate file from AZURE_SETUP_GUIDE.md, Part 2. Keep it in the same folder as config.php.
// Encryption switches on automatically for *.mysql.database.azure.com servers.
define('DB_SSL_CA', __DIR__ . '/DigiCertGlobalRootG2.crt.pem');

// The address people use to open the website (used in the "your login details" email).
define('BASE_URL', 'http://localhost/Andiswa-Business-Consultations-Website');
