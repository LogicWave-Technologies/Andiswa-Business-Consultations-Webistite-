<?php
require_once 'config.php';
require_once 'db_upgrade.php';

// Creates the tables (database.sql), adds the app settings (azure_additions.sql),
// upgrades an older database (db_upgrade.php) and creates the first admin account.
// Works on local XAMPP and on Azure. Running it again never deletes data.
$message = '';
$ok = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    check_csrf();
    $password = $_POST['password'] ?? '';

    if (strlen($password) < 10) {
        $message = 'Please choose an admin password with at least 10 characters.';
    } else {
        try {
            $pdo = db_server();

            foreach (['database.sql', 'azure_additions.sql'] as $file) {
                $path = __DIR__ . '/' . $file;
                if (!file_exists($path)) continue;
                // The SQL files say "andiswa_business". If you chose another DB_NAME, use that instead.
                $sql = str_replace('andiswa_business', DB_NAME, file_get_contents($path));
                foreach (array_filter(array_map('trim', preg_split('/;\s*(?:\r?\n|$)/', $sql))) as $statement) {
                    $pdo->exec($statement);
                }
            }

            $changes = upgrade_schema(db());

            $check = $pdo->prepare('SELECT id FROM ' . DB_NAME . '.users WHERE username=? LIMIT 1');
            $check->execute(['admin']);
            if (!$check->fetch()) {
                $hash = password_hash($password, PASSWORD_DEFAULT);
                $st = $pdo->prepare('INSERT INTO ' . DB_NAME . '.users(name,email,username,password_hash,role,must_change_password) VALUES(?,?,?,?,"admin",0)');
                $st->execute(['System Administrator', 'admin@localhost', 'admin', $hash]);
                $message = 'Setup completed on ' . DB_HOST . '. Admin username: admin (use the password you just chose).';
            } else {
                $message = 'Setup completed. The admin account already existed, so its password was NOT changed.';
            }
            if ($changes) $message .= ' Database upgraded: ' . implode('; ', $changes) . '.';
            $ok = true;
        } catch (Throwable $e) {
            $message = 'Setup failed: ' . $e->getMessage();
        }
    }
}
?>
<!doctype html>
<html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Setup</title>
<style>
body{font-family:Arial,sans-serif;max-width:640px;margin:60px auto;padding:20px;color:#232830}
input{width:100%;padding:12px;border:1px solid #ccd2d9;border-radius:8px;font-size:15px;margin:8px 0 16px}
button,a.btn{display:inline-block;padding:12px 18px;background:#24384f;color:#fff;text-decoration:none;border:0;border-radius:8px;font-weight:700;cursor:pointer}
.msg{padding:12px;border-radius:8px;background:#eef6fd;margin-bottom:18px}.bad{background:#fff0ed;color:#9b3927}
</style></head><body>
<h1>Andiswa Business Consultation Setup</h1>
<p>Database server: <strong><?= e(DB_HOST) ?></strong> &middot; database: <strong><?= e(DB_NAME) ?></strong></p>
<?php if ($message): ?><div class="msg <?= $ok ? '' : 'bad' ?>"><?= e($message) ?></div><?php endif; ?>

<?php if (!$ok): ?>
<form method="post">
  <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
  <label for="password"><strong>Choose the admin password</strong> (at least 10 characters)</label>
  <input id="password" type="password" name="password" minlength="10" required>
  <button type="submit">Create tables and admin account</button>
</form>
<?php else: ?>
<p><strong>Important:</strong> now delete or rename <code>setup.php</code>.</p>
<a class="btn" href="login.php">Go to login</a>
<?php endif; ?>
</body></html>
