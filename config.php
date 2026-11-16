<?php
// PHP site configuration. Quote, account, admin and client data use the published API.
// MySQL remains only for the existing settings editor and chatbot log.
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
// Optional MySQL configuration for the settings editor and chatbot log.
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

defined('API_BASE_URL') || define('API_BASE_URL', rtrim(env_or(
    'API_BASE_URL',
    'https://andiswa-business-api-buc0hwgybmhnabhs.southafricanorth-01.azurewebsites.net/api'
), '/'));

final class ApiException extends RuntimeException {
    public int $status;

    public function __construct(int $status, string $message) {
        parent::__construct($message);
        $this->status = $status;
    }
}

function api_request(string $method, string $path, ?array $payload = null, ?string $token = null): array {
    $headers = ['Accept: application/json'];
    if ($payload !== null) {
        $headers[] = 'Content-Type: application/json';
    }
    if ($token !== null) {
        $headers[] = 'Authorization: Bearer ' . $token;
    }

    $url = API_BASE_URL . '/' . ltrim($path, '/');
    $body = $payload !== null ? json_encode($payload, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE) : null;
    if (function_exists('curl_init')) {
        $curl = curl_init($url);
        if ($curl === false) {
            throw new RuntimeException('Could not initialize the business API request.');
        }

        $options = [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST => strtoupper($method),
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_TIMEOUT => 20,
            CURLOPT_HTTPHEADER => $headers,
        ];
        if ($body !== null) {
            $options[CURLOPT_POSTFIELDS] = $body;
        }
        curl_setopt_array($curl, $options);
        $responseBody = curl_exec($curl);
        $requestError = curl_error($curl);
        $status = (int)curl_getinfo($curl, CURLINFO_RESPONSE_CODE);
        curl_close($curl);
    } else {
        if (!ini_get('allow_url_fopen') || !extension_loaded('openssl')) {
            throw new RuntimeException('Enable PHP cURL or HTTPS streams to connect to the business API.');
        }

        $httpOptions = [
            'method' => strtoupper($method),
            'header' => implode("\r\n", $headers),
            'timeout' => 20,
            'ignore_errors' => true,
            'follow_location' => 0,
        ];
        if ($body !== null) {
            $httpOptions['content'] = $body;
        }
        $context = stream_context_create([
            'http' => $httpOptions,
            'ssl' => ['verify_peer' => true, 'verify_peer_name' => true],
        ]);
        $requestError = '';
        set_error_handler(static function (int $severity, string $message) use (&$requestError): bool {
            $requestError = $message;
            return true;
        }, E_WARNING);
        try {
            $responseBody = file_get_contents($url, false, $context);
        } finally {
            restore_error_handler();
        }
        $status = 0;
        if (isset($http_response_header[0]) && preg_match('/^HTTP\/\S+ (\d{3})/', $http_response_header[0], $matches)) {
            $status = (int)$matches[1];
        }
    }

    if ($responseBody === false) {
        error_log('Business API request failed: ' . $requestError);
        throw new RuntimeException('The business API could not be reached. Please try again later.');
    }

    try {
        $response = json_decode($responseBody, true, 512, JSON_THROW_ON_ERROR);
    } catch (JsonException $exception) {
        if ($status >= 200 && $status < 300) {
            error_log('Business API returned invalid JSON: ' . $exception->getMessage());
            throw new RuntimeException('The business API returned an invalid response.');
        }
        $response = [];
    }

    if ($status < 200 || $status >= 300) {
        if ($status === 401 && $token !== null) {
            unset($_SESSION['user'], $_SESSION['api_token']);
            flash('error', 'Your session expired. Please sign in again.');
            redirect('login.php');
        }
        $message = is_array($response)
            ? (string)($response['error'] ?? $response['detail'] ?? $response['title'] ?? '')
            : '';
        throw new ApiException($status, $message !== '' ? $message : 'The business API request failed (HTTP ' . $status . ').');
    }

    if (!is_array($response)) {
        throw new RuntimeException('The business API returned an unexpected response.');
    }
    return $response;
}

function public_base_url(): string {
    $environmentUrl = getenv('BASE_URL');
    if ($environmentUrl !== false && trim($environmentUrl) !== '') {
        return rtrim(trim($environmentUrl), '/');
    }
    return defined('BASE_URL') ? rtrim(BASE_URL, '/') : '';
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
defined('BASE_URL')  || define('BASE_URL', rtrim(env_or('BASE_URL', ''), '/'));

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
    if (empty($_SESSION['api_token'])) {
        unset($_SESSION['user']);
        flash('error', 'Please sign in again to continue.');
        redirect('login.php');
    }
    if ($role !== null && ($_SESSION['user']['role'] ?? '') !== $role) redirect('dashboard.php');
}
function current_user(): ?array { return $_SESSION['user'] ?? null; }

function smtp_settings(): ?array {
    $localConfigPath = __DIR__ . '/config.smtp.local.php';
    $localConfig = [];
    if (is_file($localConfigPath)) {
        $loadedConfig = require $localConfigPath;
        if (!is_array($loadedConfig)) {
            error_log('SMTP email could not be sent: config.smtp.local.php must return an array.');
            return null;
        }
        $localConfig = $loadedConfig;
    }

    $value = static function (string $key, string $environmentVariable, $default = '') use ($localConfig) {
        $environmentValue = getenv($environmentVariable);
        if ($environmentValue !== false && $environmentValue !== '') {
            return $environmentValue;
        }
        return $localConfig[$key] ?? $default;
    };

    $smtpHost = trim((string) $value('host', 'SMTP_HOST'));
    if ($smtpHost === '') {
        error_log('SMTP email could not be sent: SMTP_HOST is not configured.');
        return null;
    }

    $smtpUsername = trim((string) $value('username', 'SMTP_USERNAME'));
    $smtpPassword = (string) $value('password', 'SMTP_PASSWORD');
    if ($smtpUsername === '' || $smtpPassword === '') {
        error_log('SMTP email could not be sent: set the Gmail address and app password in config.smtp.local.php or SMTP_USERNAME and SMTP_PASSWORD.');
        return null;
    }

    $encryption = strtolower((string) $value('encryption', 'SMTP_ENCRYPTION', 'tls'));
    if (!in_array($encryption, ['tls', 'ssl'], true)) {
        error_log('SMTP email could not be sent: SMTP_ENCRYPTION must be tls or ssl.');
        return null;
    }

    $smtpPort = $value('port', 'SMTP_PORT', '587');
    $port = filter_var($smtpPort === false || $smtpPort === '' ? '587' : $smtpPort, FILTER_VALIDATE_INT, [
        'options' => ['min_range' => 1, 'max_range' => 65535],
    ]);
    if ($port === false) {
        error_log('SMTP email could not be sent: SMTP_PORT must be between 1 and 65535.');
        return null;
    }

    $fromEmail = (string) $value('from_email', 'SMTP_FROM_EMAIL', $smtpUsername);
    if (!filter_var($fromEmail, FILTER_VALIDATE_EMAIL)) {
        error_log('SMTP email could not be sent: set SMTP_FROM_EMAIL to a valid sender address or use a valid SMTP_USERNAME.');
        return null;
    }

    return [
        'host' => $smtpHost,
        'port' => $port,
        'username' => $smtpUsername,
        'password' => $smtpPassword,
        'encryption' => $encryption,
        'from_email' => $fromEmail,
        'from_name' => (string) $value('from_name', 'SMTP_FROM_NAME', 'Andiswa Business Consultation'),
    ];
}

function send_smtp_email(string $email, string $name, string $subject, string $body, ?string $replyTo = null, ?string $replyToName = null): bool {
    $settings = smtp_settings();
    if ($settings === null) {
        return false;
    }

    $autoload = __DIR__ . '/vendor/autoload.php';
    if (!is_file($autoload)) {
        error_log('SMTP email could not be sent: Composer dependencies are missing. Run composer install.');
        return false;
    }

    require_once $autoload;

    try {
        $mailer = new PHPMailer\PHPMailer\PHPMailer(true);
        $mailer->isSMTP();
        $mailer->Host = $settings['host'];
        $mailer->Port = $settings['port'];
        $mailer->SMTPAuth = true;
        $mailer->Username = $settings['username'];
        $mailer->Password = $settings['password'];
        $mailer->SMTPSecure = $settings['encryption'] === 'ssl'
            ? PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS
            : PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
        $mailer->Timeout = 15;
        $mailer->Timelimit = 20;
        $mailer->CharSet = PHPMailer\PHPMailer\PHPMailer::CHARSET_UTF8;
        $mailer->setFrom($settings['from_email'], $settings['from_name']);
        $mailer->addAddress($email, $name);
        if ($replyTo !== null) {
            $mailer->addReplyTo($replyTo, $replyToName ?? '');
        }
        $mailer->Subject = $subject;
        $mailer->Body = $body;
        $mailer->send();
        return true;
    } catch (PHPMailer\PHPMailer\Exception $exception) {
        error_log('SMTP email could not be sent: ' . $exception->getMessage());
        return false;
    }
}

function send_contact_email(string $name, string $email, string $service, string $message): bool {
    $body = "A new contact request was submitted on the website.\n\n"
        . "Name: {$name}\n"
        . "Email: {$email}\n"
        . "Service: {$service}\n\n"
        . "Project details:\n{$message}\n";
    return send_smtp_email(
        setting('email'),
        setting('owner_name'),
        'New website enquiry: ' . $service,
        $body,
        $email,
        $name
    );
}

function send_credentials_email(string $email, string $name, string $username, string $password): bool {
    $siteUrl = public_base_url();
    if ($siteUrl === '') {
        error_log('Client credentials email could not be sent: configure BASE_URL with the public website URL.');
        return false;
    }
    $subject = 'Your Andiswa Business Consultation login details';
    $body = "Hello {$name},\n\nYour quote has been accepted. Your client account has been created.\n\nLogin: " . $siteUrl . "/login.php\nUsername: {$username}\nTemporary password: {$password}\n\nPlease change your password after logging in.\n\nRegards,\nAndiswa Business Consultation";
    return send_smtp_email($email, $name, $subject, $body);
}

// ---------------------------------------------------------------------
// Shared settings remain on the existing settings table; the API currently
// exposes reads only and does not support the website's settings editor.
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
