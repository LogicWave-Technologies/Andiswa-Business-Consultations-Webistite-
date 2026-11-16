<?php
require_once 'config.php'; require_login('client'); $u=current_user();
if($_SERVER['REQUEST_METHOD']==='POST'){
  check_csrf();
  if(isset($_POST['change_password'])){
    try {
      api_request('POST', 'client/change-password', ['password'=>$_POST['password']??''], $_SESSION['api_token']);
      $_SESSION['user']['must_change_password']=false;
      flash('success','Password changed successfully.');
    } catch (RuntimeException $exception) {
      flash('error',$exception->getMessage());
    }
    redirect('dashboard.php');
  }
}
try {
  $dashboard=api_request('GET','client/dashboard',null,$_SESSION['api_token']);
} catch (RuntimeException $exception) {
  http_response_code(502);
  exit('Unable to load your client data: '.e($exception->getMessage()));
}
$quotes=array_map(static fn($quote)=>[
  'id'=>$quote['id'],
  'service'=>$quote['service'],
  'message'=>$quote['message'],
  'status'=>$quote['status'],
  'created_at'=>$quote['createdAt']??null,
],$dashboard['quotes']??[]);
$projects=array_map(static fn($project)=>[
  'id'=>$project['id'],
  'title'=>$project['title'],
  'description'=>$project['description']??'',
  'status'=>$project['status'],
  'progress'=>$project['progress'],
  'due_date'=>$project['dueDate']??null,
  'updates'=>array_map(static fn($update)=>[
    'update_text'=>$update['updateText'],
    'created_at'=>$update['createdAt']??null,
  ],$project['updates']??[]),
],$dashboard['projects']??[]);
$f=get_flash();
?><!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Client Dashboard</title><style>*{box-sizing:border-box}body{margin:0;background:#f7f8fa;color:#232830;font-family:Arial,sans-serif}.top{background:#24384f;color:white;padding:18px 5%;display:flex;justify-content:space-between;align-items:center}.top a{color:white;text-decoration:none}.wrap{max-width:1100px;margin:30px auto;padding:0 20px}.notice{padding:13px;border-radius:8px;margin-bottom:18px;background:#e9f7ef;color:#166534}.error{background:#fff0ed;color:#9b3927}.card{background:white;border:1px solid #ddd;border-radius:12px;padding:20px;margin-bottom:20px}.bar{height:12px;background:#e3e7eb;border-radius:20px;overflow:hidden}.bar span{display:block;height:100%;background:#5b8db8}.grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(280px,1fr));gap:20px}.muted{color:#68717d}input{padding:11px;width:100%;border:1px solid #ccd2d9;border-radius:7px}button{padding:11px 15px;border:0;border-radius:7px;background:#24384f;color:white;font-weight:700}ul{padding-left:20px}</style></head><body><header class="top"><strong>Andiswa Business Consultation — Client Portal</strong><a href="logout.php">Log out</a></header><main class="wrap"><h1>Welcome, <?=e($u['name'])?></h1><?php if($f):?><div class="notice <?=$f['type']==='error'?'error':''?>"><?=e($f['message'])?></div><?php endif;?><?php if($u['must_change_password']):?><div class="card"><h2>Change your temporary password</h2><form method="post"><input type="hidden" name="csrf" value="<?=e(csrf_token())?>"><input type="password" name="password" minlength="8" placeholder="New password" required><br><br><button name="change_password">Save password</button></form></div><?php endif;?><div class="grid"><section class="card"><h2>Your quote requests</h2><?php if(!$quotes):?><p class="muted">No quote has been linked to this account yet.</p><?php else: foreach($quotes as $x):?><div style="padding:12px 0;border-bottom:1px solid #eee"><strong><?=e($x['service'])?></strong><p><?=nl2br(e($x['message']))?></p><span class="muted">Status: <strong><?=e($x['status'])?></strong></span></div><?php endforeach; endif;?></section><section class="card"><h2>Your projects</h2><?php if(!$projects):?><p class="muted">A project will appear here once your quote has been accepted and the project has been created.</p><?php else: foreach($projects as $pr):?><div style="padding:12px 0;border-bottom:1px solid #eee"><h3><?=e($pr['title'])?></h3><p><?=e($pr['description'])?></p><p><strong><?=e($pr['status'])?></strong> — <?=e($pr['progress'])?>%</p><div class="bar"><span style="width:<?=min(100,(int)$pr['progress'])?>%"></span></div><?php if(!empty($pr['due_date'])):?><p class="muted">Due: <?=e($pr['due_date'])?></p><?php endif;?><h4>Updates</h4><?php if(empty($pr['updates'])):?><p class="muted">No updates yet.</p><?php else:?><ul><?php foreach($pr['updates'] as $up):?><li><?=e($up['update_text'])?> <small class="muted">(<?=e($up['created_at'])?>)</small></li><?php endforeach;?></ul><?php endif;?></div><?php endforeach; endif;?></section></div></main></body></html>
