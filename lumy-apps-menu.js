// lumy-apps-menu.js
(function () {
  // 1. Chargement des icônes Google Material Symbols si absentes
  if (!document.getElementById('material-symbols-font')) {
    const link = document.createElement('link');
    link.id = 'material-symbols-font';
    link.rel = 'stylesheet';
    link.href = 'https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200';
    document.head.appendChild(link);
  }

  // 2. Styles CSS avec z-index élevé et positionnement fluide
  const style = document.createElement('style');
  style.id = 'lumy-apps-menu-styles';
  style.textContent = `
    .lumy-nav-container {
      display: flex;
      align-items: center;
      gap: 8px;
      position: relative;
    }

    .lumy-icon-btn {
      width: 40px;
      height: 40px;
      border-radius: 50%;
      border: none;
      background: transparent;
      color: #444746;
      display: flex;
      align-items: center;
      justify-content: center;
      cursor: pointer;
      transition: background 0.15s;
    }
    .lumy-icon-btn:hover, .lumy-icon-btn:focus {
      background: #E9EEF6;
      color: #1F1F1F;
      outline: none;
    }

    .lumy-avatar-btn {
      width: 36px;
      height: 36px;
      border-radius: 50%;
      background: #059669;
      color: #FFFFFF;
      font-weight: 600;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 0.95rem;
      border: none;
      cursor: pointer;
      margin-left: 4px;
      transition: box-shadow 0.2s;
    }
    .lumy-avatar-btn:hover, .lumy-avatar-btn:focus {
      box-shadow: 0 0 0 4px #E9EEF6;
      outline: none;
    }

    /* Dropdown 9 Points */
    .lumy-apps-dropdown {
      display: none;
      position: absolute;
      top: 50px;
      right: 48px;
      width: 320px;
      background: #FFFFFF;
      border-radius: 28px;
      box-shadow: 0 4px 24px rgba(0,0,0,0.18);
      border: 1px solid #E0E2E0;
      padding: 16px;
      grid-template-columns: repeat(3, 1fr);
      gap: 12px;
      z-index: 99999;
    }
    .lumy-apps-dropdown.show {
      display: grid;
    }

    .lumy-app-item {
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
      padding: 12px 8px;
      border-radius: 16px;
      text-decoration: none;
      color: #1F1F1F;
      font-size: 0.8rem;
      gap: 6px;
      transition: background 0.15s;
    }
    .lumy-app-item:hover {
      background: #F1F5F9;
    }
    .lumy-app-item .material-symbols-outlined {
      font-size: 28px;
      color: #059669;
    }

    /* Popover Profil */
    .lumy-profile-popover {
      display: none;
      position: absolute;
      top: 50px;
      right: 0;
      width: 320px;
      background: #FFFFFF;
      border-radius: 28px;
      box-shadow: 0 4px 24px rgba(0,0,0,0.18);
      border: 1px solid #E0E2E0;
      padding: 20px;
      z-index: 99999;
      text-align: center;
    }
    .lumy-profile-popover.show {
      display: block;
    }

    .lumy-popover-header {
      font-size: 0.85rem;
      color: #444746;
      margin-bottom: 12px;
      word-break: break-all;
    }

    .lumy-popover-avatar {
      width: 68px;
      height: 68px;
      border-radius: 50%;
      background: #059669;
      color: #FFFFFF;
      font-size: 1.8rem;
      font-weight: 600;
      display: flex;
      align-items: center;
      justify-content: center;
      margin: 0 auto 12px;
    }

    .lumy-popover-name {
      font-size: 1.05rem;
      font-weight: 600;
      color: #1F1F1F;
    }

    .lumy-popover-email {
      font-size: 0.85rem;
      color: #444746;
      margin-bottom: 16px;
      word-break: break-all;
    }

    .lumy-popover-btn {
      display: inline-flex;
      align-items: center;
      justify-content: center;
      gap: 8px;
      width: 100%;
      height: 40px;
      border: 1px solid #E0E2E0;
      border-radius: 20px;
      text-decoration: none;
      color: #1F1F1F;
      font-size: 0.88rem;
      font-weight: 500;
      transition: background 0.15s;
    }
    .lumy-popover-btn:hover {
      background: #F8FAFD;
    }

    .lumy-popover-logout {
      margin-top: 12px;
      padding-top: 12px;
      border-top: 1px solid #E0E2E0;
    }
  `;
  if (!document.getElementById('lumy-apps-menu-styles')) {
    document.head.appendChild(style);
  }

  // 3. Liste des applications
  const apps = [
    { name: 'Dashboard', icon: 'dashboard', url: '../accounts/dashboard.php' },
    { name: 'Compte', icon: 'account_circle', url: '../myaccount/index.php' },
    { name: 'Lumy Mail', icon: 'mail', url: '../mail/index.html' },
    { name: 'Agenda', icon: 'event', url: '../agenda/index.html' },
    { name: 'Contacts', icon: 'contacts', url: '../contact/index.html' }
  ];

  async function fetchUserInfo() {
    try {
      const response = await fetch('api.php?action=get_user_info');
      if (response.ok) {
        return await response.json();
      }
    } catch (e) {
      console.warn('Impossible de charger les données utilisateur depuis api.php');
    }
    return null;
  }

  async function initMenu() {
    const container = document.getElementById('lumy-apps-menu') || document.getElementById('lumy-apps-container');
    if (!container) return;

    // Récupération automatique via API
    const apiData = await fetchUserInfo();

    const userName = apiData?.name || container.dataset.userName || 'Utilisateur';
    const userEmail = apiData?.email || container.dataset.userEmail || 'compte@lumycorp.com';
    const initials = apiData?.initials || container.dataset.initials || userName.charAt(0).toUpperCase();

    container.className = 'lumy-nav-container';
    container.innerHTML = `
      <button class="lumy-icon-btn" id="lumyBtnApps" title="Applications Lumy" type="button">
        <span class="material-symbols-outlined">apps</span>
      </button>

      <button class="lumy-avatar-btn" id="lumyBtnProfile" title="${userName}" type="button">
        ${initials}
      </button>

      <div class="lumy-apps-dropdown" id="lumyAppsDropdown">
        ${apps.map(app => `
          <a href="${app.url}" class="lumy-app-item">
            <span class="material-symbols-outlined">${app.icon}</span>
            <span>${app.name}</span>
          </a>
        `).join('')}
      </div>

      <div class="lumy-profile-popover" id="lumyProfilePopover">
        <div class="lumy-popover-header">${userEmail}</div>
        <div class="lumy-popover-avatar">${initials}</div>
        <div class="lumy-popover-name">${userName}</div>
        <div class="lumy-popover-email">${userEmail}</div>

        <a href="accounts/myaccount.php" class="lumy-popover-btn">
          <span class="material-symbols-outlined">manage_accounts</span>
          Gérer votre compte Lumy
        </a>

        <div class="lumy-popover-logout">
          <a href="logout.php" class="lumy-popover-btn" style="border-color: #FCE8E6; color: #C5221F;">
            <span class="material-symbols-outlined">logout</span>
            Se déconnecter
          </a>
        </div>
      </div>
    `;

    const btnApps = document.getElementById('lumyBtnApps');
    const btnProfile = document.getElementById('lumyBtnProfile');
    const dropdownApps = document.getElementById('lumyAppsDropdown');
    const popoverProfile = document.getElementById('lumyProfilePopover');

    btnApps.addEventListener('click', (e) => {
      e.stopPropagation();
      popoverProfile.classList.remove('show');
      dropdownApps.classList.toggle('show');
    });

    btnProfile.addEventListener('click', (e) => {
      e.stopPropagation();
      dropdownApps.classList.remove('show');
      popoverProfile.classList.toggle('show');
    });

    document.addEventListener('keydown', (e) => {
      if (e.key === 'Escape') {
        dropdownApps.classList.remove('show');
        popoverProfile.classList.remove('show');
      }
    });

    dropdownApps.addEventListener('click', (e) => e.stopPropagation());
    popoverProfile.addEventListener('click', (e) => e.stopPropagation());

    document.addEventListener('click', () => {
      dropdownApps.classList.remove('show');
      popoverProfile.classList.remove('show');
    });
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initMenu);
  } else {
    initMenu();
  }
})();