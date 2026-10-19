<?php
// =====================================================================
// chat_log.php  -  POST  ->  saves one chatbot message in "chat_messages"
// ---------------------------------------------------------------------
// Called by Projects_Js/api.js -> saveChatMessage().
// Body (JSON): { "message": "...", "sender": "user" | "bot", "sessionId": "session-..." }
//
// Protection, because anyone can open the chatbot:
//   * only JSON is accepted (other websites cannot post here with a plain form)
//   * message length, sender and session id are checked
//   * at most 40 messages per minute per visitor (counted in the PHP session)
// =====================================================================
require_once 'config.php';
header('Content-Type: application/json; charset=utf-8');

function reply(int $code, array $body): never { http_response_code($code); echo json_encode($body); exit; }

if ($_SERVER['REQUEST_METHOD'] !== 'POST') reply(405, ['error' => 'Use POST.']);
if (stripos($_SERVER['CONTENT_TYPE'] ?? '', 'application/json') !== 0) reply(415, ['error' => 'Send JSON.']);

// Simple spam limit: 40 messages per minute for one browser session
$now = time();
$_SESSION['chat_log_times'] = array_values(array_filter($_SESSION['chat_log_times'] ?? [], fn($t) => $t > $now - 60));
if (count($_SESSION['chat_log_times']) >= 40) reply(429, ['error' => 'Too many messages. Please wait a minute.']);
$_SESSION['chat_log_times'][] = $now;

$data      = json_decode(file_get_contents('php://input'), true);
$message   = trim((string)($data['message'] ?? ''));
$sender    = (string)($data['sender'] ?? '');
$sessionId = (string)($data['sessionId'] ?? '');

if ($message === '' || mb_strlen($message) > 2000)          reply(400, ['error' => 'Message must be 1 to 2000 characters.']);
if (!in_array($sender, ['user', 'bot'], true))              reply(400, ['error' => 'Sender must be user or bot.']);
if (!preg_match('/^[A-Za-z0-9_-]{6,100}$/', $sessionId))    reply(400, ['error' => 'Invalid session id.']);

// If a client or the admin is logged in, link the message to their account
$userId = current_user()['id'] ?? null;

$st = db()->prepare('INSERT INTO chat_messages(session_id, user_id, sender, message) VALUES(?, ?, ?, ?)');
$st->execute([$sessionId, $userId, $sender, $message]);

reply(201, ['success' => true, 'id' => (int)db()->lastInsertId()]);
