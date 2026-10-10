<?php
// accounts/forgot_password.php
session_start();

// Inclusion de la configuration Mailcow si disponible
if (!function_exists('callMailcow')) {
    if (file_exists(__DIR__ . '/../config.php')) {
        require_once __DIR__ . '/../config.php';
    }
}

/**
 * Fonction d'envoi d'e-mail via Socket SMTP natif (compatible Mailcow / Docker)
 */
function sendSmtpEmail($to, $subject, $body, $fromEmail, $fromName = 'Lumy Support') {
    $hosts = ['postfix-mailcow', 'postfix', '127.0.0.1', 'localhost'];
    $port  = 25;
    $socket = false;

    foreach ($hosts as $host) {
        $socket = @fsockopen($host, $port, $errno, $errstr, 3);
        if ($socket) {
            break;
        }
    }

    if (!$socket) {
        return false;
    }

    $domain = explode('@', $fromEmail)[1] ?? 'localhost';

    fgets($socket, 512);
    fputs($socket, "EHLO " . $domain . "\r\n");
    while ($line = fgets($socket, 512)) {
        if (substr($line, 3, 1) === ' ') break;
    }

    fputs($socket, "MAIL FROM: <" . $fromEmail . ">\r\n");
    fgets($socket, 512);

    fputs($socket, "RCPT TO: <" . $to . ">\r\n");
    fgets($socket, 512);

    fputs($socket, "DATA\r\n");
    fgets($socket, 512);

    $headers  = "From: " . mb_encode_mimeheader($fromName, 'UTF-8') . " <" . $fromEmail . ">\r\n";
    $headers .= "To: <" . $to . ">\r\n";
    $headers .= "Subject: " . mb_encode_mimeheader($subject, 'UTF-8') . "\r\n";
    $headers .= "MIME-Version: 1.0\r\n";
    $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";

    fputs($socket, $headers . "\r\n" . $body . "\r\n.\r\n");
    fgets($socket, 512);

    fputs($socket, "QUIT\r\n");
    fclose($socket);

    return true;
}

$message = '';
$status  = '';
$step    = 'request'; // 'request' ou 'reset'

// Vérification si un jeton de réinitialisation est transmis par URL
$tokenParam = $_GET['token'] ?? '';
if (!empty($tokenParam) && isset($_SESSION['reset_token']) && $_SESSION['reset_token'] === $tokenParam) {
    if (time() < $_SESSION['reset_expires']) {
        $step = 'reset';
    } else {
        $message = "Le lien de réinitialisation a expiré. Veuillez refaire une demande.";
        $status  = "error";
    }
}

// TRAITEMENT DES FORMULAIRES
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // ÉTAPE 1 : Demande de réinitialisation
    if ($action === 'request_reset') {
        $email = trim($_POST['email'] ?? '');

        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $message = "Veuillez entrer une adresse e-mail valide.";
            $status  = "error";
        } else {
            // Interroger l'API Mailcow pour obtenir l'adresse de récupération
            $recoveryEmail = '';
            if (function_exists('callMailcow')) {
                $mailboxData = callMailcow('/api/v1/get/mailbox/' . $email, null, 'GET');
                if (is_array($mailboxData) && !isset($mailboxData['error'])) {
                    $recoveryEmail = $mailboxData['recovery_email'] ?? '';
                }
            }

            if (!empty($recoveryEmail)) {
                // Génération du token de réinitialisation (valide 30 min)
                $token = bin2hex(random_bytes(16));
                $_SESSION['reset_token']   = $token;
                $_SESSION['reset_email']   = $email;
                $_SESSION['reset_expires'] = time() + 1800; 

                $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? "https" : "http";
                $resetUrl = $protocol . "://" . $_SERVER['HTTP_HOST'] . $_SERVER['PHP_SELF'] . "?token=" . $token;

                $domain  = explode('@', $email)[1] ?? 'lumy.com';
                $from    = "no-reply@" . $domain;
                $subject = "Lumy — Réinitialisation de votre mot de passe";
                $body    = "Bonjour,\n\n"
                         . "Une demande de réinitialisation de mot de passe a été effectuée pour le compte : " . $email . ".\n\n"
                         . "Pour choisir un nouveau mot de passe, veuillez cliquer sur le lien suivant (valide 30 minutes) :\n"
                         . $resetUrl . "\n\n"
                         . "Si vous n'êtes pas à l'origine de cette demande, vous pouvez ignorer cet e-mail.\n\n"
                         . "Cordialement,\nL'équipe Lumy";

                $sent = sendSmtpEmail($recoveryEmail, $subject, $body, $from, "Lumy Account");

                if (!$sent) {
                    $headers = "From: Lumy <" . $from . ">\r\n" .
                               "Content-Type: text/plain; charset=UTF-8\r\n";
                    $sent = @mail($recoveryEmail, $subject, $body, $headers);
                }

                $message = "Un e-mail contenant les instructions de réinitialisation a été envoyé à votre adresse de secours.";
                $status  = "success";
            } else {
                // Pour des raisons de sécurité, afficher un message générique ou informer de l'absence d'adresse de secours
                $message = "Si cette adresse existe et possède un e-mail de secours configuré, un lien de réinitialisation y a été envoyé.";
                $status  = "success";
            }
        }
    }

    // ÉTAPE 2 : Définition du nouveau mot de passe
    if ($action === 'confirm_reset') {
        $newPass     = $_POST['new_password'] ?? '';
        $confirmPass = $_POST['confirm_password'] ?? '';
        $emailToReset= $_SESSION['reset_email'] ?? '';

        if (empty($_SESSION['reset_token']) || time() > ($_SESSION['reset_expires'] ?? 0)) {
            $message = "La session de réinitialisation a expiré. Veuillez recommencer.";
            $status  = "error";
            $step    = 'request';
        } elseif (empty($newPass) || strlen($newPass) < 6) {
            $message = "Le mot de passe doit contenir au moins 6 caractères.";
            $status  = "error";
            $step    = 'reset';
        } elseif ($newPass !== $confirmPass) {
            $message = "Les mots de passe ne correspondent pas.";
            $status  = "error";
            $step    = 'reset';
        } else {
            // Mise à jour dans Mailcow via l'API
            $payload = [
                'items' => [$emailToReset],
                'attr'  => [
                    'password' => $newPass
                ]
            ];

            if (function_exists('callMailcow')) {
                $res = callMailcow('/api/v1/edit/mailbox', $payload, 'POST');

                if (is_array($res) && isset($res[0]['type']) && $res[0]['type'] === 'success') {
                    // Nettoyage de la session de réinitialisation
                    unset($_SESSION['reset_token'], $_SESSION['reset_email'], $_SESSION['reset_expires']);

                    $message = "Votre mot de passe a été réinitialisé avec succès ! Vous pouvez maintenant vous connecter.";
                    $status  = "success";
                    $step    = 'completed';
                } else {
                    $message = "Erreur lors de la mise à jour du mot de passe dans le système.";
                    $status  = "error";
                    $step    = 'reset';
                }
            } else {
                $message = "Erreur de connexion avec l'API Mailcow.";
                $status  = "error";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Mot de passe oublié — Lumy Account</title>
  
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Google+Sans:wght@400;500;700&family=Roboto:wght@400;500&display=swap" rel="stylesheet">
  <link href="https://fonts.googleapis.com/icon?family=Material+Icons+Outlined" rel="stylesheet">

  <style>
    :root {
      --lumy-bg: #F8F9FA;
      --lumy-surface: #FFFFFF;
      --lumy-border: #DADCE0;
      --lumy-text: #202124;
      --lumy-text-sec: #5F6368;
      --lumy-primary: #059669;
      --lumy-font: 'Google Sans', 'Roboto', sans-serif;
    }

    * { box-sizing: border-box; margin: 0; padding: 0; }
    body {
      font-family: var(--lumy-font);
      background-color: var(--lumy-bg);
      color: var(--lumy-text);
      min-height: 100vh;
      display: flex;
      align-items: center;
      justify-content: center;
      padding: 20px;
    }

    .card {
      background: var(--lumy-surface);
      border: 1px solid var(--lumy-border);
      border-radius: 16px;
      padding: 40px;
      max-width: 440px;
      width: 100%;
      box-shadow: 0 4px 12px rgba(0,0,0,0.05);
    }

    .brand {
      display: flex;
      align-items: center;
      gap: 10px;
      font-size: 1.25rem;
      font-weight: 700;
      color: var(--lumy-primary);
      margin-bottom: 24px;
      text-decoration: none;
    }

    .card-title {
      font-size: 1.35rem;
      font-weight: 700;
      margin-bottom: 8px;
    }

    .card-subtitle {
      font-size: 0.9rem;
      color: var(--lumy-text-sec);
      margin-bottom: 24px;
      line-height: 1.5;
    }

    .form-group {
      display: flex;
      flex-direction: column;
      gap: 6px;
      margin-bottom: 20px;
    }

    label { font-size: 0.85rem; font-weight: 500; color: var(--lumy-text); }
    input[type="email"], input[type="password"] {
      width: 100%;
      padding: 12px 14px;
      border: 1px solid var(--lumy-border);
      border-radius: 8px;
      font-family: inherit;
      font-size: 0.95rem;
      outline: none;
    }
    input:focus { border-color: var(--lumy-primary); }

    .btn-submit {
      width: 100%;
      background: var(--lumy-primary);
      color: white;
      border: none;
      padding: 12px;
      border-radius: 8px;
      font-family: inherit;
      font-weight: 500;
      font-size: 0.95rem;
      cursor: pointer;
      transition: background 0.2s;
    }
    .btn-submit:hover { background: #047857; }

    .alert {
      padding: 12px 16px;
      border-radius: 8px;
      margin-bottom: 20px;
      font-size: 0.9rem;
      line-height: 1.4;
    }
    .alert-success { background: #d1fae5; color: #065f46; border: 1px solid #a7f3d0; }
    .alert-error { background: #fce8e6; color: #c5221f; border: 1px solid #fad2cf; }

    .footer-links {
      margin-top: 24px;
      text-align: center;
      font-size: 0.9rem;
    }
    .footer-links a {
      color: var(--lumy-primary);
      text-decoration: none;
      font-weight: 500;
    }
    .footer-links a:hover { text-decoration: underline; }
  </style>
</head>
<body>

  <div class="card">
    <a href="index.html" class="brand">
      <span class="material-icons-outlined" style="font-size: 32px;">workspaces</span>
      <span>Lumy Account</span>
    </a>

    <?php if ($message): ?>
      <div class="alert alert-<?= $status ?>"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>

    <?php if ($step === 'request'): ?>
      <h1 class="card-title">Récupération de compte</h1>
      <p class="card-subtitle">
        Saisissez votre adresse e-mail principale. Un lien de réinitialisation sera transmis à votre adresse e-mail de secours.
      </p>

      <form method="POST">
        <input type="hidden" name="action" value="request_reset">

        <div class="form-group">
          <label for="email">Adresse e-mail principale</label>
          <input type="email" id="email" name="email" placeholder="votre.nom@domaine.com" required autofocus>
        </div>

        <button type="submit" class="btn-submit">Envoyer le lien de secours</button>
      </form>

    <?php elseif ($step === 'reset'): ?>
      <h1 class="card-title">Nouveau mot de passe</h1>
      <p class="card-subtitle">
        Choisissez un nouveau mot de passe pour votre compte <strong><?= htmlspecialchars($_SESSION['reset_email'] ?? '') ?></strong>.
      </p>

      <form method="POST">
        <input type="hidden" name="action" value="confirm_reset">

        <div class="form-group">
          <label for="new_password">Nouveau mot de passe</label>
          <input type="password" id="new_password" name="new_password" placeholder="Au moins 6 caractères" required autofocus>
        </div>

        <div class="form-group">
          <label for="confirm_password">Confirmer le mot de passe</label>
          <input type="password" id="confirm_password" name="confirm_password" placeholder="Répéter le mot de passe" required>
        </div>

        <button type="submit" class="btn-submit">Mettre à jour le mot de passe</button>
      </form>

    <?php elseif ($step === 'completed'): ?>
      <h1 class="card-title">Opération terminée</h1>
      <p class="card-subtitle">Vous pouvez dès à présent vous connecter avec votre nouveau mot de passe.</p>
      <a href="index.html" class="btn-submit" style="display: block; text-align: center; text-decoration: none;">Se connecter</a>
    <?php endif; ?>

    <div class="footer-links">
      <a href="index.html">← Retour à la connexion</a>
    </div>
  </div>

</body>
</html>