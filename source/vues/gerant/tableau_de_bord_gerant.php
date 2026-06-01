<?php
session_start();

// Vérifier connexion
if (!isset($_SESSION['utilisateur'])) {
    header('Location: ../connexion.php');
    exit;
}

// Vérifier le rôle
if ($_SESSION['utilisateur']['role'] !== 'GESTIONNAIRE') {
    header('Location: ../connexion.php');
    exit;
}

// Inclure les contrôleurs
require_once __DIR__ . '/../../../source/controleurs/RapportControleur.php';
require_once __DIR__ . '/../../../source/controleurs/AlerteControleur.php';
require_once __DIR__ . '/../../../source/controleurs/ProduitControleur.php';

// Initialiser
$rapportCtrl = new RapportControleur();
$alerteCtrl = new AlerteControleur();
$produitCtrl = new ProduitControleur();

// Récupérer les données
$produits = $produitCtrl->listeProduits();
$alertes = $alerteCtrl->listeAlertesNonTraitees();
$nbProduits = count($produits);
$nbAlertes = $alerteCtrl->compterNonTraitees();
$produitsAlerte = $rapportCtrl->produitsEnAlerteMagasin();

// Utilisateur connecté
$user = $_SESSION['utilisateur'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>STOCKFLOW - Gestionnaire Dashboard</title>
    
    <link href="../bootstrap-5.3.8-dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../bootstrap-5.3.8-dist/fontawesome-free-6.7.2-web/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="../../../public/css/responsive.css" rel="stylesheet">

    
    <script>
        const savedTheme = localStorage.getItem('stockflow_theme') || 'light';
        document.documentElement.setAttribute('data-theme', savedTheme);
    </script>

    <style>
        * { font-family: 'Poppins', sans-serif; }
        
        /* VARIABLES THÈME CLAIR (Par défaut) */
        :root { 
            --primary: #f97316; 
            --primary-dark: #ea580c;
            --bg-body: #f8fafc;
            --bg-sidebar: #ffffff;
            --bg-card: #ffffff;
            --bg-topbar: #ffffff;
            --text-main: #1e293b;
            --text-muted: #64748b;
            --border-color: rgba(0,0,0,0.1);
            --shadow-color: rgba(0,0,0,0.05);
            --alert-bg: #fff7ed;
            --alert-border: #f97316;
            --btn-bg: #f8f9fa;
            --btn-text: #1e293b;
            --dropdown-bg: #ffffff;
            --dropdown-text: #212529;
            --dropdown-hover: #f8f9fa;
        }
        
        /* VARIABLES THÈME SOMBRE */
        [data-theme="dark"] {
            --bg-body: #0f172a;
            --bg-sidebar: #1e293b;
            --bg-card: #1e293b;
            --bg-topbar: #1e293b;
            --text-main: #f8fafc;
            --text-muted: #94a3b8;
            --border-color: rgba(255,255,255,0.1);
            --shadow-color: rgba(0,0,0,0.2);
            --alert-bg: #332115; 
            --alert-border: #ea580c;
            --btn-bg: #334155;
            --btn-text: #f8fafc;
            --dropdown-bg: #1e293b;
            --dropdown-text: #f8fafc;
            --dropdown-hover: #334155;
        }

        /* APPLICATION DES VARIABLES */
        body { 
            background-color: var(--bg-body); 
            color: var(--text-main);
            transition: background-color 0.3s ease, color 0.3s ease;
        }
        
        /* Surcharges utilitaires Bootstrap pour le thème sombre */
        .text-muted { color: var(--text-muted) !important; }
        .text-success { color: #22c55e !important; }
        .card { background-color: var(--bg-card); border-color: var(--border-color); }
        .card-header { background-color: var(--bg-card) !important; border-bottom-color: var(--border-color); color: var(--text-main); }
        .dropdown-menu { background-color: var(--dropdown-bg); border-color: var(--border-color); }
        .dropdown-item { color: var(--dropdown-text); }
        .dropdown-item:hover { background-color: var(--dropdown-hover); color: var(--dropdown-text); }
        
        .sidebar {
            background: var(--bg-sidebar) !important;
            min-height: 100vh;
            color: var(--text-main);
            position: fixed;
            top: 0;
            left: 0;
            bottom: 0;
            width: 280px;
            overflow-y: auto;
            box-shadow: 2px 0 10px var(--shadow-color);
            border-right: 1px solid var(--border-color);
            transition: background-color 0.3s ease;
        }
        .sidebar .logo {
            text-align: center;
            padding: 25px 0;
            border-bottom: 1px solid var(--border-color);
        }
        .sidebar .logo h4 { font-weight: 800; color: var(--primary); }
        .sidebar .logo i { color: var(--primary); background: rgba(249,115,22,0.1); padding: 12px; border-radius: 15px; }
        .sidebar .logo p { color: var(--text-muted); }
        .sidebar .nav-link {
            color: var(--text-muted) !important;
            padding: 12px 25px;
            margin: 5px 0;
            border-radius: 10px;
            transition: all 0.3s ease;
            background: transparent;
        }
        .sidebar .nav-link:hover {
            background: var(--primary) !important;
            color: white !important;
            transform: translateX(5px);
        }
        .sidebar .nav-link.active {
            background: var(--primary) !important;
            color: white !important;
        }
        .sidebar .nav-link i { width: 28px; margin-right: 12px; color: var(--primary); }
        .sidebar .nav-link:hover i, .sidebar .nav-link.active i { color: white; }
        
        .main-content { margin-left: 280px; padding: 20px; }
        .stat-card {
            background: var(--bg-card);
            border-radius: 15px;
            padding: 20px;
            box-shadow: 0 2px 10px var(--shadow-color);
            transition: transform 0.3s, background-color 0.3s;
            border-left: 4px solid var(--primary);
        }
        .stat-card:hover { transform: translateY(-5px); }
        .stat-number { font-size: 28px; font-weight: 800; color: var(--text-main); }
        .top-bar {
            background: var(--bg-topbar);
            border-radius: 15px;
            padding: 15px 20px;
            margin-bottom: 25px;
            box-shadow: 0 2px 10px var(--shadow-color);
            transition: background-color 0.3s;
        }
        
        .btn-theme-toggle, .btn-user-dropdown {
            background-color: var(--btn-bg);
            color: var(--btn-text);
            border: 1px solid var(--border-color);
        }
        .btn-theme-toggle:hover, .btn-user-dropdown:hover, .btn-user-dropdown:focus {
            background-color: var(--dropdown-hover);
            color: var(--text-main);
        }

        .alert-item {
            border-left: 4px solid var(--alert-border);
            padding: 12px;
            background: var(--alert-bg);
            color: var(--text-main);
            margin-bottom: 10px;
            border-radius: 8px;
            transition: background-color 0.3s;
        }
        @media (max-width: 768px) {
            .sidebar { width: 70px; }
            .sidebar .nav-link span, .sidebar .logo p, .sidebar .logo h4 { display: none; }
            .main-content { margin-left: 70px; }
        }
    </style>
</head>
<body>

    <div class="sidebar">
        <div class="logo">
            <i class="fas fa-chart-line fa-2x"></i>
            <h4>STOCK<span>FLOW</span></h4>
            <p>Gérant</p>
        </div>
        <nav class="nav flex-column mt-3">
            <a class="nav-link active" href="tableau_de_bord_gerant.php"><i class="fas fa-tachometer-alt"></i> <span>Tableau de bord</span></a>
            <a class="nav-link" href="produits.php"><i class="fas fa-boxes"></i> <span>Produits</span></a>
            <a class="nav-link" href="fournisseurs.php"><i class="fas fa-truck"></i> <span>Fournisseurs</span></a>
            <a class="nav-link" href="stocks_consulter.php"><i class="fas fa-warehouse"></i> <span>Stocks (état global)</span></a>
            <a class="nav-link" href="commandes.php"><i class="fas fa-shopping-cart"></i> <span>Commandes fournisseurs</span></a>
            <a class="nav-link" href="commandes_historique.php"><i class="fas fa-history"></i> <span>Historique</span></a>
            <a class="nav-link" href="alertes.php"><i class="fas fa-bell"></i> <span>Alertes</span></a>
            <a class="nav-link" href="rapports.php"><i class="fas fa-chart-line"></i> <span>Rapports</span></a>
            <a class="nav-link" href="profil_gerant.php"><i class="fas fa-user"></i> <span>Mon profil</span></a>
            <a class="nav-link" href="../../../deconnexion.php"><i class="fas fa-sign-out-alt"></i> <span>Déconnexion</span></a>
        </nav>
    </div>

    <div class="main-content">
        
        <div class="top-bar d-flex justify-content-between align-items-center">
            <h4 class="mb-0"><i class="fas fa-tachometer-alt me-2" style="color: var(--primary);"></i>Tableau de bord</h4>
            
            <div class="d-flex align-items-center">
                <button class="btn btn-theme-toggle me-3 rounded-circle" onclick="toggleTheme()" title="Basculer le thème">
                    <i id="theme-icon" class="fas fa-moon"></i>
                </button>

                <div class="dropdown">
                    <button class="btn btn-user-dropdown dropdown-toggle" data-bs-toggle="dropdown">
                        <i class="fas fa-user-circle me-2"></i> 
                        <?php echo htmlspecialchars($user['prenom'] . ' ' . $user['nom']); ?>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li><a class="dropdown-item" href="profil_gerant.php"><i class="fas fa-user"></i> Mon profil</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item text-danger" href="../../../deconnexion.php"><i class="fas fa-sign-out-alt"></i> Déconnexion</a></li>
                    </ul>
                </div>
            </div>
        </div>

    <div class="row">
        <div class="col-md-4 mb-4">
            <a href="produits.php" class="text-decoration-none">
                <div class="stat-card">
                    <i class="fas fa-boxes fa-2x" style="color: var(--primary);"></i>
                    <div class="stat-number"><?php echo $nbProduits; ?></div>
                    <p class="text-muted">Produits</p>
                </div>
            </a>
        </div>

    <div class="col-md-4 mb-4">
        <a href="stocks_consulter.php" class="text-decoration-none">
            <div class="stat-card">
                <i class="fas fa-exclamation-triangle fa-2x" style="color: var(--primary);"></i>
                <div class="stat-number"><?php echo count($produitsAlerte); ?></div>
                <p class="text-muted">Produits en alerte</p>
            </div>
        </a>
    </div>

    <div class="col-md-4 mb-4">
        <a href="alertes.php" class="text-decoration-none">
            <div class="stat-card">
                <i class="fas fa-bell fa-2x" style="color: var(--primary);"></i>
                <div class="stat-number <?php echo $nbAlertes > 0 ? 'text-warning' : ''; ?>">
                    <?php echo $nbAlertes; ?>
                </div>
                <p class="text-muted">Alertes actives</p>
            </div>
        </a>
    </div>
</div>

    <script src="../bootstrap-5.3.8-dist/js/bootstrap.bundle.min.js"></script>
    <script src="../../../public/js/responsive.js"></script>
    
    <script>
        function updateThemeIcon(theme) {
            const icon = document.getElementById('theme-icon');
            if (icon) {
                if (theme === 'dark') {
                    icon.classList.replace('fa-moon', 'fa-sun');
                } else {
                    icon.classList.replace('fa-sun', 'fa-moon');
                }
            }
        }

        function toggleTheme() {
            const currentTheme = document.documentElement.getAttribute('data-theme');
            const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
            
            // Appliquer le thème
            document.documentElement.setAttribute('data-theme', newTheme);
            
            // Sauvegarder dans le navigateur
            localStorage.setItem('stockflow_theme', newTheme);
            
            // Mettre à jour l'icône
            updateThemeIcon(newTheme);
        }

        // Initialiser l'icône au chargement de la page
        document.addEventListener('DOMContentLoaded', () => {
            const savedTheme = localStorage.getItem('stockflow_theme') || 'light';
            updateThemeIcon(savedTheme);
        });
    </script>
</body>
</html>