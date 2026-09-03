<?php
require_once 'config.php'; require_login('admin');
$pdo=db(); $u=current_user();
if($_SERVER['REQUEST_METHOD']==='POST'){
 check_csrf(); $action=$_POST['action']??'';
 try {
  if($action==='quote_status'){
   $id=(int)$_POST['quote_id']; $status=$_POST['status']; $allowed=['New','Under Review','Accepted','Rejected','Completed']; if(!in_array($status,$allowed,true)) throw new Exception('Invalid status.');
   $st=$pdo->prepare('SELECT * FROM quotes WHERE id=?');$st->execute([$id]);$quote=$st->fetch(); if(!$quote) throw new Exception('Quote not found.');
   if($status==='Accepted' && empty($quote['client_id'])){
    $pdo->beginTransaction();
    $st=$pdo->prepare('SELECT * FROM users WHERE email=? LIMIT 1');$st->execute([$quote['email']]);$client=$st->fetch();
    $plain='AB!'.strtoupper(bin2hex(random_bytes(4))).'#'.random_int(10,99);
    if(!$client){$base=preg_replace('/[^a-z0-9]+/i','.',strtolower($quote['name']));$base=trim($base,'.')?:'client';$username=$base; $n=1; while(true){$s=$pdo->prepare('SELECT id FROM users WHERE username=?');$s->execute([$username]);if(!$s->fetch())break;$username=$base.$n++;}
      $s=$pdo->prepare('INSERT INTO users(name,email,username,password_hash,role,must_change_password) VALUES(?,?,?,?,"client",1)');$s->execute([$quote['name'],$quote['email'],$username,password_hash($plain,PASSWORD_DEFAULT)]);$clientId=(int)$pdo->lastInsertId();
    } else {$clientId=(int)$client['id'];$username=$client['username'];$s=$pdo->prepare('UPDATE users SET password_hash=?,must_change_password=1 WHERE id=?');$s->execute([password_hash($plain,PASSWORD_DEFAULT),$clientId]);}
    $s=$pdo->prepare('UPDATE quotes SET status=?,client_id=? WHERE id=?');$s->execute([$status,$clientId,$id]);
    $pdo->commit();
    $sent=send_credentials_email($quote['email'],$quote['name'],$username,$plain);
    $_SESSION['credentials']=['name'=>$quote['name'],'email'=>$quote['email'],'username'=>$username,'password'=>$plain,'email_sent'=>$sent];
    flash('success',$sent?'Quote accepted and credentials emailed to the client.':'Quote accepted and credentials generated. Email was not sent; use the credentials shown below to contact the client.');
   } else {$s=$pdo->prepare('UPDATE quotes SET status=?,admin_note=? WHERE id=?');$s->execute([$status,trim($_POST['admin_note']??''),$id]);flash('success','Quote status updated.');}
  } elseif($action==='create_project'){
   $client=(int)$_POST['client_id'];$title=trim($_POST['title']);$desc=trim($_POST['description']??'');$status=$_POST['project_status'];$progress=max(0,min(100,(int)$_POST['progress']));$start=$_POST['start_date']?:null;$due=$_POST['due_date']?:null;
   $s=$pdo->prepare('INSERT INTO projects(client_id,title,description,status,progress,start_date,due_date) VALUES(?,?,?,?,?,?,?)');$s->execute([$client,$title,$desc,$status,$progress,$start,$due]);flash('success','Project created.');
  } elseif($action==='update_project'){
   $id=(int)$_POST['project_id'];$status=$_POST['project_status'];$progress=max(0,min(100,(int)$_POST['progress']));$text=trim($_POST['update_text']??'');
   $s=$pdo->prepare('UPDATE projects SET status=?,progress=? WHERE id=?');$s->execute([$status,$progress,$id]); if($text!==''){ $s=$pdo->prepare('INSERT INTO project_updates(project_id,update_text) VALUES(?,?)');$s->execute([$id,$text]); } flash('success','Project progress updated.');
  }
 } catch(Throwable $e){ if($pdo->inTransaction())$pdo->rollBack(); flash('error',$e->getMessage()); }
 // Send the admin back to whichever tab the action belongs to, instead of always
 // dropping them back at the Dashboard tab.
 $returnTab = $action==='quote_status' ? 'quotes' : (in_array($action,['create_project','update_project'],true) ? 'projects' : 'dashboard');
 redirect('admin.php?tab='.$returnTab);
}
$quotes=$pdo->query('SELECT q.*,u.username FROM quotes q LEFT JOIN users u ON u.id=q.client_id ORDER BY q.created_at DESC')->fetchAll();
$clients=$pdo->query("SELECT * FROM users WHERE role='client' ORDER BY created_at DESC")->fetchAll();
$projects=$pdo->query('SELECT p.*,u.name client_name,u.email FROM projects p JOIN users u ON u.id=p.client_id ORDER BY p.updated_at DESC')->fetchAll();
$stats=['quotes'=>count($quotes),'new'=>count(array_filter($quotes,fn($q)=>$q['status']==='New')),'clients'=>count($clients),'projects'=>count($projects)];
$f=get_flash();$credentials=$_SESSION['credentials']??null;unset($_SESSION['credentials']);

// ---------------------------------------------------------------
// Which tab is active. Plain query param, so it works with or
// without JS and survives a full page reload after a form submit.
// ---------------------------------------------------------------
$tabTitles = ['dashboard'=>'Dashboard','quotes'=>'Quote Requests','clients'=>'Clients','projects'=>'Projects'];
$tab = $_GET['tab'] ?? 'dashboard';
if(!array_key_exists($tab,$tabTitles)) $tab='dashboard';

function status_class(string $status): string {
  return match($status){
    'New' => 'st-new',
    'Under Review' => 'st-review',
    'Accepted' => 'st-good',
    'Completed' => 'st-good',
    'Rejected' => 'st-bad',
    'On Hold' => 'st-review',
    'In Progress' => 'st-review',
    'Not Started' => 'st-new',
    default => 'st-new',
  };
}
?><!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Admin — Andiswa Business Consultation</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@500;600;700&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css">
<style>
:root{
  --espresso:#1B2A41; --espresso-soft:#24384F; --cream:#F7F8FA; --cream-dim:#EEF1F4;
  --ink:#232830; --tan:#3E6690; --tan-bright:#5B8DB8; --stone:#5B6472;
  --line:rgba(35,40,48,0.12); --good:#2f7a4d; --good-bg:#e9f7ef; --bad:#9b3927; --bad-bg:#fff0ed;
  --review:#8a6d1c; --review-bg:#fff8df;
  --radius:12px; --sidebar-w:250px;
}
*{box-sizing:border-box;}
html,body{margin:0;padding:0;}
body{
  font-family:'Inter',Arial,sans-serif; background:var(--cream-dim); color:var(--ink);
  -webkit-font-smoothing:antialiased;
}
h1,h2,h3{font-family:'Space Grotesk',sans-serif; margin:0;}
a{color:inherit;}
button{font-family:inherit;}

/* ---------------- layout shell ---------------- */
.shell{display:flex; min-height:100vh;}

/* ---------------- sidebar ---------------- */
.sidebar{
  width:var(--sidebar-w); flex-shrink:0; background:var(--espresso); color:#fff;
  display:flex; flex-direction:column; position:fixed; top:0; left:0; bottom:0; z-index:40;
  transition:transform .3s ease;
}
.sidebar-brand{
  display:flex; align-items:center; gap:12px; padding:22px 20px; border-bottom:1px solid rgba(255,255,255,.12);
}
.sidebar-brand .mark{
  width:36px;height:36px;border-radius:10px;background:#0a0a0a;color:#fff;display:flex;
  align-items:center;justify-content:center;font-family:'Space Grotesk',sans-serif;font-weight:700;font-size:13px;flex-shrink:0;
}
.sidebar-brand .label{line-height:1.25;}
.sidebar-brand .label strong{display:block;font-size:14px;}
.sidebar-brand .label span{display:block;font-size:11px;color:rgba(255,255,255,.55);letter-spacing:.04em;text-transform:uppercase;}

.nav-group{padding:16px 12px; flex:1; overflow-y:auto;}
.nav-link{
  display:flex; align-items:center; gap:12px; padding:11px 14px; border-radius:10px;
  color:rgba(255,255,255,.75); text-decoration:none; font-size:14px; font-weight:500; margin-bottom:4px;
  transition:background .2s ease, color .2s ease;
}
.nav-link i{width:18px; text-align:center; font-size:15px;}
.nav-link:hover{background:rgba(255,255,255,.08); color:#fff;}
.nav-link.active{background:var(--tan); color:#fff;}
.nav-link .count{
  margin-left:auto; background:rgba(255,255,255,.16); color:#fff; font-size:11px; font-weight:700;
  padding:2px 7px; border-radius:20px;
}
.nav-link.active .count{background:rgba(255,255,255,.28);}

.sidebar-foot{padding:14px 12px 18px; border-top:1px solid rgba(255,255,255,.12);}
.sidebar-foot a{
  display:flex; align-items:center; gap:12px; padding:11px 14px; border-radius:10px;
  color:rgba(255,255,255,.75); text-decoration:none; font-size:14px; font-weight:500;
}
.sidebar-foot a:hover{background:rgba(255,255,255,.08); color:#fff;}
.sidebar-foot a i{width:18px;text-align:center;}

/* ---------------- main column ---------------- */
.main{flex:1; margin-left:var(--sidebar-w); min-width:0; display:flex; flex-direction:column;}
.topbar{
  background:#fff; border-bottom:1px solid var(--line); padding:16px 28px; display:flex;
  align-items:center; justify-content:space-between; gap:16px; position:sticky; top:0; z-index:20;
}
.topbar-left{display:flex; align-items:center; gap:14px;}
.topbar h1{font-size:20px;}
.sidebar-toggle{
  display:none; width:38px;height:38px;border-radius:9px;border:1px solid var(--line);background:#fff;
  align-items:center;justify-content:center;cursor:pointer;font-size:15px;color:var(--ink);
}
.topbar-right{display:flex; align-items:center; gap:14px;}
.admin-chip{display:flex;align-items:center;gap:9px;font-size:13px;color:var(--stone);}
.admin-chip .avatar{
  width:32px;height:32px;border-radius:50%;background:var(--tan);color:#fff;display:flex;align-items:center;
  justify-content:center;font-weight:700;font-size:13px;font-family:'Space Grotesk',sans-serif;
}
.logout-btn{
  display:inline-flex; align-items:center; gap:8px; padding:9px 16px; border-radius:9px; background:var(--espresso);
  color:#fff; text-decoration:none; font-size:13px; font-weight:600;
}
.logout-btn:hover{background:var(--tan);}

.content{padding:26px 28px 60px; max-width:1300px; width:100%; margin:0 auto;}

.sidebar-backdrop{display:none; position:fixed; inset:0; background:rgba(16,20,27,.5); z-index:30;}
.sidebar-backdrop.open{display:block;}

/* ---------------- panels ---------------- */
.panel-view{display:none;}
.panel-view.active{display:block;}

/* ---------------- notices / credentials ---------------- */
.notice{padding:13px 16px;border-radius:10px;background:var(--good-bg);color:var(--good);margin-bottom:20px;font-size:14px;font-weight:500;}
.notice.error{background:var(--bad-bg);color:var(--bad);}
.credentials{background:var(--review-bg);border:1px solid #e5c45a;padding:18px 20px;border-radius:var(--radius);margin-bottom:20px;}
.credentials h2{font-size:16px;margin-bottom:8px;}
.credentials code{background:#fff;padding:4px 8px;border-radius:6px;font-size:13px;}
.small{font-size:12px;color:var(--stone);}

/* ---------------- stat cards ---------------- */
.cards{display:grid;grid-template-columns:repeat(4,1fr);gap:16px;margin-bottom:26px;}
.stat{
  background:#fff;border:1px solid var(--line);border-radius:var(--radius);padding:20px;
  display:flex; flex-direction:column; gap:10px;
}
.stat .icon{
  width:38px;height:38px;border-radius:10px;background:var(--cream-dim);color:var(--tan);
  display:flex;align-items:center;justify-content:center;font-size:16px;
}
.stat strong{font-size:30px;font-family:'Space Grotesk',sans-serif;}
.stat span{font-size:13px;color:var(--stone);font-weight:500;}

/* ---------------- generic panel / card ---------------- */
.panel{background:#fff;border:1px solid var(--line);border-radius:var(--radius);padding:22px;margin-bottom:22px;}
.panel > h2{font-size:17px;margin-bottom:18px;}
.panel-head{display:flex;align-items:center;justify-content:space-between;margin-bottom:18px;flex-wrap:wrap;gap:10px;}
.panel-head h2{margin-bottom:0;}
.two-col{display:grid;grid-template-columns:1fr 1fr;gap:22px;align-items:start;}

/* quote / project rows */
.row-item{border-top:1px solid var(--line);padding:18px 0;}
.row-item:first-child{border-top:none;padding-top:2px;}
.row-flex{display:flex;justify-content:space-between;gap:20px;flex-wrap:wrap;}
.row-flex > div:first-child{flex:1;min-width:240px;}
.row-flex form{min-width:220px;flex-shrink:0;}
.row-item h3{font-size:15px;margin:6px 0 4px;}
.row-item p{margin:6px 0;font-size:14px;line-height:1.6;color:var(--ink);}

/* status pill */
.pill{display:inline-flex;align-items:center;padding:3px 10px;border-radius:20px;font-size:11px;font-weight:700;letter-spacing:.02em;}
.pill.st-new{background:#e6edf5;color:var(--espresso);}
.pill.st-review{background:var(--review-bg);color:var(--review);}
.pill.st-good{background:var(--good-bg);color:var(--good);}
.pill.st-bad{background:var(--bad-bg);color:var(--bad);}

/* forms */
.field{margin:0 0 12px;}
.field label{display:block;font-weight:700;font-size:12px;margin-bottom:6px;color:var(--stone);text-transform:uppercase;letter-spacing:.03em;}
.field input,.field textarea,.field select{
  width:100%;padding:10px 12px;border:1px solid #ccd2d9;border-radius:8px;font-size:14px;font-family:inherit;background:#fff;color:var(--ink);
}
.field input:focus,.field textarea:focus,.field select:focus{outline:2px solid var(--tan-bright);outline-offset:1px;}
.field textarea{min-height:72px;resize:vertical;}
.btn{
  background:var(--espresso);color:#fff;border:0;padding:10px 16px;border-radius:8px;font-weight:700;
  font-size:13px;cursor:pointer;transition:background .2s ease;
}
.btn:hover{background:var(--tan);}

/* progress bar */
.bar{height:9px;background:#e3e7eb;border-radius:10px;overflow:hidden;margin:8px 0;}
.bar span{display:block;height:100%;background:var(--tan-bright);}

/* clients table */
table{width:100%;border-collapse:collapse;}
th,td{text-align:left;padding:11px 10px;border-bottom:1px solid var(--line);vertical-align:middle;font-size:14px;}
th{font-size:11px;text-transform:uppercase;letter-spacing:.04em;color:var(--stone);font-weight:700;}
.empty{color:var(--stone);font-size:14px;padding:6px 0;}

/* recent list on dashboard */
.mini-row{display:flex;justify-content:space-between;gap:14px;padding:12px 0;border-top:1px solid var(--line);font-size:14px;}
.mini-row:first-child{border-top:none;}
.mini-row .who{font-weight:600;}
.mini-row .svc{color:var(--stone);font-size:13px;}

@media(max-width:1080px){
  .cards{grid-template-columns:repeat(2,1fr);}
  .two-col{grid-template-columns:1fr;}
}
@media(max-width:860px){
  .sidebar{transform:translateX(-100%);}
  .sidebar.open{transform:translateX(0);}
  .main{margin-left:0;}
  .sidebar-toggle{display:flex;}
}
@media(max-width:640px){
  .cards{grid-template-columns:1fr 1fr;}
  .content{padding:20px 16px 50px;}
  .topbar{padding:14px 16px;}
  .row-flex form{width:100%;}
  table{display:block;overflow-x:auto;white-space:nowrap;}
  .admin-chip span:not(.avatar){display:none;}
  .logout-btn span{display:none;}
}
@media(max-width:420px){
  .cards{grid-template-columns:1fr;}
}
</style>
</head>
<body>
<div class="shell">

  <div class="sidebar-backdrop" id="sidebarBackdrop"></div>

  <aside class="sidebar" id="sidebar">
    <div class="sidebar-brand">
      <span class="mark">A|B</span>
      <span class="label"><strong>Andiswa</strong><span>Admin Panel</span></span>
    </div>
    <nav class="nav-group">
      <a href="?tab=dashboard" class="nav-link<?= $tab==='dashboard'?' active':'' ?>" data-tab="dashboard">
        <i class="fa-solid fa-gauge"></i> Dashboard
      </a>
      <a href="?tab=quotes" class="nav-link<?= $tab==='quotes'?' active':'' ?>" data-tab="quotes">
        <i class="fa-solid fa-file-invoice"></i> Quotes
        <span class="count"><?=e($stats['quotes'])?></span>
      </a>
      <a href="?tab=clients" class="nav-link<?= $tab==='clients'?' active':'' ?>" data-tab="clients">
        <i class="fa-solid fa-users"></i> Clients
        <span class="count"><?=e($stats['clients'])?></span>
      </a>
      <a href="?tab=projects" class="nav-link<?= $tab==='projects'?' active':'' ?>" data-tab="projects">
        <i class="fa-solid fa-diagram-project"></i> Projects
        <span class="count"><?=e($stats['projects'])?></span>
      </a>
    </nav>
    <div class="sidebar-foot">
      <a href="index.html"><i class="fa-solid fa-arrow-left"></i> Back to website</a>
      <a href="logout.php"><i class="fa-solid fa-arrow-right-from-bracket"></i> Log out</a>
    </div>
  </aside>

  <div class="main">
    <header class="topbar">
      <div class="topbar-left">
        <button class="sidebar-toggle" id="sidebarToggle" aria-label="Open menu"><i class="fa-solid fa-bars"></i></button>
        <h1 id="pageTitle"><?=e($tabTitles[$tab])?></h1>
      </div>
      <div class="topbar-right">
        <div class="admin-chip">
          <span class="avatar"><?=e(strtoupper(substr($u['name'] ?? 'A',0,1)))?></span>
          <span><?=e($u['name'] ?? 'Admin')?></span>
        </div>
        <a href="logout.php" class="logout-btn"><i class="fa-solid fa-arrow-right-from-bracket"></i> <span>Log out</span></a>
      </div>
    </header>

    <main class="content">

      <?php if($f):?><div class="notice <?=$f['type']==='error'?'error':''?>"><?=e($f['message'])?></div><?php endif;?>

      <?php if($credentials):?>
      <div class="credentials">
        <h2>Client Login Credentials</h2>
        <p>These credentials were generated for <strong><?=e($credentials['name'])?></strong>.</p>
        <p>Username: <code><?=e($credentials['username'])?></code><br>Password: <code><?=e($credentials['password'])?></code></p>
        <p class="small">Email delivery: <?= $credentials['email_sent']?'sent successfully':'not configured on this XAMPP installation' ?>. The password is shown once so you can securely provide it to the client if local email is not configured.</p>
      </div>
      <?php endif;?>

      <!-- ============ DASHBOARD ============ -->
      <section class="panel-view<?= $tab==='dashboard'?' active':'' ?>" data-panel="dashboard">
        <div class="cards">
          <div class="stat"><span class="icon"><i class="fa-solid fa-file-invoice"></i></span><strong><?=e($stats['quotes'])?></strong><span>Total Quotes</span></div>
          <div class="stat"><span class="icon"><i class="fa-solid fa-sparkles"></i></span><strong><?=e($stats['new'])?></strong><span>New Requests</span></div>
          <div class="stat"><span class="icon"><i class="fa-solid fa-users"></i></span><strong><?=e($stats['clients'])?></strong><span>Clients</span></div>
          <div class="stat"><span class="icon"><i class="fa-solid fa-diagram-project"></i></span><strong><?=e($stats['projects'])?></strong><span>Active Projects</span></div>
        </div>

        <div class="two-col">
          <div class="panel">
            <div class="panel-head">
              <h2>Recent Quote Requests</h2>
              <a href="?tab=quotes" class="nav-link" data-tab="quotes" style="color:var(--tan);padding:0;font-size:13px;font-weight:700;">View all →</a>
            </div>
            <?php if(!$quotes):?><p class="empty">No quote requests yet.</p><?php else: foreach(array_slice($quotes,0,5) as $q):?>
              <div class="mini-row">
                <div><span class="who"><?=e($q['name'])?></span><br><span class="svc"><?=e($q['service'])?></span></div>
                <span class="pill <?=status_class($q['status'])?>"><?=e($q['status'])?></span>
              </div>
            <?php endforeach; endif;?>
          </div>

          <div class="panel">
            <div class="panel-head">
              <h2>Project Progress</h2>
              <a href="?tab=projects" class="nav-link" data-tab="projects" style="color:var(--tan);padding:0;font-size:13px;font-weight:700;">View all →</a>
            </div>
            <?php if(!$projects):?><p class="empty">No projects yet.</p><?php else: foreach(array_slice($projects,0,5) as $p):?>
              <div class="mini-row" style="display:block;">
                <div style="display:flex;justify-content:space-between;">
                  <span class="who"><?=e($p['title'])?></span>
                  <span class="svc"><?=e($p['progress'])?>%</span>
                </div>
                <div class="bar"><span style="width:<?=e($p['progress'])?>%"></span></div>
              </div>
            <?php endforeach; endif;?>
          </div>
        </div>
      </section>

      <!-- ============ QUOTES ============ -->
      <section class="panel-view<?= $tab==='quotes'?' active':'' ?>" data-panel="quotes">
        <div class="panel">
          <h2>Quote Requests</h2>
          <?php if(!$quotes):?><p class="empty">No quote requests yet.</p><?php else: foreach($quotes as $q):?>
          <article class="row-item">
            <div class="row-flex">
              <div>
                <div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap;">
                  <strong><?=e($q['name'])?></strong>
                  <span class="pill <?=status_class($q['status'])?>"><?=e($q['status'])?></span>
                </div>
                <span class="small"><?=e($q['email'])?> · <?=e($q['created_at'])?></span>
                <h3><?=e($q['service'])?></h3>
                <p><?=nl2br(e($q['message']))?></p>
                <?php if($q['username']):?><p class="small">Client account: <strong><?=e($q['username'])?></strong></p><?php endif;?>
              </div>
              <form method="post">
                <input type="hidden" name="csrf" value="<?=e(csrf_token())?>">
                <input type="hidden" name="action" value="quote_status">
                <input type="hidden" name="quote_id" value="<?=e($q['id'])?>">
                <div class="field">
                  <label>Status</label>
                  <select name="status">
                    <option <?= $q['status']==='New'?'selected':''?>>New</option>
                    <option <?= $q['status']==='Under Review'?'selected':''?>>Under Review</option>
                    <option <?= $q['status']==='Accepted'?'selected':''?>>Accepted</option>
                    <option <?= $q['status']==='Rejected'?'selected':''?>>Rejected</option>
                    <option <?= $q['status']==='Completed'?'selected':''?>>Completed</option>
                  </select>
                </div>
                <div class="field"><textarea name="admin_note" placeholder="Admin note"><?=e($q['admin_note'])?></textarea></div>
                <button class="btn">Save status</button>
              </form>
            </div>
          </article>
          <?php endforeach; endif;?>
        </div>
      </section>

      <!-- ============ CLIENTS ============ -->
      <section class="panel-view<?= $tab==='clients'?' active':'' ?>" data-panel="clients">
        <div class="panel">
          <h2>Clients</h2>
          <?php if(!$clients):?><p class="empty">No clients yet. Accepting a quote automatically creates a client account.</p>
          <?php else:?>
          <table>
            <thead><tr><th>Name</th><th>Email</th><th>Username</th><th>Joined</th></tr></thead>
            <tbody>
              <?php foreach($clients as $c):?>
              <tr>
                <td><?=e($c['name'])?></td>
                <td><?=e($c['email'])?></td>
                <td><?=e($c['username'])?></td>
                <td><?=e($c['created_at'])?></td>
              </tr>
              <?php endforeach;?>
            </tbody>
          </table>
          <?php endif;?>
        </div>
      </section>

      <!-- ============ PROJECTS ============ -->
      <section class="panel-view<?= $tab==='projects'?' active':'' ?>" data-panel="projects">
        <div class="two-col">
          <div class="panel">
            <h2>Create Client Project</h2>
            <form method="post">
              <input type="hidden" name="csrf" value="<?=e(csrf_token())?>">
              <input type="hidden" name="action" value="create_project">
              <div class="field">
                <label>Client</label>
                <select name="client_id" required>
                  <option value="">Select client</option>
                  <?php foreach($clients as $c):?><option value="<?=e($c['id'])?>"><?=e($c['name'])?> — <?=e($c['email'])?></option><?php endforeach;?>
                </select>
              </div>
              <div class="field"><label>Project title</label><input name="title" required></div>
              <div class="field"><label>Description</label><textarea name="description"></textarea></div>
              <div class="field">
                <label>Status</label>
                <select name="project_status">
                  <option>Not Started</option><option>In Progress</option><option>On Hold</option><option>Completed</option>
                </select>
              </div>
              <div class="field"><label>Progress %</label><input type="number" name="progress" min="0" max="100" value="0"></div>
              <div class="field"><label>Start date</label><input type="date" name="start_date"></div>
              <div class="field"><label>Due date</label><input type="date" name="due_date"></div>
              <button class="btn">Create Project</button>
            </form>
          </div>

          <div class="panel">
            <h2>Project Progress</h2>
            <?php if(!$projects):?><p class="empty">No projects yet.</p><?php else: foreach($projects as $p):?>
            <div class="row-item">
              <div style="display:flex;justify-content:space-between;align-items:center;gap:10px;flex-wrap:wrap;">
                <strong><?=e($p['title'])?></strong>
                <span class="pill <?=status_class($p['status'])?>"><?=e($p['status'])?></span>
              </div>
              <p class="small"><?=e($p['client_name'])?> — <?=e($p['email'])?></p>
              <div class="bar"><span style="width:<?=e($p['progress'])?>%"></span></div>
              <p class="small"><?=e($p['progress'])?>% complete</p>
              <form method="post">
                <input type="hidden" name="csrf" value="<?=e(csrf_token())?>">
                <input type="hidden" name="action" value="update_project">
                <input type="hidden" name="project_id" value="<?=e($p['id'])?>">
                <div class="field">
                  <label>Status</label>
                  <select name="project_status">
                    <option <?= $p['status']==='Not Started'?'selected':''?>>Not Started</option>
                    <option <?= $p['status']==='In Progress'?'selected':''?>>In Progress</option>
                    <option <?= $p['status']==='On Hold'?'selected':''?>>On Hold</option>
                    <option <?= $p['status']==='Completed'?'selected':''?>>Completed</option>
                  </select>
                </div>
                <div class="field"><label>Progress %</label><input type="number" name="progress" min="0" max="100" value="<?=e($p['progress'])?>"></div>
                <div class="field"><textarea name="update_text" placeholder="What changed? This message will appear in the client's portal."></textarea></div>
                <button class="btn">Update client</button>
              </form>
            </div>
            <?php endforeach; endif;?>
          </div>
        </div>
      </section>

    </main>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
  var navLinks = document.querySelectorAll('.nav-link[data-tab]');
  var panels = document.querySelectorAll('.panel-view');
  var pageTitle = document.getElementById('pageTitle');
  var titles = <?php echo json_encode($tabTitles, JSON_HEX_TAG); ?>;
  var sidebar = document.getElementById('sidebar');
  var backdrop = document.getElementById('sidebarBackdrop');
  var toggle = document.getElementById('sidebarToggle');

  function closeSidebarMobile(){ sidebar.classList.remove('open'); backdrop.classList.remove('open'); }

  function activate(tab, push){
    if (!titles.hasOwnProperty(tab)) tab = 'dashboard';
    navLinks.forEach(function (l) { l.classList.toggle('active', l.dataset.tab === tab); });
    panels.forEach(function (p) { p.classList.toggle('active', p.dataset.panel === tab); });
    if (pageTitle) pageTitle.textContent = titles[tab];
    if (push) {
      var url = new URL(window.location.href);
      url.searchParams.set('tab', tab);
      window.history.pushState({ tab: tab }, '', url);
    }
    closeSidebarMobile();
  }

  navLinks.forEach(function (link) {
    link.addEventListener('click', function (e) {
      e.preventDefault();
      activate(link.dataset.tab, true);
    });
  });

  window.addEventListener('popstate', function () {
    var params = new URLSearchParams(window.location.search);
    activate(params.get('tab') || 'dashboard', false);
  });

  if (toggle) toggle.addEventListener('click', function () {
    sidebar.classList.toggle('open');
    backdrop.classList.toggle('open');
  });
  if (backdrop) backdrop.addEventListener('click', closeSidebarMobile);
});
</script>
</body>
</html>
