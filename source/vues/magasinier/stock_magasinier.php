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
require_once __DIR__ . '/../../../source/modeles/StockMagasin.php';

// Connexion
$db = new Database();
$conn = $db->connect();
$user = $_SESSION['utilisateur'];

// Récupérer le stock magasin
$stockMagasinModel = new StockMagasin($conn);
$produits = $stockMagasinModel->getAll();

// Calculer les statistiques
$nbProduits = count($produits);
$nbStockBas = 0;
$nbPeremptionProche = 0;
foreach ($produits as $p) {
    if ($p['quantite'] <= $p['seuil_alerte_magasin']) $nbStockBas++;
    if (!empty($p['date_peremption']) && strtotime($p['date_peremption']) - time() < 30*24*3600) $nbPeremptionProche++;
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>STOCKFLOW - Stock magasin</title>
    
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
            --text-main: #1e293b;
            --text-muted: #64748b;
            --border-color: rgba(0,0,0,0.1);
            
            /* Couleurs Table & Input */
            --input-bg: #ffffff;
            --input-text: #1e293b;
            --input-border: #ced4da;
            --table-text: #1e293b;
            --table-border: #dee2e6;
            --table-hover: rgba(0,0,0,0.05);

            /* Couleurs Badges */
            --badge-ok-bg: #d4edda; --badge-ok-text: #155724;
            --badge-bas-bg: #fff3cd; --badge-bas-text: #856404;
            --badge-rupture-bg: #f8d7da; --badge-rupture-text: #721c24;

            /* Couleurs Alertes Stats */
            --alert-info-bg: #cff4fc; --alert-info-text: #055160;
            --alert-warning-bg: #fff3cd; --alert-warning-text: #664d03;
            --alert-danger-bg: #f8d7da; --alert-danger-text: #842029;
            --alert-border: rgba(0,0,0,0.1);
        }
        
        /* --- VARIABLES THÈME SOMBRE --- */
        [data-theme="dark"] {
            --bg-body: #0f172a;
            --bg-surface: #1e293b;
            --text-main: #f8fafc;
            --text-muted: #94a3b8;
            --border-color: rgba(255,255,255,0.1);

            /* Couleurs Table & Input */
            --input-bg: #0f172a;
            --input-text: #f8fafc;
            --input-border: #334155;
            --table-text: #f8fafc;
            --table-border: #334155;
            --table-hover: rgba(255,255,255,0.05);

            /* Couleurs Badges (Fond transparent/foncé, texte clair) */
            --badge-ok-bg: rgba(21, 87, 36, 0.4); --badge-ok-text: #75b798;
            --badge-bas-bg: rgba(133, 100, 4, 0.4); --badge-bas-text: #ffda6a;
            --badge-rupture-bg: rgba(114, 28, 36, 0.4); --badge-rupture-text: #ea868f;

            /* Couleurs Alertes Stats */
            --alert-info-bg: rgba(5, 81, 96, 0.4); --alert-info-text: #6edff6;
            --alert-warning-bg: rgba(102, 77, 3, 0.4); --alert-warning-text: #ffda6a;
            --alert-danger-bg: rgba(132, 32, 41, 0.4); --alert-danger-text: #ea868f;
            --alert-border: rgba(255,255,255,0.1);
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
            box-shadow: 2px 0 10px rgba(0,0,0,0.05);
            transition: background-color 0.3s ease;
            border-right: 1px solid var(--border-color);
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
            background: var(--bg-surface); 
            border-radius: 15px; padding: 20px; 
            box-shadow: 0 2px 10px rgba(0,0,0,0.05); 
            transition: background-color 0.3s ease;
        }
        
        /* Badges */
        .stock-ok, .peremption-ok { background: var(--badge-ok-bg); color: var(--badge-ok-text); padding: 5px 12px; border-radius: 20px; font-size: 12px; display: inline-flex; align-items: center; gap: 5px; }
        .stock-bas { background: var(--badge-bas-bg); color: var(--badge-bas-text); padding: 5px 12px; border-radius: 20px; font-size: 12px; display: inline-flex; align-items: center; gap: 5px; }
        .stock-rupture, .peremption-urgente { background: var(--badge-rupture-bg); color: var(--badge-rupture-text); padding: 5px 12px; border-radius: 20px; font-size: 12px; display: inline-flex; align-items: center; gap: 5px; }
        
        /* Table Override */
        .table { color: var(--table-text); border-color: var(--table-border); }
        .table th, .table td { background-color: var(--bg-surface); color: var(--table-text); border-bottom-color: var(--table-border); }
        .table-hover tbody tr:hover td, .table-hover tbody tr:hover th { background-color: var(--table-hover); color: var(--table-text); }

        /* Input Override */
        .form-control { background-color: var(--input-bg); color: var(--input-text); border-color: var(--input-border); }
        .form-control:focus { background-color: var(--input-bg); color: var(--input-text); border-color: var(--primary); box-shadow: 0 0 0 0.25rem rgba(249, 115, 22, 0.25); }

        /* Alert Override */
        .alert { border-color: var(--alert-border); }
        .alert-info { background-color: var(--alert-info-bg); color: var(--alert-info-text); }
        .alert-warning { background-color: var(--alert-warning-bg); color: var(--alert-warning-text); }
        .alert-danger { background-color: var(--alert-danger-bg); color: var(--alert-danger-text); }

        /* Theme Toggle Button */
        .theme-toggle-btn {
            background: transparent; border: none; color: var(--text-muted); font-size: 1.2rem; cursor: pointer; transition: color 0.3s ease;
        }
        .theme-toggle-btn:hover { color: var(--primary); }

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
            <a class="nav-link active" href="stock_magasinier.php"><i class="fas fa-warehouse"></i> <span>Stock magasin</span></a>
            <a class="nav-link" href="inventaire_importer.php"><i class="fas fa-upload"></i> <span>Importer inventaire</span></a>
            <a class="nav-link" href="transfert_effectuer.php"><i class="fas fa-exchange-alt"></i> <span>Effectuer un transfert</span></a>
            <a class="nav-link " href="transferts_historique.php"><i class="fas fa-history"></i> <span>Historique transferts</span></a>
            <a class="nav-link" href="casse_perime_signaler.php"><i class="fas fa-trash-alt"></i> <span>Signaler casse/périmé</span></a>
            <a class="nav-link" href="alertes_magasinier.php"><i class="fas fa-bell"></i> <span>Alertes</span></a>
            <a class="nav-link" href="profil_magasinier.php"><i class="fas fa-user"></i> <span>Mon profil</span></a>
            <a class="nav-link" href="../../../deconnexion.php"><i class="fas fa-sign-out-alt"></i> <span>Déconnexion</span></a>
        </nav>
    </div>

    <div class="main-content">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h3><i class="fas fa-warehouse me-2" style="color: var(--primary);"></i>Stock magasin</h3>
            <div class="d-flex align-items-center w-50 justify-content-end">
                <button id="theme-toggle" class="theme-toggle-btn me-3" title="Basculer le thème">
                    <i id="theme-icon" class="fas fa-moon"></i>
                </button>
                <input type="text" class="form-control w-50" id="recherche" placeholder="🔍 Rechercher..." onkeyup="filtrer()">
            </div>
        </div>

        <div class="content-card">
            <div class="table-responsive">
                <table class="table table-hover" id="tableStock">
                    <thead>
                        <tr>
                            <th>Code-barres</th>
                            <th>Produit</th>
                            <th>Qté</th>
                            <th>Seuil</th>
                            <th>État stock</th>
                            <th>Péremption</th>
                            <th>Statut</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($produits)): ?>
                            <?php foreach ($produits as $p): 
                                // État du stock
                                $etatStock = 'ok';
                                if ($p['quantite'] == 0) $etatStock = 'rupture';
                                elseif ($p['quantite'] <= $p['seuil_alerte_magasin']) $etatStock = 'bas';
                                
                                // Statut péremption
                                $etatPeremption = 'ok';
                                $joursRestants = null;
                                if (!empty($p['date_peremption'])) {
                                    $joursRestants = ceil((strtotime($p['date_peremption']) - time()) / (24*3600));
                                    if ($joursRestants <= 0) $etatPeremption = 'perime';
                                    elseif ($joursRestants <= 30) $etatPeremption = 'urgente';
                                }
                            ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($p['code_barre']); ?></td>
                                    <td><strong><?php echo htmlspecialchars($p['nom_produit']); ?></strong></td>
                                    <td class="<?php echo $etatStock === 'rupture' ? 'text-danger fw-bold' : ($etatStock === 'bas' ? 'text-warning fw-bold' : ''); ?>">
                                        <?php echo $p['quantite']; ?>
                                    </td>
                                    <td><?php echo $p['seuil_alerte_magasin']; ?></td>
                                    <td>
                                        <?php if ($etatStock === 'ok'): ?>
                                            <span class="stock-ok">🟢 Stock OK</span>
                                        <?php elseif ($etatStock === 'bas'): ?>
                                            <span class="stock-bas">🟠 Stock bas</span>
                                        <?php else: ?>
                                            <span class="stock-rupture">🔴 Rupture</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo $p['date_peremption'] ? date('d/m/Y', strtotime($p['date_peremption'])) : 'N/A'; ?></td>
                                    <td>
                                        <?php if ($etatPeremption === 'urgente'): ?>
                                            <span class="peremption-urgente">🔴 Urgent (<?php echo $joursRestants; ?>j)</span>
                                        <?php elseif ($etatPeremption === 'perime'): ?>
                                            <span class="peremption-urgente">⛔ Périmé</span>
                                        <?php else: ?>
                                            <span class="peremption-ok">🟢 OK</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="7" class="text-center py-4 text-muted">
                                    <i class="fas fa-inbox fa-2x mb-2"></i>
                                    <p>Aucun produit dans le stock magasin.</p>
                                    <a href="inventaire_importer.php" class="btn btn-sm btn-outline-primary">Importer l'inventaire</a>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <div class="row mt-3">
                <div class="col-md-4"><div class="alert alert-info">Total produits: <strong><?php echo $nbProduits; ?></strong></div></div>
                <div class="col-md-4"><div class="alert alert-warning">Stock bas: <strong><?php echo $nbStockBas; ?></strong></div></div>
                <div class="col-md-4"><div class="alert alert-danger">Péremption proche: <strong><?php echo $nbPeremptionProche; ?></strong></div></div>
            </div>
        </div>
    </div>

    <script src="../bootstrap-5.3.8-dist/js/bootstrap.bundle.min.js"></script>
    <script src="../../../public/js/responsive.js"></script>
    <script>
        function filtrer() {
            let recherche = document.getElementById('recherche').value.toLowerCase();
            let lignes = document.querySelectorAll('#tableStock tbody tr');
            lignes.forEach(ligne => {
                let texte = ligne.textContent.toLowerCase();
                ligne.style.display = texte.includes(recherche) ? '' : 'none';
            });
        }

        // Script de gestion du thème
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
                localStorage.setItem('theme', newTheme);
                updateIcon(newTheme);
            });
        });
    </script>
</body>
</html>