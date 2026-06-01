<?php
session_start();

if (!isset($_SESSION['utilisateur']) || $_SESSION['utilisateur']['role'] !== 'GESTIONNAIRE') {
    header('Location: ../authentification/connexion.php');
    exit;
}

require_once __DIR__ . '/../../../source/controleurs/AlerteControleur.php';

$alerteCtrl = new AlerteControleur();
$alertes = $alerteCtrl->listeAlertesNonTraitees();
$user = $_SESSION['utilisateur'];

// Traiter une alerte
if (isset($_GET['traiter']) && isset($_GET['id'])) {
    $alerteCtrl->traiterAlerte($_GET['id'], $user['id_utilisateur']);
    header('Location: alertes.php');
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>STOCKFLOW - Alertes</title>
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
        
        /* VARIABLES THÈME CLAIR */
        :root { 
            --primary: #f97316; 
            --bg-body: #f8fafc;
            --bg-sidebar: #ffffff;
            --bg-card: #ffffff;
            --text-main: #1e293b;
            --text-muted: #64748b;
            --border-color: rgba(0,0,0,0.1);
            --shadow-color: rgba(0,0,0,0.05);
            --btn-theme-bg: #f8f9fa;
            --alert-critique-bg: #fef2f2;
            --alert-attention-bg: #fff7ed;
            --alert-info-bg: #eff6ff;
        }
        
        /* VARIABLES THÈME SOMBRE */
        [data-theme="dark"] {
            --bg-body: #0f172a;
            --bg-sidebar: #1e293b;
            --bg-card: #1e293b;
            --text-main: #f8fafc;
            --text-muted: #94a3b8;
            --border-color: rgba(255,255,255,0.1);
            --shadow-color: rgba(0,0,0,0.2);
            --btn-theme-bg: #334155;
            --alert-critique-bg: rgba(220, 38, 38, 0.15);
            --alert-attention-bg: rgba(249, 115, 22, 0.15);
            --alert-info-bg: rgba(59, 130, 246, 0.15);
        }

        /* APPLICATION DES VARIABLES */
        body { 
            background-color: var(--bg-body); 
            color: var(--text-main);
            transition: background-color 0.3s ease, color 0.3s ease;
        }
        
        .text-muted { color: var(--text-muted) !important; }

        .sidebar {
            background: var(--bg-sidebar) !important; 
            min-height: 100vh; 
            color: var(--text-main);
            position: fixed; top: 0; left: 0; bottom: 0; width: 280px; overflow-y: auto;
            box-shadow: 2px 0 10px var(--shadow-color);
            border-right: 1px solid var(--border-color);
            transition: background-color 0.3s ease;
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
        
        .content-card { 
            background: var(--bg-card); 
            border-radius: 15px; 
            padding: 20px; 
            box-shadow: 0 2px 10px var(--shadow-color);
            border: 1px solid var(--border-color);
            transition: background-color 0.3s;
        }

        .alerte-item { 
            border-left: 4px solid; 
            padding: 15px; 
            margin-bottom: 12px; 
            border-radius: 10px; 
            border-top: 1px solid var(--border-color);
            border-right: 1px solid var(--border-color);
            border-bottom: 1px solid var(--border-color);
            transition: background-color 0.3s;
        }
        .alerte-item.critique { border-left-color: #dc2626; background: var(--alert-critique-bg); }
        .alerte-item.attention { border-left-color: var(--primary); background: var(--alert-attention-bg); }
        .alerte-item.info { border-left-color: #3b82f6; background: var(--alert-info-bg); }

        .btn-theme-toggle {
            background-color: var(--btn-theme-bg);
            color: var(--text-main);
            border: 1px solid var(--border-color);
            width: 40px; height: 40px;
            display: inline-flex; justify-content: center; align-items: center;
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
        <div class="logo"><i class="fas fa-chart-line fa-2x"></i><h4>STOCK<span>FLOW</span></h4><p>Gérant</p></div>
        <nav class="nav flex-column mt-3">
            <a class="nav-link" href="tableau_de_bord_gerant.php"><i class="fas fa-tachometer-alt"></i> <span>Tableau de bord</span></a>
            <a class="nav-link" href="produits.php"><i class="fas fa-boxes"></i> <span>Produits</span></a>
            <a class="nav-link" href="fournisseurs.php"><i class="fas fa-truck"></i> <span>Fournisseurs</span></a>
            <a class="nav-link" href="stocks_consulter.php"><i class="fas fa-warehouse"></i> <span>Stocks</span></a>
            <a class="nav-link" href="commandes.php"><i class="fas fa-shopping-cart"></i> <span>Commandes</span></a>
            <a class="nav-link" href="commandes_historique.php"><i class="fas fa-history"></i> <span>Historique</span></a>
            <a class="nav-link active" href="alertes.php"><i class="fas fa-bell"></i> <span>Alertes</span></a>
            <a class="nav-link" href="rapports.php"><i class="fas fa-chart-line"></i> <span>Rapports</span></a>
            <a class="nav-link" href="profil_gerant.php"><i class="fas fa-user"></i> <span>Profil</span></a>
            <a class="nav-link" href="../../../deconnexion.php"><i class="fas fa-sign-out-alt"></i> <span>Déconnexion</span></a>
        </nav>
    </div>

    <div class="main-content">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h3><i class="fas fa-bell me-2" style="color: var(--primary);"></i>Gestion des alertes</h3>
            <div class="d-flex align-items-center">
                <button class="btn btn-theme-toggle rounded-circle" onclick="toggleTheme()" title="Basculer le thème">
                    <i id="theme-icon" class="fas fa-moon"></i>
                </button>
            </div>
        </div>

        <div class="content-card">
            <?php if (!empty($alertes)): ?>
                <?php foreach ($alertes as $a): 
                    $classe = $a['niveau'] === 'CRITIQUE' ? 'critique' : ($a['niveau'] === 'ATTENTION' ? 'attention' : 'info');
                    $badge = $a['niveau'] === 'CRITIQUE' ? 'bg-danger' : ($a['niveau'] === 'ATTENTION' ? 'bg-warning text-dark' : 'bg-info text-dark');
                ?>
                    <div class="alerte-item <?php echo $classe; ?>">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <strong><?php echo htmlspecialchars($a['nom_produit']); ?></strong><br>
                                <small><?php echo htmlspecialchars($a['message']); ?></small><br>
                                <small class="text-muted"><?php echo date('d/m/Y H:i', strtotime($a['date_creation'])); ?></small>
                            </div>
                            <div class="d-none d-md-block">
                                <span class="badge <?php echo $badge; ?>"><?php echo $a['niveau']; ?></span>
                            </div>
                            <div>
                                <a href="commande_passer.php" class="btn btn-sm btn-primary">Commander</a>
                                <a href="?traiter=1&id=<?php echo $a['id_alerte']; ?>" class="btn btn-sm btn-outline-secondary">Traiter</a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="text-center py-4 text-muted">
                    <i class="fas fa-check-circle fa-3x mb-3 text-success"></i>
                    <h5>Aucune alerte en cours</h5>
                </div>
            <?php endif; ?>
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
            
            document.documentElement.setAttribute('data-theme', newTheme);
            localStorage.setItem('stockflow_theme', newTheme);
            updateThemeIcon(newTheme);
        }

        document.addEventListener('DOMContentLoaded', () => {
            const savedTheme = localStorage.getItem('stockflow_theme') || 'light';
            updateThemeIcon(savedTheme);
        });
    </script>
</body>
</html>