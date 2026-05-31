<?php
session_start();

if (!isset($_SESSION['utilisateur']) || $_SESSION['utilisateur']['role'] !== 'GESTIONNAIRE') {
    header('Location: ../authentification/connexion.php');
    exit;
}

require_once __DIR__ . '/../../../source/modeles/BaseDeDonnees.php';
require_once __DIR__ . '/../../../source/modeles/StockMagasin.php';
require_once __DIR__ . '/../../../source/modeles/StockRayon.php';

$db = new Database();
$conn = $db->connect();
$stockMagasinModel = new StockMagasin($conn);
$stockRayonModel = new StockRayon($conn);

$stockMagasin = $stockMagasinModel->getAll();
$stockRayons = $stockRayonModel->getAll();
$user = $_SESSION['utilisateur'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>STOCKFLOW - État des stocks</title>
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
            --primary: #f97316; 
            --bg-body: #f8fafc;
            --bg-sidebar: #ffffff;
            --bg-card: #ffffff;
            --text-main: #1e293b;
            --text-muted: #64748b;
            --border-color: rgba(0,0,0,0.1);
            --shadow-color: rgba(0,0,0,0.05);
            --btn-theme-bg: #f8f9fa;
        }
        
        [data-theme="dark"] {
            --bg-body: #0f172a;
            --bg-sidebar: #1e293b;
            --bg-card: #1e293b;
            --text-main: #f8fafc;
            --text-muted: #94a3b8;
            --border-color: rgba(255,255,255,0.1);
            --shadow-color: rgba(0,0,0,0.2);
            --btn-theme-bg: #334155;
        }

        body { background-color: var(--bg-body); color: var(--text-main); transition: 0.3s; }
        
        .sidebar {
            background: var(--bg-sidebar) !important; color: var(--text-main);
            position: fixed; top: 0; left: 0; bottom: 0; width: 280px; overflow-y: auto;
            box-shadow: 2px 0 10px var(--shadow-color); border-right: 1px solid var(--border-color);
        }
        .sidebar .logo { text-align: center; padding: 25px 0; border-bottom: 1px solid var(--border-color); }
        .sidebar .logo h4 { font-weight: 800; color: var(--primary); }
        .sidebar .logo i { color: var(--primary); background: rgba(249,115,22,0.1); padding: 12px; border-radius: 15px; }
        .sidebar .logo p { color: var(--text-muted); }
        .sidebar .nav-link { color: var(--text-muted) !important; padding: 12px 25px; margin: 5px 0; border-radius: 10px; transition: 0.3s; }
        .sidebar .nav-link:hover, .sidebar .nav-link.active { background: var(--primary) !important; color: white !important; }
        .sidebar .nav-link i { width: 28px; margin-right: 12px; color: var(--primary); }
        .sidebar .nav-link:hover i, .sidebar .nav-link.active i { color: white; }
        
        .main-content { margin-left: 280px; padding: 20px; }
        .content-card { background: var(--bg-card); border-radius: 15px; padding: 20px; margin-bottom: 20px; box-shadow: 0 2px 10px var(--shadow-color); border: 1px solid var(--border-color); }
        
        .table { color: var(--text-main); }
        
        .stock-ok { background: #d4edda; color: #155724; padding: 5px 12px; border-radius: 20px; font-size: 12px; }
        .stock-bas { background: #fff3cd; color: #856404; padding: 5px 12px; border-radius: 20px; font-size: 12px; }
        .stock-rupture { background: #f8d7da; color: #721c24; padding: 5px 12px; border-radius: 20px; font-size: 12px; }

        .btn-theme-toggle { background-color: var(--btn-theme-bg); color: var(--text-main); border: 1px solid var(--border-color); width: 40px; height: 40px; }
        
        @media (max-width: 768px) { .sidebar { width: 70px; } .sidebar .nav-link span, .sidebar .logo p, .sidebar .logo h4 { display: none; } .main-content { margin-left: 70px; } }
    </style>
</head>
<body>

    <div class="sidebar">
        <div class="logo"><i class="fas fa-chart-line fa-2x"></i><h4>STOCK<span>FLOW</span></h4><p>Gestionnaire</p></div>
        <nav class="nav flex-column mt-3">
            <a class="nav-link" href="tableau_de_bord_gerant.php"><i class="fas fa-tachometer-alt"></i> <span>Tableau de bord</span></a>
            <a class="nav-link" href="produits.php"><i class="fas fa-boxes"></i> <span>Produits</span></a>
            <a class="nav-link" href="fournisseurs.php"><i class="fas fa-truck"></i> <span>Fournisseurs</span></a>
            <a class="nav-link active" href="stocks_consulter.php"><i class="fas fa-warehouse"></i> <span>Stocks</span></a>
            <a class="nav-link" href="commandes.php"><i class="fas fa-shopping-cart"></i> <span>Commandes</span></a>
            <a class="nav-link" href="commandes_historique.php"><i class="fas fa-history"></i> <span>Historique</span></a>
            <a class="nav-link" href="alertes.php"><i class="fas fa-bell"></i> <span>Alertes</span></a>
            <a class="nav-link" href="rapports.php"><i class="fas fa-chart-line"></i> <span>Rapports</span></a>
            <a class="nav-link" href="profil_gerant.php"><i class="fas fa-user"></i> <span>Profil</span></a>
            <a class="nav-link" href="../../../deconnexion.php"><i class="fas fa-sign-out-alt"></i> <span>Déconnexion</span></a>
        </nav>
    </div>

    <div class="main-content">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h3 class="m-0"><i class="fas fa-warehouse me-2" style="color: var(--primary);"></i>État global des stocks</h3>
            <button class="btn btn-theme-toggle rounded-circle" onclick="toggleTheme()" title="Basculer le thème">
                <i id="theme-icon" class="fas fa-moon"></i>
            </button>
        </div>

        <div class="content-card">
            <h5><i class="fas fa-building me-2" style="color: var(--primary);"></i>Stock Magasin (Réserve)</h5><hr>
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead><tr><th>Produit</th><th>Stock actuel</th><th>Seuil</th><th>Statut</th></tr></thead>
                    <tbody>
                        <?php foreach ($stockMagasin as $s): 
                            $statut = 'ok'; if ($s['quantite'] == 0) $statut = 'rupture'; elseif ($s['quantite'] <= $s['seuil_alerte_magasin']) $statut = 'bas';
                        ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($s['nom_produit']); ?></strong></td>
                                <td class="<?php echo $statut === 'rupture' ? 'text-danger fw-bold' : ($statut === 'bas' ? 'text-warning fw-bold' : ''); ?>"><?php echo $s['quantite']; ?></td>
                                <td><?php echo $s['seuil_alerte_magasin']; ?></td>
                                <td><?php if ($statut === 'ok'): ?><span class="stock-ok">🟢 OK</span><?php elseif ($statut === 'bas'): ?><span class="stock-bas">🟠 Stock bas</span><?php else: ?><span class="stock-rupture">🔴 Rupture</span><?php endif; ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="content-card">
            <h5><i class="fas fa-store me-2" style="color: var(--primary);"></i>Stock Rayons</h5><hr>
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead><tr><th>Produit</th><th>Rayon</th><th>Stock</th><th>Seuil</th><th>Statut</th></tr></thead>
                    <tbody>
                        <?php foreach ($stockRayons as $s): 
                            $statut = 'ok'; if ($s['quantite'] == 0) $statut = 'rupture'; elseif ($s['quantite'] <= $s['seuil_alerte_rayon']) $statut = 'bas';
                        ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($s['nom_produit']); ?></strong></td>
                                <td><?php echo htmlspecialchars($s['nom_rayon']); ?></td>
                                <td class="<?php echo $statut === 'rupture' ? 'text-danger fw-bold' : ($statut === 'bas' ? 'text-warning fw-bold' : ''); ?>"><?php echo $s['quantite']; ?></td>
                                <td><?php echo $s['seuil_alerte_rayon']; ?></td>
                                <td><?php if ($statut === 'ok'): ?><span class="stock-ok">🟢 OK</span><?php elseif ($statut === 'bas'): ?><span class="stock-bas">🟠 Stock bas</span><?php else: ?><span class="stock-rupture">🔴 Rupture</span><?php endif; ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script src="../bootstrap-5.3.8-dist/js/bootstrap.bundle.min.js"></script>
    <script src="../../../public/js/responsive.js"></script>
    <script>
        function toggleTheme() {
            const current = document.documentElement.getAttribute('data-theme');
            const next = current === 'dark' ? 'light' : 'dark';
            document.documentElement.setAttribute('data-theme', next);
            localStorage.setItem('stockflow_theme', next);
            document.getElementById('theme-icon').className = next === 'dark' ? 'fas fa-sun' : 'fas fa-moon';
        }
        document.addEventListener('DOMContentLoaded', () => {
            const saved = localStorage.getItem('stockflow_theme') || 'light';
            document.getElementById('theme-icon').className = saved === 'dark' ? 'fas fa-sun' : 'fas fa-moon';
        });
    </script>
</body>
</html>