<?php
session_start();

if (!isset($_SESSION['utilisateur']) || $_SESSION['utilisateur']['role'] !== 'GESTIONNAIRE') {
    header('Location: ../authentification/connexion.php');
    exit;
}

require_once __DIR__ . '/../../../source/controleurs/CommandeControleur.php';
require_once __DIR__ . '/../../../source/modeles/BaseDeDonnees.php';
require_once __DIR__ . '/../../../source/modeles/Fournisseur.php';
require_once __DIR__ . '/../../../source/modeles/Produit.php';

$db = new Database();
$conn = $db->connect();
$fournisseurModel = new Fournisseur($conn);
$produitModel = new Produit($conn);
$fournisseurs = $fournisseurModel->getActifs();
$tousProduits = $produitModel->getAll();
$user = $_SESSION['utilisateur'];

$message = '';
$erreur = '';
$id_fournisseur = $_POST['id_fournisseur'] ?? '';

// Filtrer les produits du fournisseur sélectionné
$produitsFournisseur = [];
if (!empty($id_fournisseur)) {
    foreach ($tousProduits as $p) {
        if ($p['id_fournisseur'] == $id_fournisseur) $produitsFournisseur[] = $p;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['valider'])) {
    $id_fournisseur = $_POST['id_fournisseur'] ?? 0;
    $produits = [];
    
    if (!empty($_POST['id_produit'])) {
        foreach ($_POST['id_produit'] as $i => $id_produit) {
            $qte = $_POST['quantite'][$i] ?? 0;
            if ($qte > 0) $produits[] = ['id_produit' => $id_produit, 'quantite' => $qte];
        }
    }
    
    if (empty($id_fournisseur)) {
        $erreur = 'Sélectionnez un fournisseur.';
    } elseif (empty($produits)) {
        $erreur = 'Ajoutez au moins un produit.';
    } else {
        $commandeCtrl = new CommandeControleur();
        $resultat = $commandeCtrl->passerCommande($id_fournisseur, $produits, $user['id_utilisateur']);
        
        if (is_numeric($resultat)) {
            header('Location: commandes.php?message=Commande+créée');
            exit;
        } else {
            $erreur = $resultat;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>STOCKFLOW - Passer commande</title>
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
            --bg-row: #f8fafc;
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
            --bg-row: #334155;
            --text-main: #f8fafc;
            --text-muted: #94a3b8;
            --border-color: rgba(255,255,255,0.1);
            --shadow-color: rgba(0,0,0,0.2);
            --btn-theme-bg: #334155;
        }

        body { background-color: var(--bg-body); color: var(--text-main); transition: 0.3s; }
        
        .sidebar { background: var(--bg-sidebar) !important; color: var(--text-main); position: fixed; top: 0; left: 0; bottom: 0; width: 280px; overflow-y: auto; box-shadow: 2px 0 10px var(--shadow-color); border-right: 1px solid var(--border-color); }
        .sidebar .logo { text-align: center; padding: 25px 0; border-bottom: 1px solid var(--border-color); }
        .sidebar .logo h4 { font-weight: 800; color: var(--primary); }
        .sidebar .logo i { color: var(--primary); background: rgba(249,115,22,0.1); padding: 12px; border-radius: 15px; }
        .sidebar .logo p { color: var(--text-muted); }
        .sidebar .nav-link { color: var(--text-muted) !important; padding: 12px 25px; margin: 5px 0; border-radius: 10px; transition: 0.3s; }
        .sidebar .nav-link:hover, .sidebar .nav-link.active { background: var(--primary) !important; color: white !important; }
        .sidebar .nav-link i { width: 28px; margin-right: 12px; color: var(--primary); }
        .sidebar .nav-link:hover i, .sidebar .nav-link.active i { color: white; }
        
        .main-content { margin-left: 280px; padding: 20px; }
        .form-card { background: var(--bg-card); border-radius: 15px; padding: 30px; margin-bottom: 20px; box-shadow: 0 2px 10px var(--shadow-color); border: 1px solid var(--border-color); }
        .product-row { background: var(--bg-row); padding: 15px; border-radius: 10px; margin-bottom: 15px; border: 1px solid var(--border-color); }
        
        .form-control, .form-select { background-color: var(--bg-body); color: var(--text-main); border-color: var(--border-color); }
        .form-label { color: var(--text-main); }
        .btn-theme-toggle { background-color: var(--btn-theme-bg); color: var(--text-main); border: 1px solid var(--border-color); width: 40px; height: 40px; }
        
        @media (max-width: 768px) { .sidebar { width: 70px; } .sidebar .nav-link span, .sidebar .logo p, .sidebar .logo h4 { display: none; } .main-content { margin-left: 70px; } }
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
            <a class="nav-link active" href="commandes.php"><i class="fas fa-shopping-cart"></i> <span>Commandes</span></a>
            <a class="nav-link" href="alertes.php"><i class="fas fa-bell"></i> <span>Alertes</span></a>
            <a class="nav-link" href="rapports.php"><i class="fas fa-chart-line"></i> <span>Rapports</span></a>
            <a class="nav-link" href="profil_gerant.php"><i class="fas fa-user"></i> <span>Profil</span></a>
            <a class="nav-link" href="../../../deconnexion.php"><i class="fas fa-sign-out-alt"></i> <span>Déconnexion</span></a>
        </nav>
    </div>

    <div class="main-content">
        <?php if (!empty($message)): ?><div class="alert alert-success"><?php echo $message; ?></div><?php endif; ?>
        <?php if (!empty($erreur)): ?><div class="alert alert-danger"><?php echo $erreur; ?></div><?php endif; ?>

        <div class="d-flex justify-content-between align-items-center mb-4">
            <h3 class="m-0"><i class="fas fa-cart-plus me-2" style="color: var(--primary);"></i>Passer une commande</h3>
            <button class="btn btn-theme-toggle rounded-circle" onclick="toggleTheme()" title="Basculer le thème">
                <i id="theme-icon" class="fas fa-moon"></i>
            </button>
        </div>

        <form method="POST">
            <div class="form-card">
                <div class="mb-3">
                    <label class="form-label fw-semibold">Fournisseur</label>
                    <select name="id_fournisseur" class="form-select" required onchange="this.form.submit()">
                        <option value="">-- Sélectionner --</option>
                        <?php foreach ($fournisseurs as $f): ?>
                            <option value="<?php echo $f['id_fournisseur']; ?>" <?php echo $id_fournisseur == $f['id_fournisseur'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($f['nom']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <?php if (!empty($id_fournisseur)): ?>
            <div class="form-card">
                <h6 class="mb-3">Produits du fournisseur</h6>
                <?php foreach ($produitsFournisseur as $p): ?>
                <div class="product-row">
                    <div class="row align-items-center">
                        <div class="col-md-6">
                            <strong><?php echo htmlspecialchars($p['nom']); ?></strong>
                            <br><small class="text-muted">Prix achat: <?php echo number_format($p['prix_achat'], 0, ',', ' '); ?> FCFA</small>
                            <input type="hidden" name="id_produit[]" value="<?php echo $p['id_produit']; ?>">
                        </div>
                        <div class="col-md-4">
                            <input type="number" name="quantite[]" class="form-control" placeholder="Quantité" min="0">
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
                <?php if (empty($produitsFournisseur)): ?>
                    <p class="text-muted">Aucun produit pour ce fournisseur.</p>
                <?php endif; ?>
                <hr>
                <button type="submit" name="valider" class="btn btn-outline-primary"><i class="fas fa-check-circle me-1"></i> Valider la commande</button>
                <a href="commandes.php" class="btn btn-secondary">Annuler</a>
            </div>
            <?php endif; ?>
        </form>
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