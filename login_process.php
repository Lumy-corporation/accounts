<?php
// accounts/login_process.php
ob_start();
header('Content-Type: application/json; charset=utf-8');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Chargement de la configuration générale
if (file_exists(__DIR__ . '/../config.php')) {
    require_once __DIR__ . '/../config.php';
}

$input  = json_decode(file_get_contents('php://input'), true);
$action = $_GET['action'] ?? 'login';

// ==========================================
// 1. ÉTAPE 1 : VÉRIFICATION DE L'ADRESSE E-MAIL
// ==========================================
if ($action === 'check_email') {
    ob_clean();
    $emailInput = trim($input['email'] ?? '');

    if (empty($emailInput) || !filter_var($emailInput, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(['success' => false, 'message' => 'Veuillez saisir une adresse e-mail valide.']);
        exit;
    }

    // Vérification via Mailcow si disponible
    if (function_exists('callMailcow')) {
        $mailboxData = callMailcow('/api/v1/get/mailbox/' . $emailInput, null, 'GET');

        if (!$mailboxData || isset($mailboxData['error']) || (isset($mailboxData['type']) && $mailboxData['type'] === 'danger')) {
            echo json_encode(['success' => false, 'message' => 'L\'adresse e-mail n\'existe pas.']);
            exit;
        }

        if (isset($mailboxData['active']) && $mailboxData['active'] != 1) {
            echo json_encode(['success' => false, 'message' => 'Ce compte est actuellement suspendu.']);
            exit;
        }
    }

    echo json_encode(['success' => true]);
    exit;
}

// ==========================================
// 2. ÉTAPE 2 : VALIDER LES IDENTIFIANTS (CORRIGÉ)
// ==========================================
ob_clean();
$login    = trim($input['identifier'] ?? '');
$password = $input['password'] ?? '';

if (empty($login) || empty($password)) {
    echo json_encode(['success' => false, 'message' => 'Veuillez remplir tous les champs.']);
    exit;
}

// Formatage de l'e-mail et du domaine
$parts     = explode('@', $login);
$userLocal = $parts[0];
$domain    = count($parts) > 1 ? $parts[1] : 'lumycorp.com';
$email     = $userLocal . '@' . $domain;

// --- VÉRIFICATION DU MOT DE PASSE (AUTHENTIFICATION REELLE) ---
$mailHost = defined('MAILCOW_HOST') ? MAILCOW_HOST : 'mail.lumycorp.com';
$authenticated = false;

// Test 1 : Authentification via IMAP (Serveur Mailcow)
if (function_exists('imap_open')) {
    $mailbox = "{" . $mailHost . ":993/imap/ssl/novalidate-cert}INBOX";
    $connection = @imap_open($mailbox, $email, $password);
    if ($connection) {
        imap_close($connection);
        $authenticated = true;
    }
} else {
    // Fallback de secours si extension IMAP PHP absente (sockets)
    $fp = @fsockopen("ssl://" . $mailHost, 993, $errno, $errstr, 5);
    if ($fp) {
        fgets($fp, 512);
        fputs($fp, "A1 LOGIN \"$email\" \"$password\"\r\n");
        $response = fgets($fp, 512);
        fclose($fp);
        if (strpos($response, 'A1 OK') !== false) {
            $authenticated = true;
        }
    }
}

// Si l'authentification échoue
if (!$authenticated) {
    echo json_encode(['success' => false, 'message' => 'Mot de passe incorrect.']);
    exit;
}

// --- CONNEXION RÉUSSIE ---

// Récupération du nom d'affichage
$userName = ucfirst($userLocal);
if (function_exists('callMailcow')) {
    $mailboxData = callMailcow('/api/v1/get/mailbox/' . $email, null, 'GET');
    if ($mailboxData && !empty($mailboxData['name'])) {
        $userName = $mailboxData['name'];
    }
}

// Création définitive de la session
$_SESSION['logged_in']     = true;
$_SESSION['user_email']    = $email;
$_SESSION['user_password'] = $password;
$_SESSION['user_name']     = $userName;
$_SESSION['user_domain']   = $domain;

echo json_encode([
    'success'  => true,
    'redirect' => 'dashboard.php',
    'user'     => [
        'name'  => $userName,
        'email' => $email
    ]
]);
exit;