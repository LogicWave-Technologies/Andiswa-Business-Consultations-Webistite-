<?php
require_once 'config.php';
if (logged_in()) redirect(($_SESSION['user']['role'] ?? '') === 'admin' ? 'admin.php' : 'dashboard.php');
$error='';
if ($_SERVER['REQUEST_METHOD']==='POST') {
  check_csrf();
  $username=trim($_POST['username']??''); $password=$_POST['password']??'';
  $st=db()->prepare('SELECT * FROM users WHERE username=? LIMIT 1'); $st->execute([$username]); $u=$st->fetch();
  if ($u && password_verify($password,$u['password_hash'])) {
    session_regenerate_id(true);
    $_SESSION['user']=['id'=>$u['id'],'name'=>$u['name'],'email'=>$u['email'],'username'=>$u['username'],'role'=>$u['role'],'must_change_password'=>(int)$u['must_change_password']];
    redirect($u['role']==='admin'?'admin.php':'dashboard.php');
  }
  $error='Invalid username or password.';
}
?><!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Login — Andiswa Business Consultation</title><style>
*{box-sizing:border-box}body{margin:0;font-family:Arial,sans-serif;background:#f7f8fa;color:#232830;min-height:100vh;display:grid;place-items:center}.card{width:min(440px,92vw);background:#fff;border:1px solid #ddd;border-radius:14px;padding:32px;box-shadow:0 15px 45px #00000012}h1{margin:0 0 8px;font-size:26px}p{color:#68717d}.field{margin:18px 0}.field label{display:block;font-size:13px;font-weight:700;margin-bottom:7px}.field input{width:100%;padding:13px;border:1px solid #ccd2d9;border-radius:8px;font-size:15px}button{width:100%;padding:13px;border:0;border-radius:8px;background:#24384f;color:#fff;font-weight:700;cursor:pointer}.error{background:#fff0ed;color:#9b3927;padding:12px;border-radius:8px}.links{margin-top:18px;text-align:center}.links a{color:#24384f;font-weight:700;text-decoration:none}
</style></head><body><main class="card"><h1>Client / Admin Login</h1><p>Use the credentials supplied by Andiswa Business Consultation.</p><?php if($error):?><div class="error"><?=e($error)?></div><?php endif;?><form method="post"><input type="hidden" name="csrf" value="<?=e(csrf_token())?>"><div class="field"><label>Username</label><input name="username" autocomplete="username" required></div><div class="field"><label>Password</label><input type="password" name="password" autocomplete="current-password" required></div><button>Log in</button></form><div class="links"><a href="index.html">← Back to website</a></div></main></body></html>
