<?php
require_once 'config.php';
$message = '';
try {
    $pdo = new PDO('mysql:host=' . DB_HOST . ';charset=utf8mb4', DB_USER, DB_PASS, [PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]);
    $sql = file_get_contents(__DIR__ . '/database.sql');
    foreach (array_filter(array_map('trim', preg_split('/;\s*(?:\r?\n|$)/', $sql))) as $statement) $pdo->exec($statement);
    $check = $pdo->prepare('SELECT id FROM andiswa_business.users WHERE username=? LIMIT 1');
    $check->execute(['admin']);
    if (!$check->fetch()) {
        $hash = password_hash('Admin@2026', PASSWORD_DEFAULT);
        $st = $pdo->prepare('INSERT INTO andiswa_business.users(name,email,username,password_hash,role,must_change_password) VALUES(?,?,?,?,"admin",0)');
        $st->execute(['System Administrator','admin@localhost','admin',$hash]);
    }
    $message = 'Setup completed. Admin username: admin | Admin password: Admin@2026';
} catch (Throwable $e) { $message = 'Setup failed: ' . $e->getMessage(); }
?>
<!doctype html><html><head><meta charset="utf-8"><title>Setup</title><style>body{font-family:Arial;max-width:700px;margin:60px auto;padding:20px}a{display:inline-block;margin-top:20px;padding:12px 18px;background:#24384f;color:white;text-decoration:none;border-radius:6px}</style></head><body><h1>Andiswa Business Consultation Setup</h1><p><?=e($message)?></p><p><strong>Important:</strong> delete or rename <code>setup.php</code> after setup.</p><a href="login.php">Go to login</a></body></html>
