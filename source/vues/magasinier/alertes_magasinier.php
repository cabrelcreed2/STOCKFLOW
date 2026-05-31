<?php
session_start();

if (!isset($_SESSION['utilisateur']) || $_SESSION['utilisateur']['role'] !== 'MAGASINIER') {
    header('Location: ../authentification/connexion.php');
    exit;
}

require_once __DIR__ . '/../../../source/controleurs/AlerteControleur.php';
require_once __DIR__ . '/../../../source/modeles/BaseDeDonnees.php';
require_once __DIR__ . '/../../../source/modeles/StockRayon.php';

$alerteCtrl = new AlerteControleur();
$alertes = $alerteCtrl->listeAlertesNonTraitees();
$user = $_SESSION['utilisateur'];

// Stats
$nbAlertes = count($alertes);
$nbUrgentes = 0;
$nbSurveiller = 0;
foreach ($alertes as $a) {
    if ($a['niveau'] === 'CRITIQUE') $nbUrgentes++;
    else $nbSurveiller++;
}

// Traiter
if (isset($_GET['traiter']) && isset($_GET['id'])) {
    $alerteCtrl->traiterAlerte($_GET['id'], $user['id_utilisateur']);
    header('Location: alertes_magasinier.php');
    exit;
}

// Récupérer les infos de stock pour la barre de progression
$db = new Database();
$conn = $db->connect();
$stockRayonModel = new StockRayon($conn);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>STOCKFLOW - Alertes magasinier</title>
    <link href="../bootstrap-5.3.8-dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../bootstrap-5.3.8-dist/fontawesome-free-6.7.2-web/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="../../../public/css/responsive.css" rel="stylesheet">
    
    <!-- Script d'initialisation immédiate du thème -->
    <script>
        const savedTheme = localStorage.getItem('theme') || 'light';
        document.documentElement.setAttribute('data-theme', savedTheme);
    </script>

    <style>
        * { font-family: 'Poppins', sans-serif; }
        
        /* --- VARIABLES THÈME CLAIR --- */
        :root { 
            --primary: #f97316; 
            --bg-body: #f8fafc;
            --bg-surface: #ffffff;
            --text-main: #1e293b;
            --text-muted: #64748b;
            --border-color: rgba(0,0,0,0.1);
            
            /* Spécifique Alertes */
            --item-haute-bg: #fef2f2;
            --item-haute-border: #dc2626;
            --item-moyenne-bg: #fff7ed;
            --item-moyenne-border: #f97316;
            --progress-track: #e2e8f0;

            /* Badges Bootstrap personnalisés si besoin */
            --alert-info-bg: #cff4fc; --alert-info-text: #055160;
            --alert-danger-bg: #f8d7da; --alert-danger-text: #842029;
            --alert-warning-bg: #fff3cd; --alert-warning-text: #664d03;
            --alert-secondary-bg: #e2e3e5; --alert-secondary-text: #41464b;
        }

        /* --- VARIABLES THÈME SOMBRE --- */
        [data-theme="dark"] {
            --bg-body: #0f172a;
            --bg-surface: #1e293b;
            --text-main: #f8fafc;
            --text-muted: #94a3b8;
            --border-color: rgba(255,255,255,0.1);
            
            /* Spécifique Alertes */
            --item-haute-bg: rgba(239, 68, 68, 0.1);
            --item-haute-border: #ef4444;
            --item-moyenne-bg: rgba(249, 115, 22, 0.1);
            --item-moyenne-border: #f97316;
            --progress-track: #334155;

            /* Ajustements stats cards pour le mode sombre */
            --alert-info-bg: rgba(13, 202, 240, 0.15); --alert-info-text: #6edff6;
            --alert-danger-bg: rgba(220, 53, 69, 0.15); --alert-danger-text: #ea868f;
            --alert-warning-bg: rgba(255, 193, 7, 0.15); --alert-warning-text: #ffda6a;
            --alert-secondary-bg: rgba(108, 117, 125, 0.15); --alert-secondary-text: #adb5bd;
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
        
        /* Content Card */
        .content-card { 
            background: var(--bg-surface); 
            border-radius: 15px; padding: 20px; 
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            transition: background-color 0.3s ease;
        }
        
        /* Alerte Items */
        .alerte-item { 
            border-left: 4px solid var(--border-color); 
            padding: 15px; margin-bottom: 12px; 
            border-radius: 10px; 
            background: var(--bg-body);
            transition: background-color 0.3s ease, border-color 0.3s ease;
        }
        .alerte-item.haute { border-left-color: var(--item-haute-border); background: var(--item-haute-bg); }
        .alerte-item.moyenne { border-left-color: var(--item-moyenne-border); background: var(--item-moyenne-bg); }
        
        /* Progress Bar */
        .progress-bar-custom { height: 6px; background: var(--progress-track); border-radius: 3px; margin-top: 5px; }
        .progress-fill { height: 6px; border-radius: 3px; }
        
        /* Force Theme Colors on Bootstrap Alerts (Stats Cards) */
        .alert.alert-info { background-color: var(--alert-info-bg); color: var(--alert-info-text); border: none; }
        .alert.alert-danger { background-color: var(--alert-danger-bg); color: var(--alert-danger-text); border: none; }
        .alert.alert-warning { background-color: var(--alert-warning-bg); color: var(--alert-warning-text); border: none; }
        .alert.alert-secondary { background-color: var(--alert-secondary-bg); color: var(--alert-secondary-text); border: none; }

        /* Theme Toggle Button */
        .theme-toggle-btn {
            background: transparent; border: none; color: var(--text-muted); font-size: 1.5rem; cursor: pointer; transition: color 0.3s ease;
        }
        .theme-toggle-btn:hover { color: var(--primary); }

        @media (max-width: 768px) { .sidebar { width: 70px; } .sidebar .nav-link span, .sidebar .logo p, .sidebar .logo h4 { display: none; } .main-content { margin-left: 70px; } }
    </style>
</head>
<body>

    <div class="sidebar">
        <div class="logo"><i class="fas fa-chart-line fa-2x"></i><h4>STOCK<span>FLOW</span></h4><p>Magasinier</p></div>
        <nav class="nav flex-column mt-3">
            <a class="nav-link" href="tableau_de_bord_magasinier.php"><i class="fas fa-tachometer-alt"></i> <span>Tableau de bord</span></a>
            <a class="nav-link" href="stock_magasinier.php"><i class="fas fa-warehouse"></i> <span>Stock magasin</span></a>
            <a class="nav-link" href="inventaire_importer.php"><i class="fas fa-upload"></i> <span>Importer inventaire</span></a>
            <a class="nav-link" href="transfert_effectuer.php"><i class="fas fa-exchange-alt"></i> <span>Effectuer un transfert</span></a>
            <a class="nav-link" href="casse_perime_signaler.php"><i class="fas fa-trash-alt"></i> <span>Signaler casse/périmé</span></a>
            <a class="nav-link active" href="alertes_magasinier.php"><i class="fas fa-bell"></i> <span>Alertes</span></a>
            <a class="nav-link" href="profil_magasinier.php"><i class="fas fa-user"></i> <span>Mon profil</span></a>
            <a class="nav-link" href="../../../deconnexion.php"><i class="fas fa-sign-out-alt"></i> <span>Déconnexion</span></a>
        </nav>
    </div>

    <div class="main-content">
        <!-- HEADER AVEC TITRE ET COMMUTATEUR THÈME -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h3 class="m-0"><i class="fas fa-bell me-2" style="color: var(--primary);"></i>Alertes - Stock bas rayon</h3>
            <button id="theme-toggle" class="theme-toggle-btn" title="Basculer le thème">
                <i id="theme-icon" class="fas fa-moon"></i>
            </button>
        </div>

        <div class="row mb-4">
            <div class="col-md-3"><div class="alert alert-info"><?php echo $nbAlertes; ?> alertes actives</div></div>
            <div class="col-md-3"><div class="alert alert-danger"><?php echo $nbUrgentes; ?> urgentes</div></div>
            <div class="col-md-3"><div class="alert alert-warning"><?php echo $nbSurveiller; ?> à surveiller</div></div>
            <div class="col-md-3"><div class="alert alert-secondary">Rayons concernés</div></div>
        </div>

        <div class="content-card">
            <?php if (!empty($alertes)): ?>
                <?php foreach ($alertes as $a): 
                    $classe = $a['niveau'] === 'CRITIQUE' ? 'haute' : 'moyenne';
                    $badge = $a['niveau'] === 'CRITIQUE' ? 'bg-danger' : 'bg-warning';
                    $label = $a['niveau'] === 'CRITIQUE' ? 'Urgent' : 'À surveiller';
                    
                    // Barre de progression
                    $pourcentage = 50;
                    if (!empty($a['message'])) {
                        preg_match('/\((\d+)/', $a['message'], $m);
                        if (!empty($m[1])) $pourcentage = min(100, ($m[1] / 30) * 100);
                    }
                    $barClass = $a['niveau'] === 'CRITIQUE' ? 'bg-danger' : 'bg-warning';
                ?>
                    <div class="alerte-item <?php echo $classe; ?>">
                        <div class="d-flex justify-content-between align-items-center">
                            <div style="flex:1;">
                                <strong><?php echo htmlspecialchars($a['nom_produit']); ?></strong><br>
                                <small class="text-muted">
                                    <?php if ($a['nom_rayon']): ?>Rayon: <?php echo htmlspecialchars($a['nom_rayon']); ?><?php endif; ?>
                                    <?php echo htmlspecialchars($a['message']); ?>
                                </small>
                                <div class="progress-bar-custom"><div class="progress-fill <?php echo $barClass; ?>" style="width: <?php echo $pourcentage; ?>%;"></div></div>
                            </div>
                            <div class="ms-3"><span class="badge <?php echo $badge; ?>"><?php echo $label; ?></span></div>
                            <div class="ms-3">
                                <a href="transfert_effectuer.php" class="btn btn-outline-primary btn-sm">Transférer</a>
                                <a href="?traiter=1&id=<?php echo $a['id_alerte']; ?>" class="btn btn-outline-secondary btn-sm">OK</a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="text-center py-4 text-muted">
                    <i class="fas fa-check-circle fa-3x mb-3 text-success"></i>
                    <h5>Aucune alerte</h5>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script src="../bootstrap-5.3.8-dist/js/bootstrap.bundle.min.js"></script>
    <script src="../../../public/js/responsive.js"></script>
    <script>
        // Gestion JavaScript du cycle thématique
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

            // Mettre l'icône à jour dès le chargement initial
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