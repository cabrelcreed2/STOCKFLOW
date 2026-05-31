<?php
session_start();

// Vérifier connexion
if (!isset($_SESSION['utilisateur'])) {
    header('Location: ../connexion.php');
    exit;
}

// Vérifier le rôle
if ($_SESSION['utilisateur']['role'] !== 'CAISSIERE') {
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

// Récupérer le rayon de la caissière
$user = $_SESSION['utilisateur'];
$sql = "SELECT * FROM rayon WHERE id_caissiere = :id";
$stmt = $conn->prepare($sql);
$stmt->execute(['id' => $user['id_utilisateur']]);
$rayon = $stmt->fetch();

// Initialiser les contrôleurs
$alerteCtrl = new AlerteControleur();
$stockRayonModel = new StockRayon($conn);

// Données
$nbNotifsNonLues = $alerteCtrl->compterNotificationsNonLues($user['id_utilisateur']);
$notifications = $alerteCtrl->listeNotifications($user['id_utilisateur']);
$ventesDuJour = $conn->query("SELECT COUNT(*) as nb, COALESCE(SUM(lv.quantite * lv.prix_unitaire), 0) as total FROM vente v JOIN ligne_vente lv ON v.id_vente = lv.id_vente WHERE v.id_caissiere = " . $user['id_utilisateur'] . " AND DATE(v.date_vente) = CURDATE()")->fetch();
$nbProduitsRayon = 0;
if ($rayon) {
    $stockRayon = $stockRayonModel->getByRayon($rayon['id_rayon']);
    foreach ($stockRayon as $s) {
        $nbProduitsRayon += $s['quantite'];
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>STOCKFLOW - Caissier Dashboard</title>
    
    <link href="../bootstrap-5.3.8-dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../bootstrap-5.3.8-dist/fontawesome-free-6.7.2-web/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="../../../public/css/responsive.css" rel="stylesheet">
    
    
    <style>
        /* ==========================================
           1. THÈMES ET CONFIGURATION DES VARIABLES
           ========================================== */
        :root {
            --primary: #f97316;
            --primary-dark: #ea580c;
            
            /* Thème Clair (par défaut) */
            --bg-color: #f8fafc;
            --surface-color: #ffffff;
            --text-main: #1e293b;
            --text-muted: #64748b;
            --border-color: rgba(0, 0, 0, 0.08);
            --notif-bg: #f8fafc;
        }

        [data-theme="dark"] {
            /* Thème Sombre */
            --bg-color: #0f172a;
            --surface-color: #1e293b;
            --text-main: #f8fafc;
            --text-muted: #94a3b8;
            --border-color: rgba(255, 255, 255, 0.08);
            --notif-bg: rgba(255, 255, 255, 0.03);
        }

        * { 
            font-family: 'Poppins', sans-serif; 
            margin: 0; padding: 0; box-sizing: border-box;
        }
        
        body { 
            background-color: var(--bg-color); 
            color: var(--text-main);
            transition: background-color 0.3s ease, color 0.3s ease;
        }

        /* Classes utilitaires dynamiques */
        .bg-surface { 
            background-color: var(--surface-color) !important; 
            transition: background-color 0.3s ease;
        }
        .text-muted { 
            color: var(--text-muted) !important; 
        }

        /* Sidebar */
        .sidebar {
            background: var(--surface-color) !important;
            min-height: 100vh;
            color: var(--text-main);
            position: fixed;
            top: 0; left: 0; bottom: 0;
            width: 280px;
            overflow-y: auto;
            box-shadow: 2px 0 10px rgba(0,0,0,0.02);
            border-right: 1px solid var(--border-color);
            transition: all 0.3s ease;
        }
        .sidebar .logo { text-align: center; padding: 25px 0; border-bottom: 1px solid var(--border-color); }
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
        .sidebar .nav-link:hover { background: var(--primary) !important; color: white !important; transform: translateX(5px); }
        .sidebar .nav-link.active { background: var(--primary) !important; color: white !important; }
        .sidebar .nav-link i { width: 28px; margin-right: 12px; color: var(--primary); }
        .sidebar .nav-link:hover i, .sidebar .nav-link.active i { color: white; }
        
        /* Contenu Principal */
        .main-content { margin-left: 280px; padding: 20px; transition: margin-left 0.3s ease; }
        
        .top-bar {
            background: var(--surface-color);
            border-radius: 15px;
            padding: 15px 20px;
            margin-bottom: 25px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.02);
            border: 1px solid var(--border-color);
            transition: background-color 0.3s ease, border-color 0.3s ease;
        }

        .stat-card {
            background: var(--surface-color);
            border-radius: 15px;
            padding: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.02);
            transition: transform 0.3s, background-color 0.3s;
            border: 1px solid var(--border-color);
            border-left: 4px solid var(--primary);
        }
        .stat-card:hover { transform: translateY(-5px); }
        .stat-number { font-size: 28px; font-weight: 800; color: var(--text-main); }
        
        .card {
            background-color: var(--surface-color);
            border: 1px solid var(--border-color);
            border-radius: 15px;
            overflow: hidden;
        }
        .card-header {
            background-color: var(--surface-color) !important;
            border-bottom: 1px solid var(--border-color);
            color: var(--text-main);
        }

        .quick-action {
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            border: none;
            padding: 10px 25px;
            border-radius: 50px;
            color: white;
            font-weight: 600;
            transition: transform 0.2s ease;
        }
        .quick-action:hover { transform: scale(1.03); }
        
        .notification-item {
            background: var(--notif-bg);
            border-left: 3px solid var(--primary);
            padding: 12px;
            margin-bottom: 10px;
            border-radius: 8px;
            color: var(--text-main);
        }

        /* Bouton Switch Thème */
        .theme-toggle-btn {
            background: transparent;
            border: none;
            color: var(--text-main);
            cursor: pointer;
            padding: 8px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: background-color 0.2s;
        }
        .theme-toggle-btn:hover {
            background-color: var(--notif-bg);
            color: var(--primary);
        }

        /* Adaptation Bootstrap Menus */
        .btn-light {
            background-color: var(--bg-color);
            border-color: var(--border-color);
            color: var(--text-main);
        }
        .btn-light:hover, .btn-light:focus {
            background-color: var(--border-color);
            color: var(--text-main);
        }
        .dropdown-menu {
            background-color: var(--surface-color);
            border-color: var(--border-color);
        }
        .dropdown-item { color: var(--text-main); }
        .dropdown-item:hover { background-color: var(--bg-color); color: var(--text-main); }
        .dropdown-divider { border-top-color: var(--border-color); }

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
            <p>Caissier<?php echo $rayon ? ' - ' . htmlspecialchars($rayon['nom']) : ''; ?></p>
        </div>
        <nav class="nav flex-column mt-3">
            <a class="nav-link active" href="tableau_de_bord_caissier.php"><i class="fas fa-tachometer-alt"></i> <span>Tableau de bord</span></a>
            <a class="nav-link" href="stock_rayon.php"><i class="fas fa-store"></i> <span>Stock rayon</span></a>
            <!-- <a class="nav-link" href="inventaire_rayon_importer.php"><i class="fas fa-upload"></i> <span>Importer inventaire rayon</span></a> -->
            <a class="nav-link" href="caisse.php"><i class="fas fa-cash-register"></i> <span>Caisse (vente)</span></a>
            <a class="nav-link" href="ventes_historique.php"><i class="fas fa-history"></i> <span>Historique ventes</span></a>
            <a class="nav-link" href="casse_perime_signaler.php"><i class="fas fa-trash-alt"></i> <span>Signaler casse/périmé</span></a>
            <a class="nav-link" href="notifications.php"><i class="fas fa-bell"></i> <span>Notifications</span></a>
            <a class="nav-link" href="profil.php"><i class="fas fa-user"></i> <span>Mon profil</span></a>
            <a class="nav-link" href="../../../deconnexion.php"><i class="fas fa-sign-out-alt"></i> <span>Déconnexion</span></a>
        </nav>
    </div>

    <div class="main-content">
        
        <div class="top-bar d-flex justify-content-between align-items-center">
            <h4 class="mb-0"><i class="fas fa-tachometer-alt me-2" style="color: var(--primary);"></i>Tableau de bord</h4>
            
            <div class="d-flex align-items-center gap-3">
                <button id="theme-toggle" class="theme-toggle-btn" aria-label="Changer de thème">
                    <i class="fas fa-moon fs-5"></i>
                </button>

                <div class="dropdown">
                    <button class="btn btn-light dropdown-toggle" data-bs-toggle="dropdown">
                        <i class="fas fa-user-circle me-2"></i> 
                        <?php echo htmlspecialchars($user['prenom'] . ' ' . $user['nom']); ?>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li><a class="dropdown-item" href="profil.php"><i class="fas fa-user me-2"></i>Mon profil</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item text-danger" href="../../../deconnexion.php"><i class="fas fa-sign-out-alt me-2"></i>Déconnexion</a></li>
                    </ul>
                </div>
            </div>
        </div>

        <div class="bg-surface rounded-3 p-4 mb-4 d-flex justify-content-between align-items-center border">
            <div>
                <h5 class="mb-1">Bonjour, <?php echo htmlspecialchars($user['prenom']); ?> 👋</h5>
                <p class="text-muted mb-0">
                    Bienvenue sur votre espace de caisse
                    <?php if ($rayon): ?>
                        - <strong>Rayon <?php echo htmlspecialchars($rayon['nom']); ?></strong>
                    <?php endif; ?>
                </p>
            </div>
            <a href="caisse.php" class="quick-action text-white text-decoration-none">
                <i class="fas fa-cash-register me-2"></i> Ouvrir la caisse
            </a>
        </div>

        <div class="row">
            <div class="col-md-4 mb-4">
                <div class="stat-card">
                    <i class="fas fa-store fa-2x mb-2" style="color: var(--primary);"></i>
                    <div class="stat-number"><?php echo $nbProduitsRayon; ?></div>
                    <p class="text-muted mb-0">Produits en rayon</p>
                </div>
            </div>
            <div class="col-md-4 mb-4">
                <div class="stat-card">
                    <i class="fas fa-chart-line fa-2x mb-2" style="color: var(--primary);"></i>
                    <div class="stat-number"><?php echo $ventesDuJour['nb'] ?? 0; ?></div>
                    <p class="text-muted mb-0">Ventes aujourd'hui</p>
                    <small class="fw-bold" style="color: var(--primary);"><?php echo number_format($ventesDuJour['total'] ?? 0, 0, ',', ' '); ?> FCFA</small>
                </div>
            </div>
            <div class="col-md-4 mb-4">
                <div class="stat-card">
                    <i class="fas fa-bell fa-2x mb-2" style="color: var(--primary);"></i>
                    <div class="stat-number <?php echo $nbNotifsNonLues > 0 ? 'text-warning' : ''; ?>">
                        <?php echo $nbNotifsNonLues; ?>
                    </div>
                    <p class="text-muted mb-0">Notifications non lues</p>
                    <a href="notifications.php" class="text-decoration-none small" style="color: var(--primary);">Voir</a>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h6 class="mb-0"><i class="fas fa-bell me-2" style="color: var(--primary);"></i>Notifications récentes</h6>
            </div>
            <div class="card-body">
                <?php if (!empty($notifications)): ?>
                    <?php foreach (array_slice($notifications, 0, 5) as $notif): ?>
                        <div class="notification-item <?php echo !$notif['est_lue'] ? 'fw-bold' : ''; ?>">
                            <strong><?php echo $notif['type_alerte'] ? htmlspecialchars($notif['type_alerte']) : 'Information'; ?></strong> - 
                            <?php echo htmlspecialchars($notif['message']); ?>
                            <br><small class="text-muted"><?php echo date('d/m/Y H:i', strtotime($notif['date_envoi'])); ?></small>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p class="text-muted mb-0">Aucune notification.</p>
                <?php endif; ?>
            </div>
        </div>
        
    </div>

    <script src="../bootstrap-5.3.8-dist/js/bootstrap.bundle.min.js"></script>
    <script src="../../../public/js/responsive.js"></script>
    
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const themeToggleBtn = document.getElementById('theme-toggle');
            const themeIcon = themeToggleBtn.querySelector('i');
            
            // Appliquer le thème sélectionné
            const applyTheme = (theme) => {
                if (theme === 'dark') {
                    document.documentElement.setAttribute('data-theme', 'dark');
                    themeIcon.className = 'fas fa-sun fs-5';
                } else {
                    document.documentElement.removeAttribute('data-theme');
                    themeIcon.className = 'fas fa-moon fs-5';
                }
                localStorage.setItem('stockflow_theme', theme);
            };

            // Vérification de la configuration initiale (Sauvegardée vs Système)
            const savedTheme = localStorage.getItem('stockflow_theme');
            const systemPrefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
            
            if (savedTheme) {
                applyTheme(savedTheme);
            } else if (systemPrefersDark) {
                applyTheme('dark');
            }

            // Gestion de l'événement clic
            themeToggleBtn.addEventListener('click', () => {
                const isDark = document.documentElement.getAttribute('data-theme') === 'dark';
                applyTheme(isDark ? 'light' : 'dark');
            });
        });
    </script>
</body>
</html>