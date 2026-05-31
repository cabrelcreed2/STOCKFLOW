<?php
session_start();

// Vérifier connexion
if (!isset($_SESSION['utilisateur'])) {
    header('Location: ../authentification/connexion.php');
    exit;
}

// Vérifier le rôle
if ($_SESSION['utilisateur']['role'] !== 'MAGASINIER') {
    header('Location: ../authentification/connexion.php');
    exit;
}

// Inclure les modèles
require_once __DIR__ . '/../../../source/modeles/BaseDeDonnees.php';
require_once __DIR__ . '/../../../source/modeles/MouvementStock.php';

// Connexion
$db = new Database();
$conn = $db->connect();
$user = $_SESSION['utilisateur'];

// Récupérer les transferts (filtrés si dates)
$mouvementModel = new MouvementStock($conn);

$date_debut = $_GET['date_debut'] ?? date('Y-m-d', strtotime('-7 days'));
$date_fin = $_GET['date_fin'] ?? date('Y-m-d');

$transferts = $mouvementModel->getByPeriode($date_debut, $date_fin);

// Filtrer uniquement les TRANSFERT
$transferts = array_filter($transferts, function($t) {
    return $t['type_mouvement'] === 'TRANSFERT' && strtotime($t['date_mouvement']) >= strtotime('-7 days');
});

// Re-trier
usort($transferts, function($a, $b) {
    return strtotime($b['date_mouvement']) - strtotime($a['date_mouvement']);
});

// Stats
$totalTransferts = count($transferts);
$totalArticles = 0;
$aujourdhui = 0;
foreach ($transferts as $t) {
    $totalArticles += $t['quantite'];
    if (date('Y-m-d', strtotime($t['date_mouvement'])) === date('Y-m-d')) $aujourdhui++;
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>STOCKFLOW - Historique transferts</title>
    
    <link href="../bootstrap-5.3.8-dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../bootstrap-5.3.8-dist/fontawesome-free-6.7.2-web/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="../../../public/css/responsive.css" rel="stylesheet">
    
    <script>
        const savedTheme = localStorage.getItem('theme') || 'light';
        document.documentElement.setAttribute('data-theme', savedTheme);
    </script>
    
    <style>
        * { font-family: 'Poppins', sans-serif; }
        
        /* --- VARIABLES THÈME CLAIR --- */
        :root { 
            --primary: #f97316; 
            --primary-dark: #ea580c; 
            --bg-body: #f8fafc;
            --bg-surface: #ffffff;
            --bg-filter: #f8fafc;
            --text-main: #1e293b;
            --text-muted: #64748b;
            --border-color: rgba(0,0,0,0.1);
            
            /* Formulaires */
            --input-bg: #ffffff;
            --input-text: #1e293b;
            --input-border: #ced4da;

            /* Tableau */
            --table-color: #1e293b;
            --table-border: #e2e8f0;
            --table-hover-bg: rgba(0,0,0,0.05);

            /* Badges & Alertes */
            --alert-info-bg: #cff4fc; --alert-info-text: #055160;
            --alert-success-bg: #d1e7dd; --alert-success-text: #0f5132;
            --alert-warning-bg: #fff3cd; --alert-warning-text: #664d03;
        }

        /* --- VARIABLES THÈME SOMBRE --- */
        [data-theme="dark"] {
            --bg-body: #0f172a;
            --bg-surface: #1e293b;
            --bg-filter: #0f172a;
            --text-main: #f8fafc;
            --text-muted: #94a3b8;
            --border-color: rgba(255,255,255,0.1);

            /* Formulaires */
            --input-bg: #0f172a;
            --input-text: #f8fafc;
            --input-border: #334155;

            /* Tableau */
            --table-color: #f8fafc;
            --table-border: #334155;
            --table-hover-bg: rgba(255,255,255,0.05);

            /* Badges & Alertes */
            --alert-info-bg: rgba(13, 202, 240, 0.15); --alert-info-text: #6edff6;
            --alert-success-bg: rgba(25, 135, 84, 0.15); --alert-success-text: #75b798;
            --alert-warning-bg: rgba(255, 193, 7, 0.15); --alert-warning-text: #ffda6a;
        }

        body { 
            background-color: var(--bg-body); 
            color: var(--text-main);
            transition: background-color 0.3s ease, color 0.3s ease;
        }
        
        /* Sidebar */
        .sidebar {
            background: var(--bg-surface) !important; min-height: 100vh; color: var(--text-main);
            position: fixed; top: 0; left: 0; bottom: 0; width: 280px; overflow-y: auto;
            box-shadow: 2px 0 10px rgba(0,0,0,0.05); z-index: 1000;
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
        
        /* Cards & Filters */
        .content-card { 
            background: var(--bg-surface); 
            border-radius: 15px; padding: 20px; 
            box-shadow: 0 2px 10px rgba(0,0,0,0.05); 
            transition: background-color 0.3s ease;
        }
        .filter-bar { 
            background: var(--bg-filter); 
            border-radius: 15px; padding: 15px; margin-bottom: 20px;
            border: 1px solid var(--border-color);
            transition: background-color 0.3s ease, border-color 0.3s ease;
        }
        
        /* Inputs */
        .form-control { 
            background-color: var(--input-bg); 
            color: var(--input-text); 
            border-color: var(--input-border); 
            color-scheme: light dark; /* Permet l'adaptation de l'icône calendrier natif */
        }
        .form-control:focus { 
            background-color: var(--input-bg); 
            color: var(--input-text); 
            border-color: var(--primary); 
            box-shadow: 0 0 0 0.25rem rgba(249, 115, 22, 0.25); 
        }

        /* Table */
        .table { color: var(--table-color); border-color: var(--table-border); transition: color 0.3s ease; }
        .table td, .table th { border-bottom-color: var(--table-border); }
        .table-hover tbody tr:hover { color: var(--table-color); background-color: var(--table-hover-bg); }
        .table-responsive { transition: background-color 0.3s ease; }

        /* Buttons & Alerts */
        .btn-outline-primary { color: var(--primary); border-color: var(--primary); }
        .btn-outline-primary:hover { background-color: var(--primary); border-color: var(--primary); color: #ffffff; }
        .btn-outline-primary:focus { box-shadow: none !important; }
        .btn-outline-primary:active { box-shadow: none !important; }
        .btn-outline-primary.show { box-shadow: none !important; }
        .alert-info { background-color: var(--alert-info-bg); color: var(--alert-info-text); border: none; }
        .alert-success { background-color: var(--alert-success-bg); color: var(--alert-success-text); border: none; }
        .alert-warning { background-color: var(--alert-warning-bg); color: var(--alert-warning-text); border: none; }

        /* Theme Toggle */
        .theme-toggle-btn {
            background: transparent; border: none; color: var(--text-muted); font-size: 1.5rem; cursor: pointer; transition: color 0.3s ease;
        }
        .theme-toggle-btn:hover { color: var(--primary); }

        .dropdown-item:active,
        .dropdown-item:focus {
            background-color: var(--primary) !important;
            color: white !important;
            outline: none !important;
            box-shadow: none !important;
        }

        .dropdown-item:hover {
            background-color: var(--primary) !important;
            color: white !important;
        }

        .btn-outline-primary:focus,
        .btn-outline-primary:active,
        .btn-outline-primary.show {
            background-color: var(--primary) !important;
            color: white !important;
            border-color: var(--primary) !important;
            box-shadow: none !important;
        }
        
        @media (max-width: 768px) { .sidebar { width: 70px; } .sidebar .nav-link span, .sidebar .logo p, .sidebar .logo h4 { display: none; } .main-content { margin-left: 70px; } }
    </style>
</head>
<body>

    <div class="sidebar">
        <div class="logo">
            <i class="fas fa-chart-line fa-2x"></i>
            <h4>STOCK<span>FLOW</span></h4>
            <p>Magasinier</p>
        </div>
        <nav class="nav flex-column mt-3">
            <a class="nav-link" href="tableau_de_bord_magasinier.php"><i class="fas fa-tachometer-alt"></i> <span>Tableau de bord</span></a>
            <a class="nav-link" href="stock_magasinier.php"><i class="fas fa-warehouse"></i> <span>Stock magasin</span></a>
            <a class="nav-link" href="inventaire_importer.php"><i class="fas fa-upload"></i> <span>Importer inventaire</span></a>
            <a class="nav-link" href="transfert_effectuer.php"><i class="fas fa-exchange-alt"></i> <span>Effectuer un transfert</span></a>
            <a class="nav-link active" href="transferts_historique.php"><i class="fas fa-history"></i> <span>Historique transferts</span></a>
            <a class="nav-link" href="casse_perime_signaler.php"><i class="fas fa-trash-alt"></i> <span>Signaler casse/périmé</span></a>
            <a class="nav-link" href="alertes_magasinier.php"><i class="fas fa-bell"></i> <span>Alertes</span></a>
            <a class="nav-link" href="profil_magasinier.php"><i class="fas fa-user"></i> <span>Mon profil</span></a>
            <a class="nav-link" href="../../../deconnexion.php"><i class="fas fa-sign-out-alt"></i> <span>Déconnexion</span></a>
        </nav>
    </div>

    <div class="main-content">
        
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h3 class="m-0"><i class="fas fa-history me-2" style="color: var(--primary);"></i>Historique des transferts</h3>
            <button id="theme-toggle" class="theme-toggle-btn" title="Basculer le thème">
                <i id="theme-icon" class="fas fa-moon"></i>
            </button>
        </div>

            <div class="filter-bar">
                <form method="GET" class="row align-items-end">
                <div class="col-md-3 mb-2">
                    <label class="form-label">Date début</label>
                    <input type="date" name="date_debut" class="form-control" value="<?php echo $date_debut; ?>">
                </div>
                <div class="col-md-3 mb-2">
                    <label class="form-label">Date fin</label>
                    <input type="date" name="date_fin" class="form-control" value="<?php echo $date_fin; ?>">
                </div>
                <div class="col-md-3 mb-2">
                    <div class="dropdown w-100">
                        <?php
                        $boutonTexte = '<i class="fas fa-filter me-1"></i> Filtrer';
                        if (!empty($_GET['date_debut']) || !empty($_GET['date_fin'])) {
                            $boutonTexte = '<i class="fas fa-filter me-1"></i>  Période filtrée';
                        }
                        ?>
                        <button class="btn btn-outline-primary dropdown-toggle w-100" type="button" data-bs-toggle="dropdown">
                            <?php echo $boutonTexte; ?>
                        </button>
                        <ul class="dropdown-menu w-100">
                            <li><h6 class="dropdown-header">Période rapide</h6></li>
                            <li><a class="dropdown-item" href="?date_debut=<?php echo date('Y-m-d'); ?>&date_fin=<?php echo date('Y-m-d'); ?>"> Aujourd'hui</a></li>
                            <li><a class="dropdown-item" href="?date_debut=<?php echo date('Y-m-d', strtotime('-7 days')); ?>&date_fin=<?php echo date('Y-m-d'); ?>"> 7 derniers jours</a></li>
                            <li><a class="dropdown-item" href="?date_debut=<?php echo date('Y-m-d', strtotime('-30 days')); ?>&date_fin=<?php echo date('Y-m-d'); ?>"> 30 derniers jours</a></li>
                            <li><a class="dropdown-item" href="?date_debut=<?php echo date('Y-m-01'); ?>&date_fin=<?php echo date('Y-m-d'); ?>"> Ce mois</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="transferts_historique.php"> Tout afficher</a></li>
                        </ul>
                    </div>
                </div>
                <div class="col-md-3 mb-2">
                    <button type="submit" class="btn btn-outline-primary w-100"><i class="fas fa-search me-1"></i>Rechercher</button>
                </div>
            </form>
        </div>

        <div class="content-card">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Rayon</th>
                            <th>Produit</th>
                            <th>Quantité</th>
                            <th>Magasinier</th>
                            <th>Motif</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($transferts)): ?>
                            <?php foreach ($transferts as $t): ?>
                                <tr>
                                    <td><?php echo date('d/m/Y H:i', strtotime($t['date_mouvement'])); ?></td>
                                    <td><?php echo htmlspecialchars($t['nom_rayon'] ?? 'N/A'); ?></td>
                                    <td><strong><?php echo htmlspecialchars($t['nom_produit']); ?></strong></td>
                                    <td><?php echo $t['quantite']; ?></td>
                                    <td><?php echo htmlspecialchars($t['prenom_utilisateur'] . ' ' . $t['nom_utilisateur']); ?></td>
                                    <td><?php echo htmlspecialchars($t['raison'] ?? 'Réapprovisionnement'); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" class="text-center py-4 text-muted">
                                    <i class="fas fa-inbox fa-2x mb-2"></i>
                                    <p>Aucun transfert trouvé sur cette période.</p>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <div class="row mt-3">
                <div class="col-md-4"><div class="alert alert-info">Total transferts: <strong><?php echo $totalTransferts; ?></strong></div></div>
                <div class="col-md-4"><div class="alert alert-success">Articles transférés: <strong><?php echo $totalArticles; ?></strong></div></div>
                <div class="col-md-4"><div class="alert alert-warning">Aujourd'hui: <strong><?php echo $aujourdhui; ?></strong></div></div>
            </div>
        </div>
    </div>

    <script src="../bootstrap-5.3.8-dist/js/bootstrap.bundle.min.js"></script>
    <script src="../../../public/js/responsive.js"></script>
    <script>
        // Logique de basculement du thème global
        document.addEventListener('DOMContentLoaded', () => {
            const themeToggleBtn = document.getElementById('theme-toggle');
            const themeIcon = document.getElementById('theme-icon');

            const updateIcon = (theme) => {
                if(theme === 'dark') {
                    themeIcon.classList.remove('fa-moon');
                    themeIcon.classList.add('fa-sun');
                } else {
                    themeIcon.classList.remove('fa-sun');
                    themeIcon.classList.add('fa-moon');
                }
            };

            // Ajustement immédiat de l'icône
            updateIcon(document.documentElement.getAttribute('data-theme'));

            themeToggleBtn.addEventListener('click', () => {
                let currentTheme = document.documentElement.getAttribute('data-theme');
                let newTheme = currentTheme === 'dark' ? 'light' : 'dark';
                
                document.documentElement.setAttribute('data-theme', newTheme);
                localStorage.setItem('theme', newTheme);
                updateIcon(newTheme);
            });
        });
    </script>
</body>
</html>