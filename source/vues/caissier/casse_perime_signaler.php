<?php
session_start();

// Vérifier connexion
if (!isset($_SESSION['utilisateur'])) {
    header('Location: ../authentification/connexion.php');
    exit;
}

// Vérifier le rôle
if (!in_array($_SESSION['utilisateur']['role'], ['CAISSIERE', 'MAGASINIER'])) {
    header('Location: ../authentification/connexion.php');
    exit;
}

// Inclure les contrôleurs
require_once __DIR__ . '/../../../source/controleurs/CassePerimeControleur.php';
require_once __DIR__ . '/../../../source/modeles/BaseDeDonnees.php';
require_once __DIR__ . '/../../../source/modeles/StockRayon.php';
require_once __DIR__ . '/../../../source/modeles/StockMagasin.php';

// Connexion
$db = new Database();
$conn = $db->connect();
$user = $_SESSION['utilisateur'];

// Récupérer le rayon (si caissière)
$rayon = null;
$produits = [];
if ($user['role'] === 'CAISSIERE') {
    $sql = "SELECT * FROM rayon WHERE id_caissiere = :id";
    $stmt = $conn->prepare($sql);
    $stmt->execute(['id' => $user['id_utilisateur']]);
    $rayon = $stmt->fetch();
    
    if ($rayon) {
        $stockRayonModel = new StockRayon($conn);
        $produits = $stockRayonModel->getByRayon($rayon['id_rayon']);
    }
} else {
    // Magasinier : produits du stock magasin
    $stockMagasinModel = new StockMagasin($conn);
    $produits = $stockMagasinModel->getAll();
}

// Traitement du formulaire
$message = '';
$erreur = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $type = $_POST['type'] ?? '';
    $id_produit = $_POST['id_produit'] ?? 0;
    $quantite = $_POST['quantite'] ?? 0;
    $commentaire = $_POST['commentaire'] ?? '';
    
    if (empty($type) || !in_array($type, ['CASSE', 'PERIME'])) {
        $erreur = 'Veuillez sélectionner un type (Casse ou Périmé).';
    } elseif (empty($id_produit) || $quantite <= 0) {
        $erreur = 'Veuillez sélectionner un produit et une quantité valide.';
    } else {
        // MODIFICATION ICI : On passe la connexion $conn au contrôleur
        $ctrl = new CassePerimeControleur($conn);
        
        if ($user['role'] === 'CAISSIERE') {
            $resultat = $ctrl->signalerRayon($id_produit, $rayon['id_rayon'], $type, $quantite, $commentaire, $user['id_utilisateur']);
        } else {
            $resultat = $ctrl->signalerMagasin($id_produit, $type, $quantite, $commentaire, $user['id_utilisateur']);
        }
        
        if ($resultat === true) {
            $message = 'Signalement enregistré avec succès.';
            // Rafraîchir les produits
            if ($user['role'] === 'CAISSIERE') {
                $stockRayonModel = new StockRayon($conn);
                $produits = $stockRayonModel->getByRayon($rayon['id_rayon']);
            } else {
                $stockMagasinModel = new StockMagasin($conn);
                $produits = $stockMagasinModel->getAll();
            }
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
    <title>STOCKFLOW - Signaler casse/périmé</title>
    
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
            --bg-color: #f8fafc;
            --surface-color: #ffffff;
            --text-main: #1e293b;
            --text-muted: #64748b;
            --border-color: rgba(0, 0, 0, 0.08);
            --card-hover-bg: #fff7ed;
            --input-bg: #ffffff;
        }

        [data-theme="dark"] {
            --bg-color: #0f172a;
            --surface-color: #1e293b;
            --text-main: #f8fafc;
            --text-muted: #94a3b8;
            --border-color: rgba(255, 255, 255, 0.08);
            --card-hover-bg: rgba(249, 115, 22, 0.04);
            --input-bg: #0f172a;
        }


        /* Fix pour la lisibilité des placeholders */
            .form-control::placeholder, 
            .form-select::placeholder {
                color: var(--text-muted) !important;
                opacity: 1; /* Important pour certains navigateurs qui réduisent l'opacité par défaut */
            }

        /* CORRECTIFS D'ADAPTATION */
        * { font-family: 'Poppins', sans-serif; }
        body { background-color: var(--bg-color); color: var(--text-main); transition: background 0.3s, color 0.3s; min-height: 100vh; }
        
        /* Forcer classes natives */
        .text-muted { color: var(--text-muted) !important; }
        .text-danger { color: #ef4444 !important; }
        h1, h2, h3, h4, h5, h6, label { color: var(--text-main); }
        
        /* Alertes dynamiques */
        .alert-success { background: rgba(34, 197, 94, 0.1) !important; color: #22c55e !important; border: 1px solid rgba(34, 197, 94, 0.2); }
        .alert-danger { background: rgba(239, 68, 68, 0.1) !important; color: #ef4444 !important; border: 1px solid rgba(239, 68, 68, 0.2); }

        /* Sidebar */
        .sidebar {
            background: var(--surface-color) !important; min-height: 100vh; color: var(--text-main);
            position: fixed; top: 0; left: 0; bottom: 0; width: 280px; overflow-y: auto;
            box-shadow: 2px 0 10px rgba(0,0,0,0.02); border-right: 1px solid var(--border-color); z-index: 1000;
        }
        .sidebar .logo { text-align: center; padding: 25px 0; border-bottom: 1px solid var(--border-color); }
        .sidebar .logo h4 { font-weight: 800; color: var(--primary); margin-bottom: 0; }
        .sidebar .logo i { color: var(--primary); background: rgba(249,115,22,0.1); padding: 12px; border-radius: 15px; }
        .sidebar .logo p { color: var(--text-muted); margin-top: 5px; margin-bottom: 0; font-size: 13px; }
        .sidebar .nav-link {
            color: var(--text-muted) !important; padding: 12px 25px; margin: 5px 0;
            border-radius: 10px; transition: all 0.3s ease; background: transparent;
        }
        .sidebar .nav-link:hover { background: var(--primary) !important; color: white !important; transform: translateX(5px); }
        .sidebar .nav-link.active { background: var(--primary) !important; color: white !important; }
        .sidebar .nav-link i { width: 28px; margin-right: 12px; color: var(--primary); }
        .sidebar .nav-link:hover i, .sidebar .nav-link.active i { color: white; }
        
        /* Contenu */
        .main-content { margin-left: 280px; padding: 30px; }
        .rayon-header { background-color: var(--surface-color); border-radius: 15px; padding: 20px; border: 1px solid var(--border-color); margin-bottom: 25px; }
        .form-card { background: var(--surface-color); border-radius: 15px; padding: 35px; border: 1px solid var(--border-color); }
        
        /* Cartes types */
        .type-card { border: 2px solid var(--border-color); border-radius: 15px; padding: 20px; text-align: center; cursor: pointer; transition: all 0.2s ease-in-out; background: var(--surface-color); color: var(--text-main); }
        .type-card:hover { transform: translateY(-2px); background: var(--card-hover-bg); }
        .type-card.selected { border-color: var(--primary); background: var(--card-hover-bg); }
        .type-card i { font-size: 36px; margin-bottom: 12px; }
        .type-card.casse i { color: #ef4444; }
        .type-card.perime i { color: #f97316; }
        
        /* Formulaires */
        .form-control, .form-select { background-color: var(--input-bg) !important; color: var(--text-main) !important; border-color: var(--border-color); padding: 10px 15px; }
        .form-control:focus, .form-select:focus { border-color: var(--primary); box-shadow: 0 0 0 0.25rem rgba(249, 115, 22, 0.15); }
        
        .btn-danger { background: linear-gradient(135deg, #ef4444, #dc2626); border: none; padding: 10px 24px; border-radius: 10px; font-weight: 500; color: white; }
        .btn-secondary { background: var(--bg-color); border: 1px solid var(--border-color); color: var(--text-main); padding: 10px 24px; border-radius: 10px; font-weight: 500; }
        .btn-secondary:hover { background: var(--border-color); color: var(--text-main); }
        
        @media (max-width: 768px) { .sidebar { width: 70px; } .sidebar .nav-link span, .sidebar .logo p, .sidebar .logo h4 { display: none; } .main-content { margin-left: 70px; padding: 15px; } }
    </style>
</head>
<body>

    <div class="sidebar">
        <div class="logo">
            <i class="fas fa-chart-line fa-2x"></i>
            <h4>STOCK<span>FLOW</span></h4>
            <p><?php echo htmlspecialchars($user['role'] === 'CAISSIERE' ? 'Caissier' : 'Magasinier'); ?><?php echo $rayon ? ' - ' . htmlspecialchars($rayon['nom']) : ''; ?></p>
        </div>
        <nav class="nav flex-column mt-3">
            <?php if ($user['role'] === 'CAISSIERE'): ?>
                <a class="nav-link" href="tableau_de_bord_caissier.php"><i class="fas fa-tachometer-alt"></i> <span>Tableau de bord</span></a>
                <a class="nav-link" href="stock_rayon.php"><i class="fas fa-store"></i> <span>Stock rayon</span></a>
                <a class="nav-link" href="caisse.php"><i class="fas fa-cash-register"></i> <span>Caisse (vente)</span></a>
                <a class="nav-link" href="ventes_historique.php"><i class="fas fa-history"></i> <span>Historique ventes</span></a>
            <?php else: ?>
                <a class="nav-link" href="../magasinier/tableau_de_bord_magasiner.php"><i class="fas fa-tachometer-alt"></i> <span>Tableau de bord</span></a>
                <a class="nav-link" href="../magasinier/stock_magasin.php"><i class="fas fa-warehouse"></i> <span>Stock magasin</span></a>
                <a class="nav-link" href="../magasinier/transfert_effectuer.php"><i class="fas fa-exchange-alt"></i> <span>Transfert</span></a>
            <?php endif; ?>
            <a class="nav-link active" href="#"><i class="fas fa-trash-alt"></i> <span>Signaler casse/périmé</span></a>
            <a class="nav-link" href="<?php echo $user['role'] === 'CAISSIERE' ? 'notifications.php' : '../magasinier/alertes_magasiner.php'; ?>"><i class="fas fa-bell"></i> <span><?php echo $user['role'] === 'CAISSIERE' ? 'Notifications' : 'Alertes'; ?></span></a>
            <a class="nav-link" href="../../../deconnexion.php"><i class="fas fa-sign-out-alt"></i> <span>Déconnexion</span></a>
        </nav>
    </div>

    <div class="main-content">
        
        <div class="rayon-header d-flex justify-content-between align-items-center">
            <div>
                <h5 class="mb-1 fw-bold">
                    <i class="fas fa-map-marker-alt me-2" style="color: var(--primary);"></i>
                    <?php if ($user['role'] === 'CAISSIERE' && $rayon): ?>
                        Rayon : <?php echo htmlspecialchars($rayon['nom']); ?>
                    <?php else: ?>
                        Stock Principal Magasin
                    <?php endif; ?>
                </h5>
                <p class="mb-0 text-muted small">
                    <?php if ($user['role'] === 'CAISSIERE' && $rayon): ?>
                        Zone : <?php echo htmlspecialchars($rayon['emplacement'] ?? 'Non spécifiée'); ?>
                    <?php else: ?>
                        Gestion des rebuts et anomalies de la réserve
                    <?php endif; ?>
                </p>
            </div>
            <div class="text-end text-muted small d-none d-sm-block">
                Opérateur : <strong><?php echo htmlspecialchars($user['prenom'] . ' ' . $user['nom']); ?></strong>
            </div>
        </div>

        <?php if (!empty($message)): ?>
            <div class="alert alert-success alert-dismissible fade show border-0 shadow-sm mb-4">
                <i class="fas fa-check-circle me-2"></i><?php echo $message; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        <?php if (!empty($erreur)): ?>
            <div class="alert alert-danger alert-dismissible fade show border-0 shadow-sm mb-4">
                <i class="fas fa-exclamation-triangle me-2"></i><?php echo $erreur; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <div class="form-card">
            <form method="POST" action="" id="signalementForm">
                
                <div class="mb-4">
                    <label class="form-label fw-bold small text-uppercase tracking-wider text-muted">1. Nature du problème</label>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <div class="type-card casse selected" data-type="CASSE" onclick="selectionnerType(this)">
                                <i class="fas fa-heart-broken"></i>
                                <h6 class="fw-bold mb-1">Casse / Démarque directe</h6>
                                <small class="text-muted d-block">Article abîmé, emballage détruit ou impropre à la vente</small>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="type-card perime" data-type="PERIME" onclick="selectionnerType(this)">
                                <i class="fas fa-hourglass-end"></i>
                                <h6 class="fw-bold mb-1">Date de Péremption</h6>
                                <small class="text-muted d-block">DLC/DLUO dépassée, produit retiré définitivement</small>
                            </div>
                        </div>
                    </div>
                    <input type="hidden" name="type" id="typeInput" value="CASSE">
                </div>

                <label class="form-label fw-bold small text-uppercase tracking-wider text-muted mb-2">2. Détails de l'article</label>

                <div class="mb-3">
                    <label for="id_produit" class="form-label fw-medium">Sélectionner l'article concerné</label>
                    <select class="form-select" id="id_produit" name="id_produit" required onchange="verifierStockMaximum()">
                        <option value="" data-stock="0">-- Choisir un produit de la liste --</option>
                        <?php foreach ($produits as $p): ?>
                            <option value="<?php echo $p['id_produit']; ?>" data-stock="<?php echo $p['quantite']; ?>">
                                <?php echo htmlspecialchars($p['nom_produit']); ?> — [Stock disponible actualisé : <?php echo $p['quantite']; ?>]
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="mb-3">
                    <label for="quantite" class="form-label fw-medium">Quantité défectueuse / périmée</label>
                    <input type="number" class="form-control" id="quantite" name="quantite" 
                           min="1" placeholder="Indiquer le nombre d'unités à soustraire" required oninput="verifierStockMaximum()" >
                    <div id="stockAlerte" class="form-text text-danger d-none">
                        <i class="fas fa-exclamation-circle me-1"></i> Erreur : La quantité saisie excède les stocks réels du système.
                    </div>
                </div>

                <div class="mb-4">
                    <label for="commentaire" class="form-label fw-medium">Motif ou Observations (optionnel)</label>
                    <textarea class="form-control" id="commentaire" name="commentaire" 
                              rows="3" placeholder="Exemples : Reçu brisé lors de la mise en rayon, Anomalie chaîne du froid..."></textarea>
                </div>

                <hr style="border-color: var(--border-color);" class="my-4">
                
                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-danger" id="submitBtn">
                        <i class="fas fa-check-circle me-2"></i>Enregistrer l'anomalie
                    </button>
                    <a href="<?php echo $user['role'] === 'CAISSIERE' ? 'stock_rayon.php' : '../magasinier/stock_magasin.php'; ?>" 
                       class="btn btn-secondary">Retour aux stocks</a>
                </div>
            </form>
        </div>
    </div>

    <script src="../bootstrap-5.3.8-dist/js/bootstrap.bundle.min.js"></script>
    <script src="../../../public/js/responsive.js"></script>
    <script>
        function selectionnerType(card) {
            document.querySelectorAll('.type-card').forEach(c => c.classList.remove('selected'));
            card.classList.add('selected');
            document.getElementById('typeInput').value = card.dataset.type;
        }

        function verifierStockMaximum() {
            const selectProduit = document.getElementById('id_produit');
            const inputQuantite = document.getElementById('quantite');
            const alerteDiv = document.getElementById('stockAlerte');
            const submitBtn = document.getElementById('submitBtn');

            const optionSelectionnee = selectProduit.options[selectProduit.selectedIndex];
            const stockDisponible = parseInt(optionSelectionnee.getAttribute('data-stock')) || 0;
            const quantiteSaisie = parseInt(inputQuantite.value) || 0;

            if (quantiteSaisie > stockDisponible) {
                alerteDiv.classList.remove('d-none');
                submitBtn.disabled = true;
            } else {
                alerteDiv.classList.add('d-none');
                submitBtn.disabled = false;
            }
        }

        // Gestion du thème dynamique
        document.addEventListener('DOMContentLoaded', () => {
            const currentTheme = localStorage.getItem('stockflow_theme');
            if (currentTheme === 'dark') {
                document.documentElement.setAttribute('data-theme', 'dark');
            }
        });
    </script>
</body>
</html>