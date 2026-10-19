<?php
// XAMPP / MySQL configuration
// Works with BOTH: the local XAMPP MySQL (default) and Azure Database for MySQL.
// To use Azure, create the file config.local.php next to this one (see config.local.example.php).
session_start();

// ---------------------------------------------------------------------
// Never let the browser (or a proxy) cache these dynamic, session-bound
// pages. Without this, a browser can serve a stale cached copy of the
// login form (with an old, now-invalid CSRF token) after logging out,
// making the first login attempt fail until a fresh copy loads. It can
// also serve a cached copy of an authenticated page like admin.php,
// which looks like it "skipped" the login check.
// ---------------------------------------------------------------------
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: 0');

// ---------------------------------------------------------------------
// Database details - the SAME database the mobile app's API uses.
//
// Where the details come from (first one found wins):
//   1. config.local.php next to this file (your PC, NOT uploaded to GitHub)
//   2. Environment variables DB_HOST, DB_PORT, DB_NAME, DB_USER, DB_PASS, DB_SSL_CA, DB_SSL
//      (use these when the website itself is hosted on Azure App Service:
//       Configuration > Application settings)
//   3. The normal XAMPP defaults below
// ---------------------------------------------------------------------
if (file_exists(__DIR__ . '/config.local.php')) {
    require __DIR__ . '/config.local.php';
}
function env_or(string $name, $default) {
    $value = getenv($name);
    return ($value === false || $value === '') ? $default : $value;
}
defined('DB_HOST')   || define('DB_HOST', env_or('DB_HOST', '127.0.0.1'));
defined('DB_PORT')   || define('DB_PORT', (int)env_or('DB_PORT', 3306));
defined('DB_NAME')   || define('DB_NAME', env_or('DB_NAME', 'andiswa_business'));
defined('DB_USER')   || define('DB_USER', env_or('DB_USER', 'root'));
defined('DB_PASS')   || define('DB_PASS', env_or('DB_PASS', ''));
defined('DB_SSL_CA') || define('DB_SSL_CA', env_or('DB_SSL_CA', ''));   // path to the Azure certificate file; empty for local XAMPP
// Azure servers always end in ".mysql.database.azure.com" and always need encryption,
// so we switch it on automatically for them (DB_SSL can still force it on/off).
defined('DB_SSL')    || define('DB_SSL', filter_var(env_or('DB_SSL', str_ends_with(strtolower(DB_HOST), '.mysql.database.azure.com') ? '1' : (DB_SSL_CA !== '' ? '1' : '0')), FILTER_VALIDATE_BOOLEAN));
defined('BASE_URL')  || define('BASE_URL', env_or('BASE_URL', 'http://localhost/Andiswa-Business-Consultations-Website'));

function db_options(): array {
    $options = [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC];
    if (DB_SSL) {
        // Azure only accepts encrypted connections. Giving PHP a certificate file turns encryption on.
        $ca = DB_SSL_CA !== '' ? DB_SSL_CA : __DIR__ . '/DigiCertGlobalRootG2.crt.pem';
        if (!is_file($ca)) {
            http_response_code(500);
            exit('Database setup problem: the Azure certificate file was not found at ' . htmlspecialchars($ca)
               . '. Download DigiCertGlobalRootG2.crt.pem (see AZURE_SETUP_GUIDE.md) and put it next to config.php.');
        }
        $options[PDO::MYSQL_ATTR_SSL_CA] = $ca;
        // Azure can change its certificate chain over time. The connection stays encrypted either way;
        // we skip the strict certificate check so the site does not suddenly stop working.
        $options[PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT] = false;
    }
    return $options;
}

// Connection to the server WITHOUT choosing a database (used by setup.php to create the tables)
function db_server(): PDO {
    return new PDO('mysql:host=' . DB_HOST . ';port=' . DB_PORT . ';charset=utf8mb4', DB_USER, DB_PASS, db_options());
}

function db(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        $pdo = new PDO(
            'mysql:host=' . DB_HOST . ';port=' . DB_PORT . ';dbname=' . DB_NAME . ';charset=utf8mb4',
            DB_USER,
            DB_PASS,
            db_options()
        );
    }
    return $pdo;
}

function e($value): string { return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8'); }
function redirect(string $url): never { header('Location: ' . $url); exit; }
function flash(string $type, string $message): void { $_SESSION['flash'] = ['type'=>$type, 'message'=>$message]; }
function get_flash(): ?array { $f = $_SESSION['flash'] ?? null; unset($_SESSION['flash']); return $f; }
function csrf_token(): string { if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(32)); return $_SESSION['csrf']; }
function check_csrf(): void { if (!hash_equals($_SESSION['csrf'] ?? '', $_POST['csrf'] ?? '')) { http_response_code(419); exit('Invalid security token. Please go back and try again.'); } }
function logged_in(): bool { return !empty($_SESSION['user']); }
function require_login(?string $role = null): void {
    if (!logged_in()) redirect('login.php');
    if ($role !== null && ($_SESSION['user']['role'] ?? '') !== $role) redirect('dashboard.php');
}
function current_user(): ?array { return $_SESSION['user'] ?? null; }

function send_credentials_email(string $email, string $name, string $username, string $password): bool {
    $subject = 'Your Andiswa Business Consultation login details';
    $body = "Hello {$name},\n\nYour quote has been accepted. Your client account has been created.\n\nLogin: " . BASE_URL . "/login.php\nUsername: {$username}\nTemporary password: {$password}\n\nPlease change your password after logging in.\n\nRegards,\nAndiswa Business Consultation";
    $smtpHost = getenv('SMTP_HOST') ?: '';

    if ($smtpHost !== '') {
        $autoload = __DIR__ . '/vendor/autoload.php';
        if (!is_file($autoload)) {
            error_log('SMTP email could not be sent: Composer dependencies are missing. Run composer install.');
            return false;
        }

        require_once $autoload;

        $smtpUsername = getenv('SMTP_USERNAME') ?: '';
        $smtpPassword = getenv('SMTP_PASSWORD') ?: '';
        if (($smtpUsername === '') !== ($smtpPassword === '')) {
            error_log('SMTP email could not be sent: SMTP_USERNAME and SMTP_PASSWORD must both be set or both be empty.');
            return false;
        }

        $encryption = strtolower(getenv('SMTP_ENCRYPTION') ?: 'tls');
        if (!in_array($encryption, ['tls', 'ssl'], true)) {
            error_log('SMTP email could not be sent: SMTP_ENCRYPTION must be tls or ssl.');
            return false;
        }

        try {
            $mailer = new PHPMailer\PHPMailer\PHPMailer(true);
            $mailer->isSMTP();
            $mailer->Host = $smtpHost;
            $mailer->Port = (int) (getenv('SMTP_PORT') ?: 587);
            $mailer->SMTPAuth = $smtpUsername !== '';
            $mailer->Username = $smtpUsername;
            $mailer->Password = $smtpPassword;
            $mailer->SMTPSecure = $encryption === 'ssl'
                ? PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS
                : PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
            $mailer->CharSet = PHPMailer\PHPMailer\PHPMailer::CHARSET_UTF8;
            $mailer->setFrom(
                getenv('SMTP_FROM_EMAIL') ?: 'no-reply@andiswa.local',
                getenv('SMTP_FROM_NAME') ?: 'Andiswa Business Consultation'
            );
            $mailer->addAddress($email, $name);
            $mailer->Subject = $subject;
            $mailer->Body = $body;
            $mailer->send();
            return true;
        } catch (PHPMailer\PHPMailer\Exception $exception) {
            error_log('SMTP email could not be sent: ' . $exception->getMessage());
            return false;
        }
    }

    $headers = "From: no-reply@andiswa.local\r\nContent-Type: text/plain; charset=UTF-8\r\n";
    return @mail($email, $subject, $body, $headers);
}

// ---------------------------------------------------------------------
// SHARED SETTINGS (the "settings" table)
// The website, the admin page and the mobile app's API (GET /api/studio) all read
// the same rows, so a change in Admin > Website & App shows up everywhere.
// ---------------------------------------------------------------------

// Default text, used only when a row is missing or empty (same fallbacks as the API).
const SETTING_DEFAULTS = [
    'studio'        => 'Andiswa Business Consultation',
    'tagline'       => 'WE DESIGN. YOU GROW.',
    'hero_text'     => 'Andiswa Business Consultation crafts bold visual identities and digital experiences that make South African businesses impossible to ignore.',
    'about_text1'   => 'Andiswa Business Consultation is a Port Elizabeth based creative agency founded on the belief that exceptional design is the single most powerful investment any business can make. We blend strategic thinking with visual craft to build brands that resonate, differentiate, and endure.',
    'about_text2'   => 'From early-stage startups defining their first identity to established businesses evolving for new markets, we bring the same level of care, craft, and creative ambition to every brief.',
    'email'         => 'andiswasbusinessconsultations@outlook.com',
    'phone'         => '+27 67 790 4428',
    'address'       => '2 Belmont Terrace, Port Elizabeth, 6001',
    'hours'         => 'Monday – Friday, 08:00 – 18:00',
    'whatsapp'      => '27677904428',
    'owner_name'    => 'Andiswa Phewa',
    'owner_title'   => 'Creative Director',
    'stat1_value'   => '50+', 'stat1_label' => 'Projects Completed',
    'stat2_value'   => '40+', 'stat2_label' => 'Happy Clients',
    'stat3_value'   => '5+',  'stat3_label' => 'Years Experience',
    'stat4_value'   => '98%', 'stat4_label' => 'Client Satisfaction',
    'facebook_url'  => '', 'instagram_url' => '', 'twitter_url' => '', 'linkedin_url' => '', 'youtube_url' => '',
];

// All settings as key => value. Read once per page.
function settings_all(): array {
    static $cache = null;
    if ($cache === null) {
        $cache = SETTING_DEFAULTS;
        try {
            foreach (db()->query('SELECT setting_key, setting_value FROM settings') as $row) {
                if (trim((string)$row['setting_value']) !== '' || !isset(SETTING_DEFAULTS[$row['setting_key']])
                    || str_ends_with($row['setting_key'], '_url')) {
                    $cache[$row['setting_key']] = (string)$row['setting_value'];
                }
            }
        } catch (Throwable $e) {
            // Database not reachable: the page still shows the default text.
        }
    }
    return $cache;
}

function setting(string $key): string { return settings_all()[$key] ?? ''; }

// Saves one setting (insert, or update if it exists).
function save_setting(string $key, string $value): void {
    $st = db()->prepare('INSERT INTO settings(setting_key, setting_value) VALUES(?, ?) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)');
    $st->execute([$key, $value]);
}
