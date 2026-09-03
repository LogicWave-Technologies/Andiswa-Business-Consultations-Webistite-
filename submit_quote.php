<?php
require_once 'config.php';
if ($_SERVER['REQUEST_METHOD'] !== 'POST') redirect('contact.php');
check_csrf();
$name=trim($_POST['name']??''); $email=trim($_POST['email']??''); $service=trim($_POST['service']??'General Enquiry'); $message=trim($_POST['message']??'');
if($name==='' || !filter_var($email,FILTER_VALIDATE_EMAIL) || $message==='') { flash('error','Please complete your name, valid email address and project details.'); redirect('contact.php'); }
$st=db()->prepare('INSERT INTO quotes(name,email,service,message) VALUES(?,?,?,?)'); $st->execute([$name,$email,$service,$message]);
flash('success','Your quote request was sent successfully. Thank you! Our team will review it and contact you with the next steps.');
redirect('contact.php');
