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

// Inclure les contrôleurs
require_once __DIR__ . '/../../../source/controleurs/TransfertControleur.php';
require_once __DIR__ . '/../../../source/modeles/BaseDeDonnees.php';
require_once __DIR__ . '/../../../source/modeles/StockMagasin.php';
require_once __DIR__ . '/../../../source/modeles/Rayon.php';

// Connexion
$db = new Database();
$conn = $db->connect();
$user = $_SESSION['utilisateur'];

// Récupérer les rayons et le stock magasin
$rayonModel = new Rayon($conn);
$rayons = $rayonModel->getAll();

$stockMagasinModel = new StockMagasin($conn);
$produits = $stockMagasinModel->getAll();

// Traitement du formulaire
$message = '';
$erreur = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_rayon = $_POST['id_rayon'] ?? 0;
    $transfers = $_POST['transfert'] ?? [];
    
    if (empty($id_rayon)) {
        $erreur = 'Veuillez sélectionner un rayon destinataire.';
    } elseif (empty($transfers)) {
        $erreur = 'Veuillez sélectionner au moins un produit à transférer.';
    } else {
        $transfertCtrl = new TransfertControleur();
        $nbTransferts = 0;
        $erreursTransfert = [];
        
        foreach ($transfers as $id_produit => $quantite) {
            if ($quantite > 0) {
                $resultat = $transfertCtrl->transferer(
                    $id_produit,
                    $id_rayon,
                    $quantite,
                    null,
                    $user['id_utilisateur']
                );
                
                if (is_array($resultat) && isset($resultat['succes'])) {
                    $nbTransferts++;
                } else {
                    $erreursTransfert[] = "Produit ID $id_produit : $resultat";
                }
            }
        }
        
        if ($nbTransferts > 0) {
            $message = "$nbTransferts produit(s) transféré(s) avec succès.";
            // Rafraîchir le stock
            $produits = $stockMagasinModel->getAll();
        }
        if (!empty($erreursTransfert)) {
            $erreur = implode('<br>', $erreursTransfert);
        }
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>STOCKFLOW - Effectuer transfert</title>
    
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
            
            /* Formulaires */
            --input-bg: #ffffff;
            --input-text: #1e293b;
            --input-border: #ced4da;
            --input-disabled-bg: #e9ecef;

            /* Tableau */
            --table-color: #1e293b;
            --table-border: #e2e8f0;
            --table-hover-bg: rgba(0,0,0,0.05);

            /* États des stocks */
            --stock-bas-color: #856404;
            --stock-ok-color: #155724;

            /* Alertes */
            --alert-success-bg: #d1e7dd; --alert-success-text: #0f5132;
            --alert-danger-bg: #f8d7da; --alert-danger-text: #842029;
        }

        /* --- VARIABLES THÈME SOMBRE --- */
        [data-theme="dark"] {
            --bg-body: #0f172a;
            --bg-surface: #1e293b;
            --text-main: #f8fafc;
            --text-muted: #94a3b8;
            --border-color: rgba(255,255,255,0.1);

            /* Formulaires */
            --input-bg: #0f172a;
            --input-text: #f8fafc;
            --input-border: #334155;
            --input-disabled-bg: #1e293b;

            /* Tableau */
            --table-color: #f8fafc;
            --table-border: #334155;
            --table-hover-bg: rgba(255,255,255,0.05);

            /* États des stocks (plus clairs pour le mode sombre) */
            --stock-bas-color: #ffd966;
            --stock-ok-color: #75b798;

            /* Alertes */
            --alert-success-bg: rgba(15, 81, 50, 0.4); --alert-success-text: #75b798;
            --alert-danger-bg: rgba(132, 32, 41, 0.4); --alert-danger-text: #ea868f;
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
        
        /* Cards */
        .form-card { 
            background: var(--bg-surface); 
            border-radius: 15px; padding: 30px; margin-bottom: 20px; 
            box-shadow: 0 2px 10px rgba(0,0,0,0.05); 
            transition: background-color 0.3s ease;
        }
        
        /* Inputs & Selects */
        .form-control, .form-select { 
            background-color: var(--input-bg); 
            color: var(--input-text); 
            border-color: var(--input-border); 
        }
        .form-control:focus, .form-select:focus { 
            background-color: var(--input-bg); 
            color: var(--input-text); 
            border-color: var(--primary); 
            box-shadow: 0 0 0 0.25rem rgba(249, 115, 22, 0.25); 
        }
        .form-control:disabled, .form-control[readonly] {
            background-color: var(--input-disabled-bg);
            border-color: var(--input-border);
            opacity: 0.7;
        }

        /* Table */
        .table { color: var(--table-color); border-color: var(--table-border); transition: color 0.3s ease; }
        .table td, .table th { border-bottom-color: var(--table-border); vertical-align: middle; }
        .table-hover tbody tr:hover { color: var(--table-color); background-color: var(--table-hover-bg); }
        .table-responsive { transition: background-color 0.3s ease; }

        /* États et textes */
        .stock-bas { color: var(--stock-bas-color) !important; transition: color 0.3s ease; }
        .stock-ok { color: var(--stock-ok-color) !important; transition: color 0.3s ease; }
        .text-muted { color: var(--text-muted) !important; }

        /* Alerts */
        .alert-success { background-color: var(--alert-success-bg); color: var(--alert-success-text); border-color: var(--border-color); }
        .alert-danger { background-color: var(--alert-danger-bg); color: var(--alert-danger-text); border-color: var(--border-color); }

        /* Theme Toggle */
        .theme-toggle-btn {
            background: transparent; border: none; color: var(--text-muted); font-size: 1.5rem; cursor: pointer; transition: color 0.3s ease; margin-right: 15px;
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
            <a class="nav-link" href="stock_magasinier.php"><i class="fas fa-warehouse"></i> <span>Stock magasin</span></a>
            <a class="nav-link" href="inventaire_importer.php"><i class="fas fa-upload"></i> <span>Importer inventaire</span></a>
            <a class="nav-link active" href="transfert_effectuer.php"><i class="fas fa-exchange-alt"></i> <span>Effectuer un transfert</span></a>
            <a class="nav-link " href="transferts_historique.php"><i class="fas fa-history"></i> <span>Historique transferts</span></a>
            <a class="nav-link" href="casse_perime_signaler.php"><i class="fas fa-trash-alt"></i> <span>Signaler casse/périmé</span></a>
            <a class="nav-link" href="alertes_magasinier.php"><i class="fas fa-bell"></i> <span>Alertes</span></a>
            <a class="nav-link" href="profil_magasinier.php"><i class="fas fa-user"></i> <span>Mon profil</span></a>
            <a class="nav-link" href="../../../deconnexion.php"><i class="fas fa-sign-out-alt"></i> <span>Déconnexion</span></a>
        </nav>
    </div>

    <div class="main-content">
        
        <?php if (!empty($message)): ?>
            <div class="alert alert-success alert-dismissible fade show">
                <i class="fas fa-check-circle me-2"></i><?php echo $message; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        <?php if (!empty($erreur)): ?>
            <div class="alert alert-danger alert-dismissible fade show">
                <i class="fas fa-exclamation-triangle me-2"></i><?php echo $erreur; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <div class="d-flex justify-content-between align-items-center mb-4">
            <h3 class="m-0"><i class="fas fa-exchange-alt me-2" style="color: var(--primary);"></i>Effectuer un transfert</h3>
            <div class="d-flex align-items-center">
                <button id="theme-toggle" class="theme-toggle-btn" title="Basculer le thème">
                    <i id="theme-icon" class="fas fa-moon"></i>
                </button>
                <a href="stock_magasinier.php" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Retour</a>
            </div>
        </div>

        <form method="POST" action="">
            <div class="form-card">
                <div class="mb-3">
                    <label class="form-label fw-semibold">Rayon destinataire</label>
                    <select class="form-select" name="id_rayon" required>
                        <option value="">-- Sélectionner un rayon --</option>
                        <?php foreach ($rayons as $r): ?>
                            <option value="<?php echo $r['id_rayon']; ?>">
                                <?php echo htmlspecialchars($r['nom']); ?>
                                <?php echo $r['nom_caissiere'] ? ' (Caissière : ' . htmlspecialchars($r['prenom_caissiere'] . ' ' . $r['nom_caissiere']) . ')' : ' (non assigné)'; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="form-card">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Sélection</th>
                                <th>Produit</th>
                                <th>Stock magasin</th>
                                <th>Seuil</th>
                                <th>Quantité à transférer</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($produits)): ?>
                                <?php foreach ($produits as $p): 
                                    $stockClass = $p['quantite'] <= $p['seuil_alerte_magasin'] ? 'stock-bas fw-bold' : 'stock-ok';
                                ?>
                                    <tr>
                                        <td>
                                            <input class="form-check-input product-checkbox" type="checkbox" onchange="toggleQuantite(this)">
                                        </td>
                                        <td>
                                            <strong><?php echo htmlspecialchars($p['nom_produit']); ?></strong>
                                            <br><small class="text-muted"><?php echo htmlspecialchars($p['code_barre']); ?></small>
                                        </td>
                                        <td class="<?php echo $stockClass; ?>"><?php echo $p['quantite']; ?></td>
                                        <td><?php echo $p['seuil_alerte_magasin']; ?></td>
                                        <td>
                                            <input type="number" class="form-control w-75" 
                                                   name="transfert[<?php echo $p['id_produit']; ?>]" 
                                                   disabled min="1" max="<?php echo $p['quantite']; ?>"
                                                   placeholder="Qté">
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="5" class="text-center py-4 text-muted">
                                        <i class="fas fa-inbox fa-2x mb-2"></i>
                                        <p>Aucun produit en stock magasin.</p>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                
                <?php if (!empty($produits)): ?>
                    <hr class="my-4" style="border-color: var(--border-color);">
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-check-circle me-1"></i> Valider le transfert
                    </button>
                <?php endif; ?>
            </div>
        </form>
    </div>

    <script src="../bootstrap-5.3.8-dist/js/bootstrap.bundle.min.js"></script>
    <script src="../../../public/js/responsive.js"></script>
    <script>
        // Logique de basculement du thème global
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

            // Ajustement immédiat de l'icône
            updateIcon(document.documentElement.getAttribute('data-theme'));

            themeToggleBtn.addEventListener('click', () => {
                let currentTheme = document.documentElement.getAttribute('data-theme');
                let newTheme = currentTheme === 'dark' ? 'light' : 'dark';
                
                document.documentElement.setAttribute('data-theme', newTheme);
                localStorage.setItem('theme', newTheme);
                updateIcon(newTheme);
            });
        });

        // Activer/Désactiver l'input quantité
        function toggleQuantite(checkbox) {
            const input = checkbox.closest('tr').querySelector('input[type="number"]');
            if (input) {
                input.disabled = !checkbox.checked;
                if (!checkbox.checked) input.value = '';
                // Focus automatique si coché
                if (checkbox.checked) input.focus();
            }
        }
    </script>
</body>
</html>