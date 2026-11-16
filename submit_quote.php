<?php
// Saves a quote request through the published API so it appears in the shared
// Admin > Quote Requests list.
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

try {
    api_request('POST', 'quotes', [
        'name' => $name,
        'email' => $email,
        'service' => $service,
        'message' => $message,
        'source' => 'website',
    ]);
} catch (RuntimeException $exception) {
    flash('error', 'Your quote request could not be saved: ' . $exception->getMessage());
    redirect('contact.php');
}

try {
    $emailSent = send_contact_email($name, $email, $service, $message);
} catch (RuntimeException $exception) {
    error_log('The quote was saved through the business API, but the notification email could not be prepared: ' . $exception->getMessage());
    $emailSent = false;
}

if ($emailSent) {
    flash('success', 'Your quote request was sent successfully. Thank you! Our team will review it and contact you with the next steps.');
} else {
    flash('error', 'Your quote request was saved, but we could not email the team. Please contact us directly at ' . setting('email') . '.');
}
redirect('contact.php');
