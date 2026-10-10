<?php
// accounts/check_session.php
header('Content-Type: application/json; charset=utf-8');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

echo json_encode([
    'logged_in' => isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true
]);
exit;