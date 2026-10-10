<?php
// session.php — Gestionnaire de session unifié Lumy

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Redirection vers la page de connexion si la session n'est pas active
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: /accounts/index.html');
    exit;
}

// Données de l'utilisateur connecté
$userEmail  = $_SESSION['user_email'] ?? '';
$userName   = $_SESSION['user_name'] ?? 'Utilisateur';

// Extraction du domaine
$parts      = explode('@', $userEmail);
$userDomain = strtolower(end($parts));

// Rôle : Administrateur si le domaine N'EST PAS lumymail.com
$isAdmin = ($userDomain !== 'lumymail.com');

/**
 * Fonction pour afficher le badge de profil unifié dans le coin supérieur droit
 */
function renderUserBadge() {
    global $userEmail, $userDomain, $isAdmin;
    
    $emailEsc  = htmlspecialchars($userEmail);
    $domainEsc = htmlspecialchars($userDomain);
    
    $badgeHtml = $isAdmin 
        ? '<span style="font-size: 0.7rem; font-weight: 700; color: #059669; background: #d1fae5; padding: 2px 6px; border-radius: 4px; text-transform: uppercase;">Admin ' . $domainEsc . '</span>'
        : '<span style="font-size: 0.7rem; color: #5f6368;">Utilisateur</span>';

    echo '
    <div style="display: flex; align-items: center; gap: 12px; background: #ffffff; padding: 6px 14px; border: 1px solid #dadce0; border-radius: 20px; box-shadow: 0 1px 3px rgba(0,0,0,0.05);">
        <div style="display: flex; flex-direction: column; align-items: flex-end;">
            <span style="font-size: 0.85rem; font-weight: 500; color: #202124;">' . $emailEsc . '</span>
            ' . $badgeHtml . '
        </div>
        <a href="/accounts/logout.php" title="Déconnexion" style="color: #d93025; text-decoration: none; display: flex; align-items: center; justify-content: center; padding: 4px; border-radius: 50%;">
            <span class="material-icons-outlined" style="font-size: 20px;">logout</span>
        </a>
    </div>';
}