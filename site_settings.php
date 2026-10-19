<?php
// =====================================================================
// site_settings.php  -  GET  ->  the public studio details as JSON
// ---------------------------------------------------------------------
// The plain .html pages cannot run PHP, so Projects_Js/site-settings.js asks
// this file for the phone, email, address, hours, tagline and social links.
// They come from the SAME "settings" table the mobile app's API reads
// (GET /api/studio), so the website and the app always show the same details.
//
// Only the keys listed below are sent. Nothing private lives in this table,
// but listing them keeps it that way if someone adds a private setting later.
// =====================================================================
require_once 'config.php';

const PUBLIC_SETTING_KEYS = [
    'studio', 'tagline', 'hero_text', 'about_text1', 'about_text2',
    'email', 'phone', 'address', 'hours', 'whatsapp', 'owner_name', 'owner_title',
    'stat1_value', 'stat1_label', 'stat2_value', 'stat2_label',
    'stat3_value', 'stat3_label', 'stat4_value', 'stat4_label',
    'facebook_url', 'instagram_url', 'twitter_url', 'linkedin_url', 'youtube_url',
];

$all = settings_all();
$out = [];
foreach (PUBLIC_SETTING_KEYS as $key) {
    $out[$key] = $all[$key] ?? '';
}

header('Content-Type: application/json; charset=utf-8');
echo json_encode($out, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
