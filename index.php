<?php
// 1. CONFIGURATION STRICTE ET SECURISEE DES SESSIONS
ini_set('session.cookie_httponly', 1);
ini_set('session.cookie_secure', 1);
ini_set('session.cookie_samesite', 'Strict');
ini_set('session.use_strict_mode', 1);

session_start();

// 2. GÉNÉRATION D'UN NONCE UNIQUE POUR LA CSP (Protection Anti-XSS ultime)
$csp_nonce = base64_encode(random_bytes(16));

// 3. EN-TÊTES DE SÉCURITÉ CYBER MAXIMALES
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");
header("X-Frame-Options: DENY");
header("X-Content-Type-Options: nosniff");
header("X-XSS-Protection: 1; mode=block");
header("Referrer-Policy: strict-origin-when-cross-origin");
header("Permissions-Policy: camera=(), microphone=(), geolocation=(), payment=()");
header("Content-Security-Policy: default-src 'self'; script-src 'self' 'nonce-{$csp_nonce}' https://fonts.googleapis.com; style-src 'self' 'unsafe-inline' https://fonts.googleapis.com; font-src 'self' https://fonts.gstatic.com; img-src 'self' data:; connect-src 'self'; frame-ancestors 'none';");

// 4. VÉRIFICATION DE SESSION CÔTÉ SERVEUR
if (isset($_SESSION['user_id']) && !empty($_SESSION['user_id'])) {
    header('Location: dashboard.php');
    exit();
}

// 5. GÉNÉRATION DU JETON ANTI-CSRF
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrf_token = $_SESSION['csrf_token'];
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Connexion — Lumy</title>
  
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Google+Sans:wght@400;500;700&family=Roboto:wght@400;500&display=swap" rel="stylesheet">
  <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">

  <style>
    :root {
      --lumy-bg: #FFFFFF;
      --lumy-surface: #F8F9FA;
      --lumy-gradient: linear-gradient(135deg, #059669 0%, #06b6d4 100%);
      --lumy-primary: #059669;
      --lumy-text: #202124;
      --lumy-text-muted: #5f6368;
      --lumy-border: #dadce0;
      --lumy-shadow-sm: 0 1px 3px 0 rgba(60,64,67,0.15);
      --lumy-shadow-md: 0 4px 12px 0 rgba(60,64,67,0.15);
      --lumy-font: 'Google Sans', 'Roboto', sans-serif;
    }

    * { 
      box-sizing: border-box; 
      margin: 0; 
      padding: 0;
      -webkit-user-select: none;
      -moz-user-select: none;
      -ms-user-select: none;
      user-select: none;
    }

    body {
      font-family: var(--lumy-font);
      background-color: var(--lumy-surface);
      color: var(--lumy-text);
      min-height: 100vh;
      display: flex;
      align-items: center;
      justify-content: center;
      padding: 24px 16px;
    }

    .login-card {
      max-width: 450px;
      width: 100%;
      background: white;
      border: 1px solid var(--lumy-border);
      border-radius: 24px;
      padding: 40px;
      box-shadow: var(--lumy-shadow-sm);
      position: relative;
      overflow: hidden;
    }

    .progress-bar-container {
      position: absolute;
      top: 0; left: 0; width: 100%; height: 4px;
      background-color: #E6F4EA; display: none; overflow: hidden;
    }

    .progress-bar-value {
      width: 100%; height: 100%; background: var(--lumy-gradient);
      animation: googleIndeterminate 1.5s infinite linear;
      transform-origin: 0% 0%;
    }

    @keyframes googleIndeterminate {
      0% { transform: translateX(-100%) scaleX(0.2); }
      50% { transform: translateX(0%) scaleX(0.5); }
      100% { transform: translateX(100%) scaleX(0.2); }
    }

    .brand-logo {
      display: flex; align-items: center; justify-content: center;
      gap: 8px; text-decoration: none; margin-bottom: 24px;
    }

    .brand-name {
      font-size: 1.75rem; font-weight: 700;
      background: var(--lumy-gradient);
      -webkit-background-clip: text;
      -webkit-text-fill-color: transparent;
    }

    .card-header { text-align: center; margin-bottom: 24px; }
    .card-title { font-size: 1.5rem; font-weight: 700; margin-bottom: 6px; }
    .card-subtitle { color: var(--lumy-text-muted); font-size: 0.95rem; }

    .user-chip {
      display: inline-flex; align-items: center; gap: 8px;
      border: 1px solid var(--lumy-border); border-radius: 16px;
      padding: 6px 14px; font-size: 0.9rem; color: var(--lumy-text);
      cursor: pointer; margin-top: 8px;
    }

    .form-group { display: flex; flex-direction: column; gap: 6px; margin-bottom: 16px; }
    label { font-size: 0.85rem; font-weight: 500; }
    input {
      width: 100%; padding: 12px 16px; border: 1px solid var(--lumy-border);
      border-radius: 8px; font-family: inherit; font-size: 0.95rem; outline: none;
      -webkit-user-select: text;
      -moz-user-select: text;
      -ms-user-select: text;
      user-select: text;
    }
    input:focus { border-color: var(--lumy-primary); }

    .forgot-link-container { display: flex; justify-content: flex-start; margin-bottom: 16px; }
    .forgot-link { color: var(--lumy-primary); text-decoration: none; font-size: 0.9rem; font-weight: 500; }
    .forgot-link:hover { text-decoration: underline; }

    .alert-error {
      background: #fce8e6; border: 1px solid #fad2cf; color: #c5221f;
      padding: 12px; border-radius: 8px; font-size: 0.85rem; margin-bottom: 20px; display: none;
    }

    .btn-submit {
      width: 100%; background: var(--lumy-gradient); color: white; border: none;
      padding: 14px; border-radius: 24px; font-family: inherit; font-size: 1rem;
      font-weight: 500; cursor: pointer; margin-top: 10px; transition: box-shadow 0.2s;
    }
    .btn-submit:hover { box-shadow: var(--lumy-shadow-md); }

    .step-section { display: none; }
    .step-section.active { display: block; }
  </style>
</head>
<body>

  <div class="login-card">
    <div id="progressBar" class="progress-bar-container"><div class="progress-bar-value"></div></div>

    <a href="#" class="brand-logo" onclick="return false;">
      <span class="material-icons" style="color: var(--lumy-primary); font-size: 36px;">workspaces</span>
      <span class="brand-name">Lumy</span>
    </a>

    <div class="card-header">
      <h1 class="card-title">Connexion</h1>
      <p class="card-subtitle" id="cardSubtitle">Accédez à votre espace centralisé Lumy</p>
      
      <div id="userChip" class="user-chip" style="display: none;" onclick="goToStep(1)">
        <span class="material-icons" style="font-size: 18px; color: var(--lumy-text-muted);">account_circle</span>
        <span id="chipEmailText"></span>
        <span class="material-icons" style="font-size: 16px; color: var(--lumy-text-muted);">arrow_drop_down</span>
      </div>
    </div>

    <div id="errorBox" class="alert-error"></div>

    <form id="loginForm" onsubmit="return false;" autocomplete="off">
      <input type="hidden" id="csrfToken" value="<?php echo htmlspecialchars($csrf_token, ENT_QUOTES, 'UTF-8'); ?>">

      <!-- ÉTAPE 1 : EMAIL -->
      <div id="step1" class="step-section active">
        <div class="form-group">
          <label for="loginIdentifier">Adresse e-mail</label>
          <input type="email" id="loginIdentifier" placeholder="nom@lumycorp.com" required autofocus autocomplete="username" spellcheck="false">
        </div>
        <div class="forgot-link-container">
          <a href="forgot_password.php" class="forgot-link">Mot de passe oublié ?</a>
        </div>
        <button type="button" id="btnStep1" class="btn-submit">Suivant</button>
      </div>

      <!-- ÉTAPE 2 : MOT DE PASSE -->
      <div id="step2" class="step-section">
        <div class="form-group">
          <label for="loginPassword">Mot de passe</label>
          <input type="password" id="loginPassword" placeholder="••••••••" autocomplete="current-password">
        </div>
        <div class="forgot-link-container">
          <a href="forgot_password.php" class="forgot-link">Mot de passe oublié ?</a>
        </div>
        <button type="button" id="btnStep2" class="btn-submit">Se connecter</button>
      </div>

    </form>
  </div>

  <script nonce="<?php echo $csp_nonce; ?>">
    // --- SÉCURITÉ CYBER : BLOCAGE DEVTOOLS & ANTI-INSPECTION ---
    document.addEventListener('contextmenu', e => e.preventDefault());
    document.addEventListener('dragstart', e => e.preventDefault());
    
    document.addEventListener('keydown', e => {
      if (
        e.key === 'F12' || 
        (e.ctrlKey && e.shiftKey && (e.key === 'I' || e.key === 'J' || e.key === 'C' || e.key === 'K' || e.key === 'S')) ||
        (e.ctrlKey && e.key === 'u')
      ) {
        e.preventDefault();
        return false;
      }
    });

    // Boucle Anti-Debugger (Fiége les personnes qui ouvrent la console)
    setInterval(() => {
      const startTime = performance.now();
      (function(){}).constructor("debugger")();
      if (performance.now() - startTime > 100) {
        window.location.reload();
      }
    }, 1000);

    // --- SCRIPT OBFUSQUÉ ET INTERFACE ---
    const progressBar = document.getElementById('progressBar');
    const errorBox = document.getElementById('errorBox');
    const step1 = document.getElementById('step1');
    const step2 = document.getElementById('step2');
    
    const emailInput = document.getElementById('loginIdentifier');
    const passwordInput = document.getElementById('loginPassword');
    const csrfToken = document.getElementById('csrfToken').value;
    
    const userChip = document.getElementById('userChip');
    const chipEmailText = document.getElementById('chipEmailText');
    const cardSubtitle = document.getElementById('cardSubtitle');

    function showLoading() { progressBar.style.display = 'block'; }
    function hideLoading() { progressBar.style.display = 'none'; }
    function showError(msg) { errorBox.innerText = msg; errorBox.style.display = 'block'; }
    function hideError() { errorBox.style.display = 'none'; }

    function goToStep(stepNumber) {
      hideError();
      step1.classList.remove('active');
      step2.classList.remove('active');

      if (stepNumber === 1) {
        step1.classList.add('active');
        userChip.style.display = 'none';
        cardSubtitle.style.display = 'block';
        passwordInput.value = '';
        emailInput.focus();
      } else if (stepNumber === 2) {
        step2.classList.add('active');
        chipEmailText.innerText = emailInput.value.trim();
        userChip.style.display = 'inline-flex';
        cardSubtitle.style.display = 'none';
        passwordInput.focus();
      }
    }

    document.getElementById('btnStep1').addEventListener('click', async () => {
      hideError();
      const email = emailInput.value.trim();
      if (!email || !email.includes('@')) {
        showError("Veuillez saisir une adresse e-mail valide.");
        return;
      }

      showLoading();
      try {
        const res = await fetch('login_process.php?action=check_email', {
          method: 'POST',
          headers: { 
            'Content-Type': 'application/json',
            'X-CSRF-Token': csrfToken
          },
          body: JSON.stringify({ email, csrf_token: csrfToken })
        });
        const data = await res.json();
        hideLoading();

        if (data.success) {
          goToStep(2);
        } else {
          showError(data.message || "Adresse e-mail introuvable.");
        }
      } catch (e) {
        hideLoading();
        showError("Erreur de connexion serveur.");
      }
    });

    document.getElementById('btnStep2').addEventListener('click', async () => {
      hideError();
      if (!passwordInput.value) {
        showError("Veuillez saisir votre mot de passe.");
        return;
      }

      showLoading();
      try {
        const res = await fetch('login_process.php', {
          method: 'POST',
          headers: { 
            'Content-Type': 'application/json',
            'X-CSRF-Token': csrfToken
          },
          body: JSON.stringify({
            identifier: emailInput.value.trim(),
            password: passwordInput.value,
            csrf_token: csrfToken
          })
        });
        const data = await res.json();
        hideLoading();

        if (data.success) {
          window.location.href = data.redirect;
        } else {
          showError(data.message || "Identifiants incorrects.");
        }
      } catch (e) {
        hideLoading();
        showError("Erreur lors de la connexion.");
      }
    });

    emailInput.addEventListener('keydown', (e) => { if (e.key === 'Enter') document.getElementById('btnStep1').click(); });
    passwordInput.addEventListener('keydown', (e) => { if (e.key === 'Enter') document.getElementById('btnStep2').click(); });
  </script>
</body>
</html>