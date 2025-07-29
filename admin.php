<?php
session_start();

// Contrôle simple (à adapter)
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    // Rediriger vers login si besoin
    // header('Location: login.php');
    // exit;
}

$page = $_GET['page'] ?? 'dashboard';
$allowed_pages = ['dashboard', 'users', 'presences'];
if (!in_array($page, $allowed_pages)) {
    $page = 'dashboard';
}

function isActive($p) {
    global $page;
    return $page === $p ? 'active' : '';
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>Admin - Tableau de bord</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet" />
<style>
  body, html {
    margin: 0; height: 100%;
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
  }

  /* Sidebar */
  #sidebar {
    position: fixed; top: 0; left: 0; height: 100vh;
    width: 220px;
    background: #343a40;
    color: white;
    transition: width 0.3s ease;
    overflow-x: hidden;
    z-index: 1040;
  }
  #sidebar.closed {
    width: 70px;
  }
  #sidebar .sidebar-header {
    padding: 1.2rem 1rem;
    font-size: 1.4rem;
    font-weight: 600;
    text-align: center;
    border-bottom: 1px solid #495057;
  }
  #sidebar ul.components {
    padding: 0;
    margin: 0;
    list-style: none;
  }
  #sidebar ul li {
    padding: 12px 1rem;
    cursor: pointer;
    display: flex;
    align-items: center;
    transition: background-color 0.2s;
  }
  #sidebar ul li.active,
  #sidebar ul li:hover {
    background-color: #495057;
  }
  #sidebar ul li i {
    font-size: 1.3rem;
    min-width: 20px;
    text-align: center;
  }
  #sidebar ul li span {
    margin-left: 1rem;
    white-space: nowrap;
    transition: opacity 0.3s ease;
  }
  #sidebar.closed ul li span {
    opacity: 0;
    pointer-events: none;
  }

  /* Header */
  #header {
    position: fixed;
    top: 0; left: 0; right: 0;
    height: 56px;
    background: #fff;
    border-bottom: 1px solid #dee2e6;
    display: flex;
    align-items: center;
    padding: 0 1rem;
    z-index: 1050;
  }
  #toggle-sidebar-btn {
    font-size: 1.5rem;
    background: none;
    border: none;
    color: #343a40;
    cursor: pointer;
  }
  #header h2 {
    margin-left: 1rem;
    font-size: 1.3rem;
    font-weight: 600;
    user-select: none;
  }

  /* Content */
  #content {
    margin-left: 220px;
    padding: 1.5rem 2rem;
    min-height: 100vh;
    transition: margin-left 0.3s ease;
    background: #f8f9fa;
  }
  #sidebar.closed ~ #content {
    margin-left: 70px;
  }
  #content-inner {
    margin-top: 56px; /* pour ne pas être masqué par le header */
  }

  /* Responsive */
  @media (max-width: 768px) {
    #sidebar {
      width: 70px;
    }
    #sidebar.closed {
      width: 0;
    }
    #sidebar.closed ~ #content {
      margin-left: 0;
    }
    #content {
      margin-left: 70px;
      padding: 1rem;
    }
    #header h2 {
      margin-left: 0.5rem;
      font-size: 1.1rem;
    }
  }
</style>
</head>
<body>

<div id="sidebar" class="">
  <div class="sidebar-header">Admin</div>
  <ul class="components" role="menu">
    <li role="menuitem" class="<?= isActive('dashboard') ?>" data-page="dashboard" tabindex="0">
      <i class="bi bi-speedometer2"></i><span>Tableau de bord</span>
    </li>
    <li role="menuitem" class="<?= isActive('users') ?>" data-page="users" tabindex="0">
      <i class="bi bi-people"></i><span>Gestion utilisateurs</span>
    </li>
    <li role="menuitem" class="<?= isActive('presences') ?>" data-page="presences" tabindex="0">
      <i class="bi bi-clock-history"></i><span>Gestion présences</span>
    </li>
  </ul>
</div>

<div id="header">
  <button id="toggle-sidebar-btn" aria-label="Ouvrir/Fermer le menu sidebar" title="Ouvrir/Fermer le menu">
    <i class="bi bi-list"></i>
  </button>
  <h2 id="page-title">
    <?php
    switch ($page) {
      case 'users': echo 'Gestion des utilisateurs'; break;
      case 'presences': echo 'Gestion des présences'; break;
      default: echo 'Tableau de bord';
    }
    ?>
  </h2>
</div>

<div id="content">
  <div id="content-inner" class="container-fluid">
    <?php
    // Inclure la page correspondant au menu sélectionné
    switch ($page) {
        case 'users':
            include __DIR__ . '/crud-users.php';
            break;
        case 'presences':
            include __DIR__ . '/admin-presence.php';
            break;
        case 'dashboard':
        default:
            ?>
            <div class="p-4 bg-light rounded shadow-sm">
                <h3>Bienvenue sur votre tableau de bord</h3>
                <p>Utilisez le menu latéral pour accéder aux différentes sections.</p>
            </div>
            <?php
            break;
    }
    ?>
  </div>
</div>

<script>
  const sidebar = document.getElementById('sidebar');
  const toggleBtn = document.getElementById('toggle-sidebar-btn');
  const pageTitle = document.getElementById('page-title');
  const menuItems = sidebar.querySelectorAll('ul.components li');

  toggleBtn.addEventListener('click', () => {
    sidebar.classList.toggle('closed');
  });

  menuItems.forEach(item => {
    item.addEventListener('click', () => {
      const page = item.getAttribute('data-page');
      if (!page) return;

      // Mise à jour de l'URL sans recharger la page
      const url = new URL(window.location);
      url.searchParams.set('page', page);
      window.history.pushState({page}, '', url);

      // Mise à jour du titre
      pageTitle.textContent = item.textContent.trim();

      // Mise à jour visuelle active dans le menu
      menuItems.forEach(mi => mi.classList.remove('active'));
      item.classList.add('active');

      // Chargement du contenu via fetch Ajax
      loadPageContent(page);
    });
  });

  function loadPageContent(page) {
    let url;
    switch(page) {
      case 'users': url = 'crud-users.php'; break;
      case 'presences': url = 'admin-presence.php'; break;
      case 'dashboard': 
      default: 
        document.getElementById('content-inner').innerHTML = `
          <div class="p-4 bg-light rounded shadow-sm">
            <h3>Bienvenue sur votre tableau de bord</h3>
            <p>Utilisez le menu latéral pour accéder aux différentes sections.</p>
          </div>`;
        return;
    }

    fetch(url)
      .then(response => {
        if (!response.ok) throw new Error('Erreur chargement');
        return response.text();
      })
      .then(html => {
        document.getElementById('content-inner').innerHTML = html;
      })
      .catch(error => {
        document.getElementById('content-inner').innerHTML = `<div class="alert alert-danger">Erreur de chargement : ${error.message}</div>`;
      });
  }

  // Gérer l'historique navigateur
  window.addEventListener('popstate', e => {
    let page = 'dashboard';
    if (e.state && e.state.page) {
      page = e.state.page;
    } else {
      const urlParams = new URLSearchParams(window.location.search);
      page = urlParams.get('page') || 'dashboard';
    }

    // Mise à jour du menu
    menuItems.forEach(mi => {
      mi.classList.toggle('active', mi.getAttribute('data-page') === page);
      if (mi.getAttribute('data-page') === page) {
        pageTitle.textContent = mi.textContent.trim();
      }
    });

    loadPageContent(page);
  });

  // Initialiser au chargement
  document.addEventListener('DOMContentLoaded', () => {
    const urlParams = new URLSearchParams(window.location.search);
    let initialPage = urlParams.get('page') || 'dashboard';

    menuItems.forEach(mi => {
      mi.classList.toggle('active', mi.getAttribute('data-page') === initialPage);
    });

    const initialItem = [...menuItems].find(mi => mi.getAttribute('data-page') === initialPage);
    if (initialItem) pageTitle.textContent = initialItem.textContent.trim();

    if (initialPage !== 'dashboard') {
      loadPageContent(initialPage);
    }
  });
</script>
</body>
</html>
