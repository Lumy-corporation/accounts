<?php
// config.php
define('MAILCOW_URL', 'https://mail.interne.lumycorp.com');
define('MAILCOW_API_KEY', 'MaSuperCleAPI123456789');

function callMailcow($endpoint, $payload = null, $method = 'POST') {
    $ch = curl_init(MAILCOW_URL . $endpoint);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'X-API-Key: ' . MAILCOW_API_KEY,
        'Content-Type: application/json'
    ]);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    
    if ($method === 'POST') {
        curl_setopt($ch, CURLOPT_POST, true);
        if ($payload) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
        }
    }
    
    $res = curl_exec($ch);
    curl_close($ch);
    return json_decode($res, true);
}
?>