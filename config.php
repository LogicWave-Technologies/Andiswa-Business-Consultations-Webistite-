<?php
// XAMPP / MySQL configuration
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

const DB_HOST = '127.0.0.1';
const DB_NAME = 'andiswa_business';
const DB_USER = 'root';
const DB_PASS = '';

function db(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        $pdo = new PDO(
            'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4',
            DB_USER,
            DB_PASS,
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]
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
    $body = "Hello {$name},\n\nYour quote has been accepted. Your client account has been created.\n\nLogin: " . (defined('BASE_URL') ? BASE_URL : 'http://localhost/Andiswa-Business-Consultations-Website-v8') . "/login.php\nUsername: {$username}\nTemporary password: {$password}\n\nPlease change your password after logging in.\n\nRegards,\nAndiswa Business Consultation";
    $headers = "From: no-reply@andiswa.local\r\nContent-Type: text/plain; charset=UTF-8\r\n";
    return @mail($email, $subject, $body, $headers);
}
