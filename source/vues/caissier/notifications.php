<?php
session_start();

if (!isset($_SESSION['utilisateur']) || $_SESSION['utilisateur']['role'] !== 'CAISSIERE') {
    header('Location: ../authentification/connexion.php');
    exit;
}

require_once __DIR__ . '/../../../source/modeles/BaseDeDonnees.php';
require_once __DIR__ . '/../../../source/modeles/Notification.php';

$db = new Database();
$conn = $db->connect();
$notifModel = new Notification($conn);
$user = $_SESSION['utilisateur'];

// Filtre
$filtre = $_GET['filtre'] ?? 'all';

// Actions
if (isset($_GET['action']) && isset($_GET['id'])) {
    if ($_GET['action'] === 'lire') $notifModel->marquerLue($_GET['id']);
    header('Location: notifications.php?filtre=' . $filtre);
    exit;
}
if (isset($_GET['tout_lire'])) {
    $notifModel->marquerToutLu($user['id_utilisateur']);
    header('Location: notifications.php');
    exit;
}

// Récupérer les notifications
$notifications = $notifModel->getByUtilisateur($user['id_utilisateur']);
$nbNonLues = $notifModel->compterNonLues($user['id_utilisateur']);
$nbTotal = count($notifications);
$nbLues = $nbTotal - $nbNonLues;

// Filtrer
if ($filtre !== 'all') {
    $notifications = array_filter($notifications, function($n) use ($filtre) {
        return ($n['type_alerte'] ?? 'info') === $filtre;
    });
}

// Récupérer le rayon
$sql = "SELECT * FROM rayon WHERE id_caissiere = :id";
$stmt = $conn->prepare($sql);
$stmt->execute(['id' => $user['id_utilisateur']]);
$rayon = $stmt->fetch();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>STOCKFLOW - Notifications</title>
    <link href="../bootstrap-5.3.8-dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../bootstrap-5.3.8-dist/fontawesome-free-6.7.2-web/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="../../../public/css/responsive.css" rel="stylesheet">
    
    <script>
        if (localStorage.getItem('stockflow_theme') === 'dark' || (!localStorage.getItem('stockflow_theme') && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
            document.documentElement.setAttribute('data-theme', 'dark');
        }
    </script>
    
    <style>
        /* ==========================================
           VARIABLES DE THÈME (CLAIR / SOMBRE)
           ========================================== */
        :root {
            --primary: #f97316;
            --primary-dark: #ea580c;
            
            /* Thème Clair */
            --bg-color: #f8fafc;
            --surface-color: #ffffff;
            --text-main: #1e293b;
            --text-muted: #64748b;
            --border-color: rgba(0, 0, 0, 0.08);
            --notif-bg: #f8fafc;
            --notif-unread: #fff7ed;
            --filter-active-bg: #f97316;
            --filter-inactive-bg: #e2e8f0;
            --filter-inactive-text: #64748b;
        }

        [data-theme="dark"] {
            /* Thème Sombre */
            --bg-color: #0f172a;
            --surface-color: #1e293b;
            --text-main: #f8fafc;
            --text-muted: #94a3b8;
            --border-color: rgba(255, 255, 255, 0.08);
            --notif-bg: rgba(255, 255, 255, 0.02);
            --notif-unread: rgba(249, 115, 22, 0.1);
            --filter-active-bg: #f97316;
            --filter-inactive-bg: #334155;
            --filter-inactive-text: #94a3b8;
        }

        * { font-family: 'Poppins', sans-serif; }
        
        body { 
            background-color: var(--bg-color); 
            color: var(--text-main);
            transition: background-color 0.3s ease, color 0.3s ease;
        }
        
        /* Sidebar */
        .sidebar {
            background: var(--surface-color) !important; min-height: 100vh; color: var(--text-main);
            position: fixed; top: 0; left: 0; bottom: 0; width: 280px; overflow-y: auto;
            box-shadow: 2px 0 10px rgba(0,0,0,0.02); z-index: 1000;
            border-right: 1px solid var(--border-color);
            transition: all 0.3s ease;
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
        
        /* Contenu principal */
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

        .content-card { 
            background: var(--surface-color); 
            border-radius: 15px; 
            padding: 20px; 
            box-shadow: 0 2px 10px rgba(0,0,0,0.02); 
            border: 1px solid var(--border-color);
        }
        
        /* Notifications */
        .notification-item {
            background: var(--notif-bg); 
            border: 1px solid var(--border-color);
            border-left: 4px solid; 
            padding: 15px; 
            margin-bottom: 12px;
            border-radius: 10px; 
            display: flex; 
            justify-content: space-between; 
            align-items: center;
            color: var(--text-main);
            transition: background-color 0.2s;
        }
        .notification-item.unread { background: var(--notif-unread); font-weight: 700; }
        .notification-item.warning { border-left-color: #f97316; }
        .notification-item.danger { border-left-color: #dc2626; }
        .notification-item.success { border-left-color: #22c55e; }
        .notification-item.info { border-left-color: #3b82f6; }
        
        /* Filtres */
        .filter-bar { 
            background: var(--surface-color); 
            border: 1px solid var(--border-color);
            border-radius: 15px; 
            padding: 15px; 
            margin-bottom: 20px; 
        }
        .btn-filter { border-radius: 20px; padding: 5px 15px; margin: 3px; text-decoration: none; display: inline-block; font-size: 0.9rem; }
        .btn-filter-active { background: var(--filter-active-bg); color: white !important; }
        .btn-filter-inactive { background: var(--filter-inactive-bg); color: var(--filter-inactive-text) !important; }
        .btn-filter-inactive:hover { opacity: 0.9; }

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
            background-color: var(--bg-color);
            color: var(--primary);
        }

        /* Bootstrap UI Overrides */
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

        /* Correction alertes en mode sombre */
        [data-theme="dark"] .alert-info { background-color: rgba(59, 130, 246, 0.15); border-color: rgba(59, 130, 246, 0.3); color: #93c5fd; }
        [data-theme="dark"] .alert-warning { background-color: rgba(249, 115, 22, 0.15); border-color: rgba(249, 115, 22, 0.3); color: #fdba74; }
        [data-theme="dark"] .alert-success { background-color: rgba(34, 197, 94, 0.15); border-color: rgba(34, 197, 94, 0.3); color: #86efac; }

        @media (max-width: 768px) { .sidebar { width: 70px; } .sidebar .nav-link span, .sidebar .logo p, .sidebar .logo h4 { display: none; } .main-content { margin-left: 70px; } }
    </style>
</head>
<body>

    <div class="sidebar">
        <div class="logo"><i class="fas fa-chart-line fa-2x"></i><h4>STOCK<span>FLOW</span></h4><p>Caissier<?php echo $rayon ? ' - ' . htmlspecialchars($rayon['nom']) : ''; ?></p></div>
        <nav class="nav flex-column mt-3">
            <a class="nav-link" href="tableau_de_bord_caissier.php"><i class="fas fa-tachometer-alt"></i> <span>Tableau de bord</span></a>
            <a class="nav-link" href="stock_rayon.php"><i class="fas fa-store"></i> <span>Stock rayon</span></a>
            <!-- <a class="nav-link" href="inventaire_rayon_importer.php"><i class="fas fa-upload"></i> <span>Importer inventaire</span></a> -->
            <a class="nav-link" href="caisse.php"><i class="fas fa-cash-register"></i> <span>Caisse</span></a>
            <a class="nav-link" href="ventes_historique.php"><i class="fas fa-history"></i> <span>Historique ventes</span></a>
            <a class="nav-link" href="casse_perime_signaler.php"><i class="fas fa-trash-alt"></i> <span>Casse/Périmé</span></a>
            <a class="nav-link active" href="notifications.php"><i class="fas fa-bell"></i> <span>Notifications</span></a>
            <a class="nav-link" href="profil.php"><i class="fas fa-user"></i> <span>Profil</span></a>
            <a class="nav-link" href="../../../deconnexion.php"><i class="fas fa-sign-out-alt"></i> <span>Déconnexion</span></a>
        </nav>
    </div>

    <div class="main-content">
        
        <div class="top-bar d-flex justify-content-between align-items-center">
            <h4 class="mb-0"><i class="fas fa-bell me-2" style="color: var(--primary);"></i>Notifications</h4>
            
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

        <div class="filter-bar">
            <div class="d-flex flex-wrap gap-2 align-items-center">
                <a href="?filtre=all" class="btn-filter <?php echo $filtre==='all'?'btn-filter-active':'btn-filter-inactive'; ?>">Toutes</a>
                <a href="?filtre=STOCK_BAS_RAYON" class="btn-filter <?php echo $filtre==='STOCK_BAS_RAYON'?'btn-filter-active':'btn-filter-inactive'; ?>">⚠️ Alertes</a>
                <a href="?filtre=PERIME" class="btn-filter <?php echo $filtre==='PERIME'?'btn-filter-active':'btn-filter-inactive'; ?>">🔴 Critiques</a>
                <div class="ms-auto">
                    <a href="?tout_lire=1" class="btn btn-sm btn-outline-primary"><i class="fas fa-check-double me-1"></i> Tout marquer lu</a>
                </div>
            </div>
        </div>

        <div class="content-card">
            <?php if (!empty($notifications)): ?>
                <?php foreach ($notifications as $n): 
                    $typeClass = 'info';
                    if (strpos($n['type_alerte'] ?? '', 'STOCK') !== false) $typeClass = 'warning';
                    if (strpos($n['type_alerte'] ?? '', 'PERIME') !== false) $typeClass = 'danger';
                    if (strpos($n['type_alerte'] ?? '', 'TRANSFERT') !== false) $typeClass = 'success';
                ?>
                    <div class="notification-item <?php echo $typeClass; ?> <?php echo !$n['est_lue'] ? 'unread' : ''; ?>">
                        <div>
                            <strong><?php echo htmlspecialchars($n['type_alerte'] ?? 'Notification'); ?></strong><br>
                            <small><?php echo htmlspecialchars($n['message']); ?></small><br>
                            <small class="text-muted"><i class="far fa-clock me-1"></i><?php echo date('d/m/Y H:i', strtotime($n['date_envoi'])); ?></small>
                        </div>
                        <div>
                            <?php if (!$n['est_lue']): ?>
                                <a href="?action=lire&id=<?php echo $n['id_notification']; ?>&filtre=<?php echo $filtre; ?>" class="btn btn-sm btn-outline-primary">Marquer lue</a>
                            <?php else: ?>
                                <span class="badge bg-secondary">Lue</span>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="text-center py-4 text-muted">
                    <i class="fas fa-bell-slash fa-3x mb-3" style="opacity: 0.5;"></i>
                    <h5>Aucune notification</h5>
                </div>
            <?php endif; ?>
        </div>

        <div class="row mt-3">
            <div class="col-md-4"><div class="alert alert-info shadow-sm">Total: <strong><?php echo $nbTotal; ?></strong></div></div>
            <div class="col-md-4"><div class="alert alert-warning shadow-sm">Non lues: <strong><?php echo $nbNonLues; ?></strong></div></div>
            <div class="col-md-4"><div class="alert alert-success shadow-sm">Lues: <strong><?php echo $nbLues; ?></strong></div></div>
        </div>
    </div>

    <script src="../bootstrap-5.3.8-dist/js/bootstrap.bundle.min.js"></script>
    <script src="../../../public/js/responsive.js"></script>
    <script>
        // Script d'activation et d'écouteur pour le thème
        document.addEventListener('DOMContentLoaded', () => {
            const themeToggleBtn = document.getElementById('theme-toggle');
            const themeIcon = themeToggleBtn.querySelector('i');
            
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

            const savedTheme = localStorage.getItem('stockflow_theme');
            const systemPrefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
            
            if (savedTheme) {
                applyTheme(savedTheme);
            } else if (systemPrefersDark) {
                applyTheme('dark');
            }

            themeToggleBtn.addEventListener('click', () => {
                const isDark = document.documentElement.getAttribute('data-theme') === 'dark';
                applyTheme(isDark ? 'light' : 'dark');
            });
        });
    </script>
</body>
</html>