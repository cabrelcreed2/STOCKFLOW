<?php
session_start();

// Vérifier connexion
if (!isset($_SESSION['utilisateur'])) {
    header('Location: ../connexion.php');
    exit;
}

// Vérifier le rôle
if ($_SESSION['utilisateur']['role'] !== 'MAGASINIER') {
    header('Location: ../connexion.php');
    exit;
}

// Inclure les contrôleurs
require_once __DIR__ . '/../../../source/controleurs/RapportControleur.php';
require_once __DIR__ . '/../../../source/controleurs/AlerteControleur.php';
require_once __DIR__ . '/../../../source/controleurs/ProduitControleur.php';

// Connexion BDD
$db = new Database();
$conn = $db->connect();

// Initialiser
$alerteCtrl = new AlerteControleur();
$stockMagasinModel = new StockMagasin($conn);

// Données
$user = $_SESSION['utilisateur'];
$nbAlertesNonTraitees = $alerteCtrl->compterNonTraitees();
$stockMagasin = $stockMagasinModel->getAll();
$produitsAlerte = $stockMagasinModel->getProduitsEnAlerte();

// Calculer les statistiques
$nbProduits = 0;
$totalStock = 0;
foreach ($stockMagasin as $s) {
    $nbProduits++;
    $totalStock += $s['quantite'];
}

// Transferts du jour
$sql = "SELECT COUNT(*) FROM mouvement_stock WHERE type_mouvement = 'TRANSFERT' AND DATE(date_mouvement) = CURDATE()";
$nbTransferts = $conn->query($sql)->fetchColumn();
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>STOCKFLOW - Magasinier Dashboard</title>
    
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
        
        :root {
            --primary: #f97316; --primary-dark: #ea580c;
            --bg-body: #f8fafc; --bg-surface: #ffffff;
            --text-main: #1e293b; --text-muted: #64748b;
            --border-color: rgba(0,0,0,0.1); --alert-bg: #fff7ed;
            --dropdown-bg: #ffffff; --dropdown-hover: #f1f5f9;
        }

        [data-theme="dark"] {
            --bg-body: #0f172a; --bg-surface: #1e293b;
            --text-main: #f8fafc; --text-muted: #94a3b8;
            --border-color: rgba(255,255,255,0.1); --alert-bg: #3f1d0b;
            --dropdown-bg: #1e293b; --dropdown-hover: #334155;
        }

        body { background-color: var(--bg-body); color: var(--text-main); transition: background-color 0.3s ease, color 0.3s ease; }
        
        .sidebar {
            background: var(--bg-surface) !important; min-height: 100vh; color: var(--text-main);
            position: fixed; top: 0; left: 0; bottom: 0; width: 280px; overflow-y: auto;
            box-shadow: 2px 0 10px rgba(0,0,0,0.05); transition: background-color 0.3s ease;
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
            background: var(--bg-surface); border-radius: 15px; padding: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05); transition: transform 0.3s, background-color 0.3s ease;
            border-left: 4px solid var(--primary);
        }
        .stat-card:hover { transform: translateY(-5px); }
        .stat-number { font-size: 28px; font-weight: 800; color: var(--text-main); }
        .stat-card p { color: var(--text-muted) !important; }
        a.text-decoration-none { color: inherit; }
        a.text-decoration-none:hover { color: inherit; }
        
        .top-bar {
            background: var(--bg-surface); border-radius: 15px; padding: 15px 20px;
            margin-bottom: 25px; box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            transition: background-color 0.3s ease;
        }
        .alert-item {
            border-left: 4px solid var(--primary); padding: 12px;
            background: var(--alert-bg); margin-bottom: 10px; border-radius: 8px;
            color: var(--text-main); transition: background-color 0.3s ease;
        }
        .card { background-color: var(--bg-surface); color: var(--text-main); border-color: var(--border-color); }
        .card-header { background-color: var(--bg-surface) !important; border-bottom: 1px solid var(--border-color); }
        .dropdown-menu { background-color: var(--dropdown-bg); border-color: var(--border-color); }
        .dropdown-item { color: var(--text-main); }
        .dropdown-item:hover { background-color: var(--dropdown-hover); color: var(--text-main); }
        .dropdown-divider { border-top-color: var(--border-color); }
        .btn-light { background-color: var(--bg-surface); color: var(--text-main); border-color: var(--border-color); }
        .btn-light:hover { background-color: var(--dropdown-hover); color: var(--text-main); border-color: var(--border-color); }
        .theme-toggle-btn {
            background: transparent; border: none; color: var(--text-muted);
            font-size: 1.2rem; cursor: pointer; transition: color 0.3s ease; margin-right: 15px;
        }
        .theme-toggle-btn:hover { color: var(--primary); }

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
            <p>Magasinier</p>
        </div>
        <nav class="nav flex-column mt-3">
            <a class="nav-link active" href="tableau_de_bord_magasinier.php"><i class="fas fa-tachometer-alt"></i> <span>Tableau de bord</span></a>
            <a class="nav-link" href="stock_magasinier.php"><i class="fas fa-warehouse"></i> <span>Stock magasin</span></a>
            <a class="nav-link" href="inventaire_importer.php"><i class="fas fa-upload"></i> <span>Importer inventaire</span></a>
            <a class="nav-link" href="transfert_effectuer.php"><i class="fas fa-exchange-alt"></i> <span>Effectuer un transfert</span></a>
            <a class="nav-link" href="transferts_historique.php"><i class="fas fa-history"></i> <span>Historique de transferts</span></a>
            <a class="nav-link" href="alertes_magasinier.php"><i class="fas fa-bell"></i> <span>Alertes</span></a>
            <a class="nav-link" href="casse_perime_signaler.php"><i class="fas fa-trash-alt"></i> <span>Signaler casse/périmé</span></a>
            <a class="nav-link" href="profil_magasinier.php"><i class="fas fa-user"></i> <span>Mon profil</span></a>
            <a class="nav-link" href="../../../deconnexion.php"><i class="fas fa-sign-out-alt"></i> <span>Déconnexion</span></a>
        </nav>
    </div>

    <div class="main-content">
        
        <div class="top-bar d-flex justify-content-between align-items-center">
            <h4 class="mb-0"><i class="fas fa-tachometer-alt me-2" style="color: var(--primary);"></i>Tableau de bord</h4>
            <div class="d-flex align-items-center">
                <button id="theme-toggle" class="theme-toggle-btn" title="Basculer le thème">
                    <i id="theme-icon" class="fas fa-moon"></i>
                </button>
                
                <div class="dropdown">
                    <button class="btn btn-light dropdown-toggle" data-bs-toggle="dropdown">
                        <i class="fas fa-user-circle me-2"></i> 
                        <?php echo htmlspecialchars($user['prenom'] . ' ' . $user['nom']); ?>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li><a class="dropdown-item" href="profil_magasinier.php"><i class="fas fa-user"></i> Mon profil</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item text-danger" href="../../../deconnexion.php"><i class="fas fa-sign-out-alt"></i> Déconnexion</a></li>
                    </ul>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-3 mb-4">
                <a href="stock_magasinier.php" class="text-decoration-none">
                    <div class="stat-card">
                        <i class="fas fa-boxes fa-2x" style="color: var(--primary);"></i>
                        <div class="stat-number"><?php echo $nbProduits; ?></div>
                        <p class="mb-0">Produits différents</p>
                    </div>
                </a>
            </div>
            <div class="col-md-3 mb-4">
                <a href="transferts_historique.php" class="text-decoration-none">
                    <div class="stat-card">
                        <i class="fas fa-exchange-alt fa-2x" style="color: var(--primary);"></i>
                        <div class="stat-number"><?php echo $nbTransferts; ?></div>
                        <p class="mb-0">Transferts aujourd'hui</p>
                    </div>
                </a>
            </div>
            <div class="col-md-3 mb-4">
                <a href="alertes_magasinier.php" class="text-decoration-none">
                    <div class="stat-card">
                        <i class="fas fa-bell fa-2x" style="color: var(--primary);"></i>
                        <div class="stat-number <?php echo $nbAlertesNonTraitees > 0 ? 'text-warning' : ''; ?>">
                            <?php echo $nbAlertesNonTraitees; ?>
                        </div>
                        <p class="mb-0">Alertes reçues</p>
                    </div>
                </a>
            </div>
            <div class="col-md-3 mb-4">
                <a href="stock_magasinier.php" class="text-decoration-none">
                    <div class="stat-card">
                        <i class="fas fa-chart-line fa-2x" style="color: var(--primary);"></i>
                        <div class="stat-number"><?php echo number_format($totalStock, 0, ',', ' '); ?></div>
                        <p class="mb-0">Unités en stock</p>
                    </div>
                </a>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h6 class="mb-0"><i class="fas fa-bell me-2" style="color: var(--primary);"></i>Alertes de réapprovisionnement</h6>
            </div>
            <div class="card-body">
                <?php if (!empty($produitsAlerte)): ?>
                    <?php foreach (array_slice($produitsAlerte, 0, 5) as $alerte): ?>
                        <div class="alert-item">
                            <strong><?php echo htmlspecialchars($alerte['nom_produit']); ?></strong> - 
                            Stock: <?php echo $alerte['quantite']; ?> / Seuil: <?php echo $alerte['seuil_alerte_magasin']; ?>
                            <a href="transfert_effectuer.php" class="btn btn-sm btn-outline-primary float-end">Transférer</a>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p class="text-success mb-0"><i class="fas fa-check-circle me-2"></i>Tous les stocks sont au-dessus des seuils.</p>
                <?php endif; ?>
            </div>
        </div>
        
    </div>

    <script src="../bootstrap-5.3.8-dist/js/bootstrap.bundle.min.js"></script>
    <script src="../../../public/js/responsive.js"></script>
    
    <script>
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

            updateIcon(document.documentElement.getAttribute('data-theme'));

            themeToggleBtn.addEventListener('click', () => {
                let currentTheme = document.documentElement.getAttribute('data-theme');
                let newTheme = currentTheme === 'dark' ? 'light' : 'dark';
                
                document.documentElement.setAttribute('data-theme', newTheme);
                localStorage.setItem('stockflow_theme', newTheme);
                updateIcon(newTheme);
            });
        });
    </script>
</body>
</html>