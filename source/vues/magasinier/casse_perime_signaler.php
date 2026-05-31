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

// Inclure les contrôleurs et modèles
require_once __DIR__ . '/../../controleurs/CassePerimeControleur.php';
require_once __DIR__ . '/../../modeles/BaseDeDonnees.php';
require_once __DIR__ . '/../../modeles/StockMagasin.php';

// Connexion
$db = new Database();
$conn = $db->connect();
$user = $_SESSION['utilisateur'];

// Récupérer les produits du stock magasin
$stockMagasinModel = new StockMagasin($conn);
$produits = $stockMagasinModel->getAll();

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
        $ctrl = new CassePerimeControleur($conn);
        $resultat = $ctrl->signalerMagasin($id_produit, $type, $quantite, $commentaire, $user['id_utilisateur']);
        
        if ($resultat === true) {
            $message = 'Signalement enregistré avec succès.';
            $produits = $stockMagasinModel->getAll();
        } else {
            $erreur = $resultat;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>STOCKFLOW - Signaler casse/périmé</title>
    
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
            
            /* Inputs et Formulaires */
            --input-bg: #ffffff;
            --input-text: #1e293b;
            --input-border: #ced4da;

            /* Type Cards */
            --card-border: #e2e8f0;
            --card-hover-bg: #fff7ed;
            --card-casse-icon: #dc2626;

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

            /* Inputs et Formulaires */
            --input-bg: #0f172a;
            --input-text: #f8fafc;
            --input-border: #334155;

            /* Type Cards */
            --card-border: #334155;
            --card-hover-bg: rgba(249, 115, 22, 0.1);
            --card-casse-icon: #ef4444;

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
        
        /* Form Card */
        .form-card { 
            background: var(--bg-surface); 
            border-radius: 15px; padding: 30px; 
            box-shadow: 0 2px 10px rgba(0,0,0,0.05); 
            transition: background-color 0.3s ease;
        }
        
        /* Type Cards (Casse / Périmé) */
        .type-card { 
            border: 2px solid var(--card-border); 
            border-radius: 15px; padding: 20px; 
            text-align: center; cursor: pointer; 
            transition: all 0.3s; 
            background: var(--bg-surface);
        }
        .type-card.selected { border-color: var(--primary); background: var(--card-hover-bg); }
        .type-card i { font-size: 40px; margin-bottom: 10px; transition: color 0.3s; }
        .type-card.casse i { color: var(--card-casse-icon); }
        .type-card.perime i { color: var(--primary); }
        
        /* Inputs & Form Controls */
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

        /* Alerts */
        .alert-success { background-color: var(--alert-success-bg); color: var(--alert-success-text); border-color: var(--border-color); }
        .alert-danger { background-color: var(--alert-danger-bg); color: var(--alert-danger-text); border-color: var(--border-color); }

        /* Theme Toggle Button */
        .theme-toggle-btn {
            background: transparent; border: none; color: var(--text-muted); font-size: 1.5rem; cursor: pointer; transition: color 0.3s ease;
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
            <a class="nav-link" href="transfert_effectuer.php"><i class="fas fa-exchange-alt"></i> <span>Effectuer un transfert</span></a>
            <a class="nav-link " href="transferts_historique.php"><i class="fas fa-history"></i> <span>Historique transferts</span></a>
            <a class="nav-link active" href="casse_perime_signaler.php"><i class="fas fa-trash-alt"></i> <span>Signaler casse/périmé</span></a>
            <a class="nav-link" href="alertes_magasinier.php"><i class="fas fa-bell"></i> <span>Alertes</span></a>
            <a class="nav-link" href="profil_magasinier.php"><i class="fas fa-user"></i> <span>Mon profil</span></a>
            <a class="nav-link" href="../../../deconnexion.php"><i class="fas fa-sign-out-alt"></i> <span>Déconnexion</span></a>
        </nav>
    </div>

    <div class="main-content">
        
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h3 class="m-0"><i class="fas fa-warehouse me-2" style="color: var(--primary);"></i>Stock Magasin - Signaler casse/périmé</h3>
            <button id="theme-toggle" class="theme-toggle-btn" title="Basculer le thème">
                <i id="theme-icon" class="fas fa-moon"></i>
            </button>
        </div>

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

        <div class="form-card">
            <form method="POST" action="">
                
                <div class="mb-4">
                    <label class="form-label fw-semibold">Type de signalement</label>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="type-card casse selected" data-type="CASSE" onclick="selectionnerType(this)">
                                <i class="fas fa-broken"></i>
                                <h6>Casse</h6>
                                <small class="text-muted">Produit cassé ou endommagé</small>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="type-card perime" data-type="PERIME" onclick="selectionnerType(this)">
                                <i class="fas fa-calendar-times"></i>
                                <h6>Périmé</h6>
                                <small class="text-muted">Date de péremption dépassée</small>
                            </div>
                        </div>
                    </div>
                    <input type="hidden" name="type" id="typeInput" value="CASSE">
                </div>

                <div class="mb-3">
                    <label for="id_produit" class="form-label fw-semibold">Produit</label>
                    <select class="form-select" id="id_produit" name="id_produit" required>
                        <option value="">-- Sélectionner un produit --</option>
                        <?php foreach ($produits as $p): ?>
                            <option value="<?php echo $p['id_produit']; ?>">
                                <?php echo htmlspecialchars($p['nom_produit']); ?> 
                                (Stock: <?php echo $p['quantite']; ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="mb-3">
                    <label for="quantite" class="form-label fw-semibold">Quantité</label>
                    <input type="number" class="form-control" id="quantite" name="quantite" 
                           min="1" placeholder="Nombre d'unités" required>
                </div>

                <div class="mb-3">
                    <label for="commentaire" class="form-label fw-semibold">Commentaire (optionnel)</label>
                    <textarea class="form-control" id="commentaire" name="commentaire" 
                              rows="2" placeholder="Description du problème..."></textarea>
                </div>

                <hr style="border-color: var(--border-color);">
                <button type="submit" class="btn btn-danger">
                    <i class="fas fa-flag-checkered me-1"></i> Signaler
                </button>
                <a href="stock_magasinier.php" class="btn btn-secondary">Annuler</a>
            </form>
        </div>
    </div>

    <script src="../bootstrap-5.3.8-dist/js/bootstrap.bundle.min.js"></script>
    <script src="../../../public/js/responsive.js"></script>
    <script>
        // Logique de sélection du type
        function selectionnerType(card) {
            document.querySelectorAll('.type-card').forEach(c => c.classList.remove('selected'));
            card.classList.add('selected');
            document.getElementById('typeInput').value = card.dataset.type;
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