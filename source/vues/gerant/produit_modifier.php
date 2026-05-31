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
$fournisseurs = $produitCtrl->listeFournisseursActifs();
$user = $_SESSION['utilisateur'];

// Récupérer le produit
$id = $_GET['id'] ?? 0;
$produit = $produitCtrl->getProduit($id);

if (!$produit) {
    header('Location: produits.php');
    exit;
}

// Récupérer le stock magasin
$db = new Database();
$conn = $db->connect();
$stockModel = new StockMagasin($conn);
$stock = $stockModel->getByProduit($id);
$stockQte = $stock ? $stock['quantite'] : 0;

$message = '';
$erreur = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $resultat = $produitCtrl->modifierProduit(
        $id,
        $_POST['code_barre'] ?? $produit['code_barre'],
        $_POST['nom'] ?? '',
        $_POST['description'] ?? '',
        $_POST['prix_achat'] ?? 0,
        $_POST['prix_vente'] ?? 0,
        $_POST['seuil_magasin'] ?? 10,
        $_POST['seuil_rayon'] ?? 10,
        $_POST['peremption_defaut'] ?? 30,
        $_POST['id_categorie'] ?? 0,
        $_POST['id_fournisseur'] ?? 0
    );
    
    if ($resultat === true) {
        $message = 'Produit modifié !';
        $produit = $produitCtrl->getProduit($id);
    } else {
        $erreur = $resultat;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>STOCKFLOW - Modifier produit</title>
    <link href="../bootstrap-5.3.8-dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../bootstrap-5.3.8-dist/fontawesome-free-6.7.2-web/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="../../../public/css/responsive.css" rel="stylesheet">
    <style>
        * { font-family: 'Poppins', sans-serif; }
        body { background-color: #f8fafc; }
        :root { --primary: #f97316; }
        .sidebar {
            background: white !important; min-height: 100vh; color: #1e293b;
            position: fixed; top: 0; left: 0; bottom: 0; width: 280px; overflow-y: auto;
            box-shadow: 2px 0 10px rgba(0,0,0,0.05);
        }
        .sidebar .logo { text-align: center; padding: 25px 0; border-bottom: 1px solid rgba(0,0,0,0.1); }
        .sidebar .logo h4 { font-weight: 800; color: #f97316; }
        .sidebar .logo i { color: #f97316; background: rgba(249,115,22,0.1); padding: 12px; border-radius: 15px; }
        .sidebar .logo p { color: #64748b; }
        .sidebar .nav-link {
            color: #64748b !important; padding: 12px 25px; margin: 5px 0;
            border-radius: 10px; transition: all 0.3s ease; background: transparent;
        }
        .sidebar .nav-link:hover { background: #f97316 !important; color: white !important; transform: translateX(5px); }
        .sidebar .nav-link.active { background: #f97316 !important; color: white !important; }
        .sidebar .nav-link i { width: 28px; margin-right: 12px; color: #f97316; }
        .sidebar .nav-link:hover i, .sidebar .nav-link.active i { color: white; }
        .main-content { margin-left: 280px; padding: 20px; }
        .form-card { background: white; border-radius: 15px; padding: 30px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); }
        @media (max-width: 768px) { .sidebar { width: 70px; } .sidebar .nav-link span, .sidebar .logo p, .sidebar .logo h4 { display: none; } .main-content { margin-left: 70px; } }
    </style>
</head>
<body>

    <div class="sidebar">
        <div class="logo"><i class="fas fa-chart-line fa-2x"></i><h4>STOCK<span>FLOW</span></h4><p>Gestionnaire</p></div>
        <nav class="nav flex-column mt-3">
            <a class="nav-link" href="tableau_de_bord_gerant.php"><i class="fas fa-tachometer-alt"></i> <span>Tableau de bord</span></a>
            <a class="nav-link active" href="produits.php"><i class="fas fa-boxes"></i> <span>Produits</span></a>
            <a class="nav-link" href="fournisseurs.php"><i class="fas fa-truck"></i> <span>Fournisseurs</span></a>
            <a class="nav-link" href="stocks_consulter.php"><i class="fas fa-warehouse"></i> <span>Stocks</span></a>
            <a class="nav-link" href="commandes.php"><i class="fas fa-shopping-cart"></i> <span>Commandes</span></a>
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
            <h3><i class="fas fa-edit me-2" style="color: #f97316;"></i>Modifier produit</h3>
            <a href="produits.php" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Retour</a>
        </div>

        <div class="form-card">
            <form method="POST">
                <div class="row">
                    <div class="col-md-6 mb-3"><label class="form-label">Code-barres</label><input type="text" name="code_barre" class="form-control" value="<?php echo htmlspecialchars($produit['code_barre']); ?>" required></div>
                    <div class="col-md-6 mb-3"><label class="form-label">Nom du produit</label><input type="text" name="nom" class="form-control" value="<?php echo htmlspecialchars($produit['nom']); ?>" required></div>
                    <div class="col-md-6 mb-3"><label class="form-label">Description</label><input type="text" name="description" class="form-control" value="<?php echo htmlspecialchars($produit['description'] ?? ''); ?>"></div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Catégorie</label>
                        <select name="id_categorie" class="form-select" required>
                            <?php foreach ($categories as $c): ?>
                                <option value="<?php echo $c['id_categorie']; ?>" <?php echo $produit['id_categorie'] == $c['id_categorie'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($c['nom']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Fournisseur</label>
                        <select name="id_fournisseur" class="form-select" required>
                            <?php foreach ($fournisseurs as $f): ?>
                                <option value="<?php echo $f['id_fournisseur']; ?>" <?php echo $produit['id_fournisseur'] == $f['id_fournisseur'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($f['nom']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3 mb-3"><label class="form-label">Prix d'achat (FCFA)</label><input type="number" name="prix_achat" class="form-control" value="<?php echo $produit['prix_achat']; ?>" required></div>
                    <div class="col-md-3 mb-3"><label class="form-label">Prix de vente (FCFA)</label><input type="number" name="prix_vente" class="form-control" value="<?php echo $produit['prix_vente']; ?>" required></div>
                    <div class="col-md-3 mb-3"><label class="form-label">Stock actuel</label><input type="number" class="form-control" value="<?php echo $stockQte; ?>" readonly></div>
                    <div class="col-md-3 mb-3"><label class="form-label">Seuil magasin</label><input type="number" name="seuil_magasin" class="form-control" value="<?php echo $produit['seuil_alerte_magasin']; ?>"></div>
                </div>
                <hr>
                <button type="submit" class="btn btn-outline-primary"><i class="fas fa-save"></i> Mettre à jour</button>
                <a href="produits.php" class="btn btn-secondary">Annuler</a>
            </form>
        </div>
    </div>

    <script src="../bootstrap-5.3.8-dist/js/bootstrap.bundle.min.js"></script>
    <script src="../../../public/js/responsive.js"></script>
</body>
</html>