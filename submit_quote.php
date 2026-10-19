<?php
// Saves a quote request from the website's Contact page into the shared "quotes" table.
// The mobile app saves into the SAME table through its API (POST /api/quotes),
// so the checks below match the API's checks and both end up in Admin > Quote Requests.
require_once 'config.php';
if ($_SERVER['REQUEST_METHOD'] !== 'POST') redirect('contact.php');
check_csrf();

$name    = trim($_POST['name'] ?? '');
if ($name === '') $name = trim(($_POST['first_name'] ?? '') . ' ' . ($_POST['last_name'] ?? ''));   // works even if JavaScript is off
$email   = trim($_POST['email'] ?? '');
$service = trim($_POST['service'] ?? '');
$message = trim($_POST['message'] ?? '');

$error = '';
if ($name === '')                                   $error = 'Please enter your name.';
elseif (mb_strlen($name) > 120)                     $error = 'Your name is too long.';
elseif (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 190) $error = 'Please enter a valid email address.';
elseif ($message === '')                            $error = 'Please tell us about your project.';
elseif (mb_strlen($message) > 4000)                 $error = 'Your message is too long (maximum 4000 characters).';
if ($error !== '') { flash('error', $error); redirect('contact.php'); }

if ($service === '') $service = 'General Enquiry';
$service = mb_substr($service, 0, 150);

$st = db()->prepare("INSERT INTO quotes(name, email, service, message, source) VALUES(?, ?, ?, ?, 'website')");
$st->execute([$name, $email, $service, $message]);

flash('success', 'Your quote request was sent successfully. Thank you! Our team will review it and contact you with the next steps.');
redirect('contact.php');
