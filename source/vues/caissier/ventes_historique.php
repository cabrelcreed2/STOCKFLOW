<?php
session_start();

// Vérifier connexion
if (!isset($_SESSION['utilisateur'])) {
    header('Location: ../authentification/connexion.php');
    exit;
}

// Vérifier le rôle
if ($_SESSION['utilisateur']['role'] !== 'CAISSIERE') {
    header('Location: ../authentification/connexion.php');
    exit;
}

// Inclure les modèles
require_once __DIR__ . '/../../../source/modeles/BaseDeDonnees.php';
require_once __DIR__ . '/../../../source/modeles/Vente.php';
require_once __DIR__ . '/../../../source/modeles/LigneVente.php';

// Connexion BDD
$db = new Database();
$conn = $db->connect();

// Récupérer le rayon de la caissière
$user = $_SESSION['utilisateur'];
$sql = "SELECT * FROM rayon WHERE id_caissiere = :id";
$stmt = $conn->prepare($sql);
$stmt->execute(['id' => $user['id_utilisateur']]);
$rayon = $stmt->fetch();

// Récupérer les filtres de date
$date_debut = !empty($_GET['date_debut']) ? $_GET['date_debut'] : null;
$date_fin = !empty($_GET['date_fin']) ? $_GET['date_fin'] : null;

// Récupérer les ventes
$venteModel = new Vente($conn);
$ligneVenteModel = new LigneVente($conn);

if ($rayon) {
    // Passage des filtres optionnels au modèle
    $ventes = $venteModel->getByRayon($rayon['id_rayon'], $date_debut, $date_fin);
} else {
    $ventes = [];
}

// Calculer les totaux de manière optimisée (Une seule requête par vente)
$totalGeneral = 0;
foreach ($ventes as $key => $v) {
    $montantVente = $ligneVenteModel->getTotal($v['id_vente']);
    $ventes[$key]['montant_total'] = $montantVente; // Stockage pour éviter un second appel en BDD plus bas
    $totalGeneral += $montantVente;
}

$nbVentes = count($ventes);
$moyenne = $nbVentes > 0 ? $totalGeneral / $nbVentes : 0;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>STOCKFLOW - Historique ventes</title>
    
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
            --filter-bg: #f1f5f9;
            --row-hover: #fff7ed;
        }

        [data-theme="dark"] {
            /* Thème Sombre */
            --bg-color: #0f172a;
            --surface-color: #1e293b;
            --text-main: #f8fafc;
            --text-muted: #94a3b8;
            --border-color: rgba(255, 255, 255, 0.08);
            --notif-bg: rgba(255, 255, 255, 0.03);
            --filter-bg: rgba(255, 255, 255, 0.02);
            --row-hover: rgba(249, 115, 22, 0.05);
        }

        * { font-family: 'Poppins', sans-serif; }
        
        body { 
            background-color: var(--bg-color); 
            color: var(--text-main);
            transition: background-color 0.3s ease, color 0.3s ease;
        }

        /* CORRECTIF : Forcer les classes textuelles Bootstrap à utiliser nos variables */
        .text-muted { color: var(--text-muted) !important; }
        .text-secondary { color: var(--text-muted) !important; }
        .text-dark { color: var(--text-main) !important; }
        h1, h2, h3, h4, h5, h6, label { color: var(--text-main); }
        
        /* Sidebar */
        .sidebar {
            background: var(--surface-color) !important; 
            min-height: 100vh; 
            color: var(--text-main);
            position: fixed; top: 0; left: 0; bottom: 0; width: 280px; overflow-y: auto;
            box-shadow: 2px 0 10px rgba(0,0,0,0.02); z-index: 1000;
            border-right: 1px solid var(--border-color);
            transition: all 0.3s ease;
        }
        .sidebar .logo { text-align: center; padding: 25px 0; border-bottom: 1px solid var(--border-color); }
        .sidebar .logo h4 { font-weight: 800; color: var(--primary); }
        .sidebar .logo i { color: var(--primary); background: rgba(249,115,22,0.1); padding: 12px; border-radius: 15px; }
        .sidebar .logo p { color: var(--text-muted); margin-bottom: 0; margin-top: 10px; font-weight: 500; }
        .sidebar .nav-link {
            color: var(--text-muted) !important; padding: 12px 25px; margin: 5px 0;
            border-radius: 10px; transition: all 0.3s ease; background: transparent;
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
        }

        .content-card { 
            background: var(--surface-color); 
            border-radius: 15px; 
            padding: 20px; 
            box-shadow: 0 2px 10px rgba(0,0,0,0.02); 
            border: 1px solid var(--border-color);
        }
        .summary-card { 
            background-color: var(--surface-color); 
            border-radius: 20px; 
            padding: 25px; 
            color: var(--text-main); 
            margin-bottom: 20px;
            border: 1px solid var(--border-color);
            box-shadow: 0 4px 15px rgba(0,0,0,0.01);
        }
        .filter-bar { 
            background: var(--filter-bg); 
            border-radius: 15px; 
            padding: 20px; 
            margin-bottom: 25px;
            border: 1px solid var(--border-color);
        }
        
        /* Formulaires & Tableaux */
        .form-control {
            background-color: var(--bg-color);
            border-color: var(--border-color);
            color: var(--text-main);
        }
        .form-control:focus {
            background-color: var(--bg-color);
            color: var(--text-main);
            border-color: var(--primary);
            box-shadow: 0 0 0 0.25rem rgba(249, 115, 22, 0.25);
        }
        .table {
            color: var(--text-main) !important;
            border-color: var(--border-color);
        }
        .table th { color: var(--text-main); }
        .vente-row { cursor: pointer; transition: background-color 0.2s; }
        .vente-row:hover { background: var(--row-hover) !important; }
        
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

        .btn-light {
            background-color: var(--bg-color);
            border-color: var(--border-color);
            color: var(--text-main);
        }
        .btn-light:hover, .btn-light:focus {
            background-color: var(--border-color);
            color: var(--text-main);
        }
        .dropdown-menu { background-color: var(--surface-color); border-color: var(--border-color); }
        .dropdown-item { color: var(--text-main); }
        .dropdown-item:hover { background-color: var(--bg-color); color: var(--text-main); }

        @media (max-width: 768px) { .sidebar { width: 70px; } .sidebar .nav-link span, .sidebar .logo p, .sidebar .logo h4 { display: none; } .main-content { margin-left: 70px; } }
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
            <a class="nav-link" href="tableau_de_bord_caissier.php"><i class="fas fa-tachometer-alt"></i> <span>Tableau de bord</span></a>
            <a class="nav-link" href="stock_rayon.php"><i class="fas fa-store"></i> <span>Stock rayon</span></a>
            <!-- <a class="nav-link" href="inventaire_rayon_importer.php"><i class="fas fa-upload"></i> <span>Importer inventaire rayon</span></a> -->
            <a class="nav-link" href="caisse.php"><i class="fas fa-cash-register"></i> <span>Caisse (vente)</span></a>
            <a class="nav-link active" href="ventes_historique.php"><i class="fas fa-history"></i> <span>Historique ventes</span></a>
            <a class="nav-link" href="casse_perime_signaler.php"><i class="fas fa-trash-alt"></i> <span>Signaler casse/périmé</span></a>
            <a class="nav-link" href="notifications.php"><i class="fas fa-bell"></i> <span>Notifications</span></a>
            <a class="nav-link" href="profil.php"><i class="fas fa-user"></i> <span>Mon profil</span></a>
            <a class="nav-link" href="../../../deconnexion.php"><i class="fas fa-sign-out-alt"></i> <span>Déconnexion</span></a>
        </nav>
    </div>

    <div class="main-content">
        
        <div class="top-bar d-flex justify-content-between align-items-center">
            <h4 class="mb-0"><i class="fas fa-history me-2" style="color: var(--primary);"></i>Historique des ventes</h4>
            
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
                        <li><hr class="dropdown-divider" style="border-color: var(--border-color);"></li>
                        <li><a class="dropdown-item text-danger" href="../../../deconnexion.php"><i class="fas fa-sign-out-alt me-2"></i>Déconnexion</a></li>
                    </ul>
                </div>
            </div>
        </div>
        
        <div class="summary-card">
            <p class="mb-1 text-uppercase fw-semibold tracking-wider" style="color: var(--text-muted); font-size: 12px;">Volume total des ventes filtrées</p>
            <h2 class="fw-bold mb-2" style="color: var(--primary);"><?php echo number_format($totalGeneral, 0, ',', ' '); ?> FCFA</h2>
            <div class="d-flex gap-3" style="color: var(--text-muted); font-size: 14px;">
                <span><i class="fas fa-shopping-basket me-1" style="color: var(--text-main);"></i> <strong style="color: var(--text-main);"><?php echo $nbVentes; ?></strong> transaction<?php echo $nbVentes > 1 ? 's' : ''; ?></span>
                <span>•</span>
                <span><i class="fas fa-calculator me-1" style="color: var(--text-main);"></i> Panier moyen : <strong style="color: var(--text-main);"><?php echo number_format($moyenne, 0, ',', ' '); ?> FCFA</strong></span>
            </div>
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
                <table class="table table-hover align-middle">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Heure</th>
                            <th>N° Vente</th>
                            <th>Montant</th>
                            <th>Mode de Paiement</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($ventes)): ?>
                            <?php foreach ($ventes as $vente): 
                                $badgeClass = 'bg-info text-dark';
                                if ($vente['mode_paiement'] === 'CARTE') $badgeClass = 'bg-secondary text-white';
                                if ($vente['mode_paiement'] === 'MIXTE') $badgeClass = 'bg-success text-white';
                            ?>
                                <tr class="vente-row" onclick="afficherDetail(<?php echo $vente['id_vente']; ?>)">
                                    <td style="color: var(--text-main);"><?php echo date('d/m/Y', strtotime($vente['date_vente'])); ?></td>
                                    <td><span style="color: var(--text-muted);"><?php echo date('H:i', strtotime($vente['date_vente'])); ?></span></td>
                                    <td><code style="color: var(--primary);">V-<?php echo $vente['id_vente']; ?></code></td>
                                    <td class="fw-bold" style="color: var(--text-main);"><?php echo number_format($vente['montant_total'], 0, ',', ' '); ?> FCFA</td>
                                    <td><span class="badge <?php echo $badgeClass; ?> px-2 py-1.5"><?php echo $vente['mode_paiement']; ?></span></td>
                                    <td class="text-end">
                                        <button class="btn btn-sm btn-light border" onclick="event.stopPropagation(); afficherDetail(<?php echo $vente['id_vente']; ?>)">
                                            <i class="fas fa-eye" style="color: var(--primary);"></i>
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" class="text-center py-5">
                                    <i class="fas fa-inbox fa-3x mb-3 d-block" style="color: var(--border-color);"></i>
                                    <p class="mb-0" style="color: var(--text-muted);">Aucune vente enregistrée pour les critères sélectionnés.</p>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="modal fade" id="detailModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content" style="background-color: var(--surface-color); color: var(--text-main); border: 1px solid var(--border-color);">
                <div class="modal-header border-bottom-0">
                    <h5 class="modal-title fw-bold"><i class="fas fa-receipt me-2" style="color: var(--primary);"></i>Détail de la vente</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" style="filter: var(--border-color);"></button>
                </div>
                <div class="modal-body" id="detailContent">
                    <div class="text-center py-4">
                        <div class="spinner-border text-primary" role="status"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="../bootstrap-5.3.8-dist/js/bootstrap.bundle.min.js"></script>
    <script src="../../../public/js/responsive.js"></script>
    <script>
        function afficherDetail(idVente) {
            document.getElementById('detailContent').innerHTML = '<div class="text-center py-4"><div class="spinner-border text-primary" role="status"></div></div>';
            var myModal = new bootstrap.Modal(document.getElementById('detailModal'));
            myModal.show();
            
            fetch('../../../source/vues/ventes/get_detail.php?id=' + idVente)
                .then(response => response.text())
                .then(html => {
                    document.getElementById('detailContent').innerHTML = html;
                });
        }

        // Script de synchronisation dynamique du thème global
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
            
            if (savedTheme) applyTheme(savedTheme);
            else if (systemPrefersDark) applyTheme('dark');

            themeToggleBtn.addEventListener('click', () => {
                const isDark = document.documentElement.getAttribute('data-theme') === 'dark';
                applyTheme(isDark ? 'light' : 'dark');
            });
        });
    </script>
</body>
</html>