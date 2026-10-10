<?php
// accounts/dashboard.php
require_once __DIR__ . '/../session.php';

// Redirection si l'utilisateur n'est pas connecté
if (!isset($_SESSION['user_name']) || !isset($_SESSION['user_email'])) {
    header('Location: /login_process.php');
    exit();
}

$userName   = $_SESSION['user_name'];
$userEmail  = $_SESSION['user_email'];
$initial    = mb_strtoupper(mb_substr($userName, 0, 1, 'UTF-8'));

$parts      = explode('@', $userEmail);
$userDomain = strtolower(end($parts));

$showAdminConsole = ($userDomain !== 'lumymail.com');
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Tableau de bord — Lumy</title>
  
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Google+Sans:wght@400;500;700&family=Roboto:wght@400;500&display=swap" rel="stylesheet">
  <link href="https://fonts.googleapis.com/icon?family=Material+Icons+Outlined" rel="stylesheet">
  <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" />

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
      flex-direction: column;
    }

    .header {
      height: 64px;
      background: var(--lumy-surface);
      border-bottom: 1px solid var(--lumy-border);
      display: flex;
      align-items: center;
      justify-content: space-between;
      padding: 0 24px;
      position: relative;
      z-index: 1000;
    }

    .brand {
      display: flex;
      align-items: center;
      gap: 10px;
      font-size: 1.25rem;
      font-weight: 700;
      color: var(--lumy-primary);
      text-decoration: none;
    }

    .header-right {
      display: flex;
      align-items: center;
      gap: 12px;
    }

    .container {
      max-width: 1000px;
      width: 100%;
      margin: 48px auto;
      padding: 0 24px;
    }

    .welcome-title { font-size: 1.75rem; font-weight: 400; margin-bottom: 8px; }
    .welcome-subtitle { color: var(--lumy-text-sec); margin-bottom: 36px; font-size: 0.95rem; }

    .apps-grid {
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
      gap: 20px;
    }

    .app-card {
      background: var(--lumy-surface);
      border: 1px solid var(--lumy-border);
      border-radius: 16px;
      padding: 24px;
      display: flex;
      flex-direction: column;
      align-items: center;
      text-align: center;
      text-decoration: none;
      color: var(--lumy-text);
      transition: transform 0.2s, box-shadow 0.2s, border-color 0.2s;
    }

    .app-card:hover {
      transform: translateY(-2px);
      box-shadow: 0 6px 16px rgba(0,0,0,0.08);
      border-color: var(--lumy-primary);
    }

    .app-icon {
      width: 56px; height: 56px; border-radius: 14px;
      display: flex; align-items: center; justify-content: center;
      margin-bottom: 16px; color: white;
    }

    .app-title { font-size: 1.1rem; font-weight: 500; margin-bottom: 4px; }
    .app-desc { font-size: 0.8rem; color: var(--lumy-text-sec); }
  </style>
</head>
<body>

  <header class="header">
    <a href="#" class="brand">
      <span class="material-icons-outlined" style="font-size: 32px;">workspaces</span>
      <span>Lumy Hub</span>
    </a>

    <div class="header-right">
      <div id="lumy-apps-menu"></div>
    </div>
  </header>

  <div class="container">
    <h1 class="welcome-title">Bienvenue, <?= htmlspecialchars($userName) ?></h1>
    <p class="welcome-subtitle">Sélectionnez une application pour commencer votre travail.</p>

    <div class="apps-grid">
      
      <!-- Mail -->
      <a href="../mail/index.html" class="app-card">
        <div class="app-icon" style="background: linear-gradient(135deg, #ea4335, #c5221f);">
          <span class="material-icons-outlined" style="font-size: 32px;">mail</span>
        </div>
        <div class="app-title">Lumy Mail</div>
        <div class="app-desc">Messagerie professionnelle et boîtes partagées</div>
      </a>

      <!-- Agenda -->
      <a href="../agenda/index.html" class="app-card">
        <div class="app-icon" style="background: linear-gradient(135deg, #34a853, #1e8e3e);">
          <span class="material-icons-outlined" style="font-size: 32px;">calendar_today</span>
        </div>
        <div class="app-title">Agenda</div>
        <div class="app-desc">Planification et événements d'équipe</div>
      </a>

      <!-- Contacts -->
      <a href="../contacts/index.html" class="app-card">
        <div class="app-icon" style="background: linear-gradient(135deg, #0284c7, #0369a1);">
          <span class="material-icons-outlined" style="font-size: 32px;">contacts</span>
        </div>
        <div class="app-title">Contacts</div>
        <div class="app-desc">Carnet d'adresses et gestion des annuaires</div>
      </a>

      <!-- Paramètres du compte -->
      <a href="settings.php" class="app-card">
        <div class="app-icon" style="background: linear-gradient(135deg, #6b7280, #374151);">
          <span class="material-icons-outlined" style="font-size: 32px;">settings</span>
        </div>
        <div class="app-title">Paramètres</div>
        <div class="app-desc">Gestion du profil, sécurité et 2FA</div>
      </a>

      <!-- Console Admin -->
      <?php if ($showAdminConsole): ?>
      <a href="../admin/users.html" class="app-card">
        <div class="app-icon" style="background: linear-gradient(135deg, #059669, #047857);">
          <span class="material-icons-outlined" style="font-size: 32px;">admin_panel_settings</span>
        </div>
        <div class="app-title">Lumy Admin</div>
        <div class="app-desc">Console de gestion pour <?= htmlspecialchars($userDomain) ?></div>
      </a>
      <?php endif; ?>

    </div>
  </div>

  <script>
    // Récupération stricte de la session utilisateur active
    window.LUMY_USER = {
      name: <?= json_encode($userName) ?>,
      email: <?= json_encode($userEmail) ?>,
      initial: <?= json_encode($initial) ?>
    };
  </script>
  <script src="/../lumy-apps-menu.js"></script>
</body>
</html>