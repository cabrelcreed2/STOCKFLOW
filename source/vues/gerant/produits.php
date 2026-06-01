<?php
session_start();

if (!isset($_SESSION['utilisateur']) || $_SESSION['utilisateur']['role'] !== 'GESTIONNAIRE') {
    header('Location: ../authentification/connexion.php');
    exit;
}

require_once __DIR__ . '/../../../source/controleurs/ProduitControleur.php';
require_once __DIR__ . '/../../../source/modeles/BaseDeDonnees.php';
require_once __DIR__ . '/../../../source/modeles/StockMagasin.php';

$produitCtrl = new ProduitControleur();
$categories = $produitCtrl->listeCategories();
$user = $_SESSION['utilisateur'];

// Filtres
$id_categorie = $_GET['categorie'] ?? '';
$recherche = $_GET['recherche'] ?? '';
$statut = $_GET['statut'] ?? '';

if (!empty($recherche)) {
    $produits = $produitCtrl->rechercherProduits($recherche);
} elseif (!empty($id_categorie)) {
    $produits = $produitCtrl->produitsParCategorie($id_categorie);
} else {
    $produits = $produitCtrl->listeProduits();
}

// Ajouter le stock magasin à chaque produit
$db = new Database();
$conn = $db->connect();
$stockMagasinModel = new StockMagasin($conn);
foreach ($produits as &$p) {
    $stock = $stockMagasinModel->getByProduit($p['id_produit']);
    $p['stock_magasin'] = $stock ? $stock['quantite'] : 0;
}
unset($p);

// Appliquer le filtre par statut
if (!empty($statut) && $statut !== 'tous') {
    $produits = array_filter($produits, function($p) use ($statut) {
        $stock = $p['stock_magasin'];
        $seuil = $p['seuil_alerte_magasin'];
        if ($statut === 'ok') return $stock > $seuil;
        if ($statut === 'bas') return $stock <= $seuil && $stock > 0;
        if ($statut === 'rupture') return $stock == 0;
        return true;
    });
}

$message = $_GET['message'] ?? '';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>STOCKFLOW - Produits</title>
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
            --bg-body: #f8fafc; --bg-sidebar: #ffffff; --bg-card: #ffffff;
            --text-main: #1e293b; --text-muted: #64748b;
            --border-color: rgba(0,0,0,0.1); --shadow-color: rgba(0,0,0,0.05);
            --input-bg: #ffffff; --input-text: #212529; --table-hover: rgba(0,0,0,0.05);
            --btn-theme-bg: #f8f9fa;
        }
        
        [data-theme="dark"] {
            --bg-body: #0f172a; --bg-sidebar: #1e293b; --bg-card: #1e293b;
            --text-main: #f8fafc; --text-muted: #94a3b8;
            --border-color: rgba(255,255,255,0.1); --shadow-color: rgba(0,0,0,0.2);
            --input-bg: #334155; --input-text: #f8fafc; --table-hover: rgba(255,255,255,0.05);
            --btn-theme-bg: #334155;
        }

        body { background-color: var(--bg-body); color: var(--text-main); transition: background-color 0.3s ease, color 0.3s ease; }
        .text-muted { color: var(--text-muted) !important; }
        .form-control, .form-select { background-color: var(--input-bg); color: var(--input-text); border-color: var(--border-color); }
        .form-control:focus, .form-select:focus { background-color: var(--input-bg); color: var(--input-text); }
        .form-control::placeholder { color: var(--text-muted); }
        .table { color: var(--text-main); }
        .table th, .table td { border-bottom-color: var(--border-color); color: var(--text-main); background: transparent; }
        .table-hover tbody tr:hover { background-color: var(--table-hover); color: var(--text-main); }
        .table-hover tbody tr:hover td { color: var(--text-main); background: transparent; }

        .sidebar {
            background: var(--bg-sidebar) !important; min-height: 100vh; color: var(--text-main);
            position: fixed; top: 0; left: 0; bottom: 0; width: 280px; overflow-y: auto;
            box-shadow: 2px 0 10px var(--shadow-color); border-right: 1px solid var(--border-color);
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
            background: var(--bg-card); border-radius: 15px; padding: 20px; 
            box-shadow: 0 2px 10px var(--shadow-color); border: 1px solid var(--border-color);
            transition: background-color 0.3s;
        }
        .btn-theme-toggle {
            background-color: var(--btn-theme-bg); color: var(--text-main);
            border: 1px solid var(--border-color); width: 40px; height: 40px;
            display: inline-flex; justify-content: center; align-items: center;
        }
        .stock-badge { padding: 5px 12px; border-radius: 20px; font-size: 12px; font-weight: 600; }
        .stock-ok { background: #d4edda; color: #155724; }
        .stock-alert { background: #f8d7da; color: #721c24; }
        .stock-moyen { background: #fff3cd; color: #856404; }
        .dropdown-menu { background-color: var(--bg-card); border-color: var(--border-color); }
        .dropdown-item { color: var(--text-main); }
        .dropdown-item:hover { background-color: var(--table-hover); color: var(--text-main); }
        .dropdown-header { color: var(--text-muted); }
        
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
            <a class="nav-link active" href="produits.php"><i class="fas fa-boxes"></i> <span>Produits</span></a>
            <a class="nav-link" href="fournisseurs.php"><i class="fas fa-truck"></i> <span>Fournisseurs</span></a>
            <a class="nav-link" href="stocks_consulter.php"><i class="fas fa-warehouse"></i> <span>Stocks</span></a>
            <a class="nav-link" href="commandes.php"><i class="fas fa-shopping-cart"></i> <span>Commandes</span></a>
            <a class="nav-link" href="commandes_historique.php"><i class="fas fa-history"></i> <span>Historique</span></a>
            <a class="nav-link" href="alertes.php"><i class="fas fa-bell"></i> <span>Alertes</span></a>
            <a class="nav-link" href="rapports.php"><i class="fas fa-chart-line"></i> <span>Rapports</span></a>
            <a class="nav-link" href="profil_gerant.php"><i class="fas fa-user"></i> <span>Profil</span></a>
            <a class="nav-link" href="../../../deconnexion.php"><i class="fas fa-sign-out-alt"></i> <span>Déconnexion</span></a>
        </nav>
    </div>

    <div class="main-content">
        <?php if (!empty($message)): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>

        <div class="d-flex justify-content-between align-items-center mb-4">
            <h3><i class="fas fa-boxes me-2" style="color: var(--primary);"></i>Gestion des produits</h3>
            <div class="d-flex align-items-center">
                <button class="btn btn-theme-toggle rounded-circle me-3" onclick="toggleTheme()" title="Basculer le thème">
                    <i id="theme-icon" class="fas fa-moon"></i>
                </button>
                <a href="produit_ajouter.php" class="btn btn-outline-primary"><i class="fas fa-plus"></i> Ajouter</a>
            </div>
        </div>

        <div class="content-card">
            <form method="GET" class="row mb-3">
                <div class="col-md-6 mb-2">
                    <input type="text" name="recherche" class="form-control" placeholder="🔍 Rechercher un produit..." value="<?php echo htmlspecialchars($recherche); ?>">
                </div>
                <div class="col-md-3 mb-2 ms-auto">
                    <div class="dropdown w-100">
                        <?php
                        $boutonTexte = '<i class="fas fa-filter me-1"></i> Filtrer';
                        if ($statut === 'ok') $boutonTexte = '<i class="fas fa-filter me-1"></i> 🟢 Stock OK';
                        elseif ($statut === 'bas') $boutonTexte = '<i class="fas fa-filter me-1"></i> 🟠 Stock bas';
                        elseif ($statut === 'rupture') $boutonTexte = '<i class="fas fa-filter me-1"></i> 🔴 Rupture';
                        elseif (!empty($id_categorie)) {
                            foreach ($categories as $c) {
                                if ($c['id_categorie'] == $id_categorie) {
                                    $boutonTexte = '<i class="fas fa-filter me-1"></i> 📌 ' . htmlspecialchars($c['nom']);
                                    break;
                                }
                            }
                        }
                        ?>
                        <button class="btn btn-outline-primary dropdown-toggle w-100" type="button" data-bs-toggle="dropdown">
                            <?php echo $boutonTexte; ?>
                        </button>
                        <ul class="dropdown-menu w-100">
                            <li><h6 class="dropdown-header">Par statut</h6></li>
                            <li><a class="dropdown-item" href="?statut=tous<?php echo !empty($recherche) ? '&recherche=' . urlencode($recherche) : ''; ?>">📋 Tous les statuts</a></li>
                            <li><a class="dropdown-item" href="?statut=ok<?php echo !empty($recherche) ? '&recherche=' . urlencode($recherche) : ''; ?>">🟢 Stock OK</a></li>
                            <li><a class="dropdown-item" href="?statut=bas<?php echo !empty($recherche) ? '&recherche=' . urlencode($recherche) : ''; ?>">🟠 Stock bas</a></li>
                            <li><a class="dropdown-item" href="?statut=rupture<?php echo !empty($recherche) ? '&recherche=' . urlencode($recherche) : ''; ?>">🔴 Rupture</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><h6 class="dropdown-header">Par catégorie</h6></li>
                            <li><a class="dropdown-item" href="?<?php echo !empty($recherche) ? 'recherche=' . urlencode($recherche) : ''; ?>">📁 Toutes catégories</a></li>
                            <?php foreach ($categories as $c): ?>
                                <li><a class="dropdown-item" href="?categorie=<?php echo $c['id_categorie']; ?><?php echo !empty($recherche) ? '&recherche=' . urlencode($recherche) : ''; ?>">📌 <?php echo htmlspecialchars($c['nom']); ?></a></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                </div>
            </form>

            <div class="table-responsive">
                <table class="table table-hover">
                    <thead><tr><th>Code</th><th>Produit</th><th>Catégorie</th><th>Prix</th><th>Stock</th><th>Seuil</th><th>Statut</th><th>Actions</th></tr></thead>
                    <tbody>
                        <?php if (!empty($produits)): ?>
                            <?php foreach ($produits as $p): 
                                $stockStatus = 'ok';
                                if ($p['stock_magasin'] <= $p['seuil_alerte_magasin'] && $p['stock_magasin'] > 0) $stockStatus = 'moyen';
                                if ($p['stock_magasin'] == 0) $stockStatus = 'alert';
                            ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($p['code_barre']); ?></td>
                                    <td><strong><?php echo htmlspecialchars($p['nom']); ?></strong></td>
                                    <td><?php echo htmlspecialchars($p['nom_categorie'] ?? 'N/A'); ?></td>
                                    <td><?php echo number_format($p['prix_vente'], 0, ',', ' '); ?> FCFA</td>
                                    <td><?php echo $p['stock_magasin']; ?></td>
                                    <td><?php echo $p['seuil_alerte_magasin']; ?></td>
                                    <td>
                                        <?php if ($stockStatus === 'ok'): ?><span class="stock-badge stock-ok">Stock OK</span>
                                        <?php elseif ($stockStatus === 'moyen'): ?><span class="stock-badge stock-moyen">Stock bas</span>
                                        <?php else: ?><span class="stock-badge stock-alert">Rupture</span><?php endif; ?>
                                    </td>
                                    <td>
                                        <a href="produit_modifier.php?id=<?php echo $p['id_produit']; ?>" class="btn btn-warning btn-sm"><i class="fas fa-edit"></i></a>
                                        <a href="?desactiver=<?php echo $p['id_produit']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('Désactiver ?')"><i class="fas fa-trash"></i></a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="8" class="text-center py-4 text-muted">Aucun produit trouvé.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script src="../bootstrap-5.3.8-dist/js/bootstrap.bundle.min.js"></script>
    <script src="../../../public/js/responsive.js"></script>
    
    <script>
        function updateThemeIcon(theme) {
            const icon = document.getElementById('theme-icon');
            if (icon) {
                if (theme === 'dark') { icon.classList.replace('fa-moon', 'fa-sun'); }
                else { icon.classList.replace('fa-sun', 'fa-moon'); }
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