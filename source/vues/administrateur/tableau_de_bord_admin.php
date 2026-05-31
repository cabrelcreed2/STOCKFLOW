<?php
session_start();

// Vérifier connexion
if (!isset($_SESSION['utilisateur'])) {
    header('Location: ../authentification/connexion.php');
    exit;
}

// Vérifier le rôle
if ($_SESSION['utilisateur']['role'] !== 'ADMIN') {
    header('Location: ../authentification/connexion.php');
    exit;
}

// Inclure les contrôleurs
require_once __DIR__ . '/../../../source/controleurs/AdministrationControleur.php';
require_once __DIR__ . '/../../../source/controleurs/AlerteControleur.php';
require_once __DIR__ . '/../../../source/controleurs/ProduitControleur.php';

// Initialiser
$adminCtrl = new AdministrationControleur();
$alerteCtrl = new AlerteControleur();
$produitCtrl = new ProduitControleur();

// Récupérer les données
$utilisateurs = $adminCtrl->listeUtilisateurs();
$rayons = $adminCtrl->listeRayons();
$produits = $produitCtrl->listeProduits();
$nbAlertes = $alerteCtrl->compterNonTraitees();
$alertes = $alerteCtrl->listeAlertesNonTraitees();

// Statistiques
$nbUtilisateurs = count($utilisateurs);
$nbRayons = count($rayons);
$nbProduits = count($produits);

// Utilisateur connecté
$user = $_SESSION['utilisateur'];
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>STOCKFLOW - Admin Dashboard</title>
    
    <script>
        const savedTheme = localStorage.getItem('theme') || 'light';
        document.documentElement.setAttribute('data-theme', savedTheme);
    </script>

    <link href="../bootstrap-5.3.8-dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../bootstrap-5.3.8-dist/fontawesome-free-6.7.2-web/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="../../../public/css/responsive.css" rel="stylesheet">
    
    <style>
        :root { 
            --primary: #f97316; 
            --primary-dark: #ea580c; 
            
            /* Thème Clair (Défaut) */
            --bg-body: #f8fafc;
            --bg-surface: #ffffff;
            --text-main: #1e293b;
            --text-muted: #64748b;
            --border-color: rgba(0,0,0,0.1);
            --shadow-color: rgba(0,0,0,0.05);
        }

        [data-theme="dark"] {
            /* Thème Sombre */
            --bg-body: #0f172a;
            --bg-surface: #1e293b;
            --text-main: #f1f5f9;
            --text-muted: #94a3b8;
            --border-color: rgba(255,255,255,0.1);
            --shadow-color: rgba(0,0,0,0.3);
        }

        * { font-family: 'Poppins', sans-serif; }
        
        body { 
            background-color: var(--bg-body); 
            color: var(--text-main);
            transition: background-color 0.3s ease, color 0.3s ease;
        }
        
        .sidebar {
            background: var(--bg-surface) !important;
            min-height: 100vh;
            color: var(--text-main);
            position: fixed;
            top: 0; left: 0; bottom: 0;
            width: 280px;
            overflow-y: auto;
            box-shadow: 2px 0 10px var(--shadow-color);
            transition: background-color 0.3s ease;
            z-index: 1000;
        }
        .sidebar .logo { text-align: center; padding: 25px 0; border-bottom: 1px solid var(--border-color); }
        .sidebar .logo h4 { font-weight: 800; color: var(--primary); }
        .sidebar .logo i { color: var(--primary); background: rgba(249,115,22,0.1); padding: 12px; border-radius: 15px; }
        .sidebar .logo p { color: var(--text-muted); }
        .sidebar .nav-link {
            color: var(--text-muted) !important; padding: 12px 25px; margin: 5px 0;
            border-radius: 10px; transition: all 0.3s ease; background: transparent;
        }
        .sidebar .nav-link:hover { background: var(--primary) !important; color: white !important; transform: translateX(5px); }
        .sidebar .nav-link.active { background: var(--primary) !important; color: white !important; }
        .sidebar .nav-link i { width: 28px; margin-right: 12px; color: var(--primary); }
        .sidebar .nav-link:hover i, .sidebar .nav-link.active i { color: white; }
        
        .main-content { margin-left: 280px; padding: 20px; }
        
        .stat-card {
            background: var(--bg-surface); 
            border-radius: 15px; padding: 20px;
            box-shadow: 0 2px 10px var(--shadow-color); 
            transition: transform 0.3s, background-color 0.3s;
            border-left: 4px solid var(--primary);
        }
        .stat-card:hover { transform: translateY(-5px); }
        .stat-number { font-size: 32px; font-weight: 800; color: var(--text-main); }
        .stat-card p.text-muted { color: var(--text-muted) !important; }
        
        .top-bar {
            background: var(--bg-surface); border-radius: 15px; padding: 15px 20px;
            margin-bottom: 25px; box-shadow: 0 2px 10px var(--shadow-color);
            transition: background-color 0.3s ease;
        }

        /* --- Surcharges Bootstrap pour le Thème Sombre --- */
        [data-theme="dark"] .bg-white { background-color: var(--bg-surface) !important; }
        [data-theme="dark"] .card { background-color: var(--bg-surface); border-color: var(--border-color); }
        [data-theme="dark"] .card-header { background-color: var(--bg-surface) !important; border-bottom-color: var(--border-color); color: var(--text-main); }
        [data-theme="dark"] .list-group-item { background-color: var(--bg-surface); color: var(--text-main); border-color: var(--border-color); }
        [data-theme="dark"] .btn-light { background-color: #334155; color: #f1f5f9; border-color: #475569; }
        [data-theme="dark"] .btn-light:hover { background-color: #475569; color: #ffffff; }
        [data-theme="dark"] .dropdown-menu { background-color: var(--bg-surface); border-color: var(--border-color); }
        [data-theme="dark"] .dropdown-item { color: var(--text-main); }
        [data-theme="dark"] .dropdown-item:hover { background-color: #334155; }
        [data-theme="dark"] .text-muted { color: var(--text-muted) !important; }
        
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
            <p>Administrateur</p>
        </div>
        <nav class="nav flex-column mt-3">
            <a class="nav-link active" href="tableau_de_bord_admin.php">
                <i class="fas fa-tachometer-alt"></i> <span>Tableau de bord</span>
            </a>
            <a class="nav-link" href="utilisateur.php">
                <i class="fas fa-users"></i> <span>Utilisateurs</span>
            </a>
            <a class="nav-link" href="rayons.php">
                <i class="fas fa-layer-group"></i> <span>Rayons</span>
            </a>
            <a class="nav-link" href="../../../deconnexion.php">
                <i class="fas fa-sign-out-alt"></i> <span>Déconnexion</span>
            </a>
        </nav>
    </div>

    <div class="main-content">
        <div class="top-bar d-flex justify-content-between align-items-center">
            <h4 class="mb-0"><i class="fas fa-tachometer-alt me-2" style="color: var(--primary);"></i>Tableau de bord</h4>
            
            <div class="d-flex align-items-center">
                <button id="theme-toggle" class="btn btn-light me-3 rounded-circle" style="width: 40px; height: 40px; display: flex; align-items: center; justify-content: center;">
                    <i class="fas fa-moon"></i>
                </button>

                <div class="dropdown">
                    <button class="btn btn-light dropdown-toggle" data-bs-toggle="dropdown">
                        <i class="fas fa-user-circle me-2"></i> 
                        <?php echo htmlspecialchars($user['prenom'] . ' ' . $user['nom']); ?>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li><a class="dropdown-item text-danger" href="../../../deconnexion.php"><i class="fas fa-sign-out-alt"></i> Déconnexion</a></li>
                    </ul>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-3 mb-4">
                <div class="stat-card">
                    <i class="fas fa-users fa-2x" style="color: var(--primary);"></i>
                    <div class="stat-number"><?php echo $nbUtilisateurs; ?></div>
                    <p class="text-muted">Utilisateurs</p>
                </div>
            </div>
            <div class="col-md-3 mb-4">
                <div class="stat-card">
                    <i class="fas fa-layer-group fa-2x" style="color: var(--primary);"></i>
                    <div class="stat-number"><?php echo $nbRayons; ?></div>
                    <p class="text-muted">Rayons</p>
                </div>
            </div>
            <div class="col-md-3 mb-4">
                <div class="stat-card">
                    <i class="fas fa-boxes fa-2x" style="color: var(--primary);"></i>
                    <div class="stat-number"><?php echo $nbProduits; ?></div>
                    <p class="text-muted">Produits</p>
                </div>
            </div>
            <div class="col-md-3 mb-4">
                <div class="stat-card">
                    <i class="fas fa-bell fa-2x" style="color: var(--primary);"></i>
                    <div class="stat-number <?php echo $nbAlertes > 0 ? 'text-warning' : ''; ?>">
                        <?php echo $nbAlertes; ?>
                    </div>
                    <p class="text-muted">Alertes</p>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header bg-white">
                <h6 class="mb-0"><i class="fas fa-history me-2" style="color: var(--primary);"></i>Activité récente</h6>
            </div>
            <div class="card-body">
                <div class="list-group list-group-flush">
                    <?php if (!empty($alertes)): ?>
                        <?php foreach (array_slice($alertes, 0, 5) as $alerte): ?>
                            <div class="list-group-item px-0">
                                <i class="fas fa-bell text-warning me-2"></i>
                                <?php echo htmlspecialchars($alerte['message']); ?> - 
                                <small class="text-muted"><?php echo date('d/m/Y', strtotime($alerte['date_creation'])); ?></small>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="list-group-item px-0">
                            <i class="fas fa-check-circle text-success me-2"></i> Aucune alerte en cours
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <script src="../bootstrap-5.3.8-dist/js/bootstrap.bundle.min.js"></script>
    <script src="../../../public/js/responsive.js"></script>
    
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const themeToggle = document.getElementById('theme-toggle');
            const htmlEl = document.documentElement;
            const icon = themeToggle.querySelector('i');

            // Met à jour l'icône initiale en fonction du thème stocké
            if (htmlEl.getAttribute('data-theme') === 'dark') {
                icon.classList.remove('fa-moon');
                icon.classList.add('fa-sun');
            }

            themeToggle.addEventListener('click', () => {
                const currentTheme = htmlEl.getAttribute('data-theme');
                const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
                
                // Application du thème et sauvegarde dans le cache du navigateur
                htmlEl.setAttribute('data-theme', newTheme);
                localStorage.setItem('theme', newTheme);
                
                // Changement de l'icône (Soleil pour clair, Lune pour sombre)
                if (newTheme === 'dark') {
                    icon.classList.remove('fa-moon');
                    icon.classList.add('fa-sun');
                } else {
                    icon.classList.remove('fa-sun');
                    icon.classList.add('fa-moon');
                }
            });
        });
    </script>
</body>
</html>