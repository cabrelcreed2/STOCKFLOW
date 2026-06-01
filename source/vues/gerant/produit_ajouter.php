<?php
session_start();

if (!isset($_SESSION['utilisateur']) || $_SESSION['utilisateur']['role'] !== 'GESTIONNAIRE') {
    header('Location: ../authentification/connexion.php');
    exit;
}

require_once __DIR__ . '/../../../source/controleurs/ProduitControleur.php';

$produitCtrl = new ProduitControleur();
$categories = $produitCtrl->listeCategories();
$fournisseurs = $produitCtrl->listeFournisseursActifs();
$user = $_SESSION['utilisateur'];

$message = '';
$erreur = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $resultat = $produitCtrl->ajouterProduit(
        $_POST['code_barre'] ?? '',
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
    
    if (is_numeric($resultat)) {
        $message = 'Produit ajouté avec succès !';
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
    <title>STOCKFLOW - Ajouter produit</title>
    
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
        /* Variables de thème unifiées */
        :root {
            --primary: #f97316;
            --primary-dark: #ea580c;
            --primary-light: rgba(249, 115, 22, 0.1);
            --bg-color: #f8fafc;
            --surface-color: #ffffff;
            --surface-hover: #f1f5f9;
            --text-main: #0f172a;
            --text-muted: #64748b;
            --border-color: #e2e8f0;
            --shadow-sm: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
        }

        [data-theme="dark"] {
            --bg-color: #0b1120;
            --surface-color: #1e293b;
            --surface-hover: #334155;
            --text-main: #f8fafc;
            --text-muted: #94a3b8;
            --border-color: #334155;
            --shadow-sm: 0 1px 3px 0 rgba(0, 0, 0, 0.5);
        }

        body { 
            font-family: 'Poppins', sans-serif;
            background-color: var(--bg-color); 
            color: var(--text-main);
            transition: background-color 0.4s ease, color 0.4s ease;
        }

        /* Scrollbar */
        ::-webkit-scrollbar { width: 8px; height: 8px; }
        ::-webkit-scrollbar-track { background: var(--bg-color); }
        ::-webkit-scrollbar-thumb { background: var(--border-color); border-radius: 10px; }
        ::-webkit-scrollbar-thumb:hover { background: var(--text-muted); }

        /* Sidebar */
        .sidebar {
            background: var(--surface-color) !important; 
            min-height: 100vh; position: fixed; top: 0; left: 0; bottom: 0; width: 280px; overflow-y: auto;
            border-right: 1px solid var(--border-color); z-index: 1000;
            transition: all 0.4s ease;
        }
        .sidebar .logo { text-align: center; padding: 25px 0; border-bottom: 1px solid var(--border-color); }
        .sidebar .logo h4 { font-weight: 800; color: var(--primary); }
        .sidebar .logo i { color: var(--primary); background: var(--primary-light); padding: 12px; border-radius: 15px; }
        .sidebar .logo p { color: var(--text-muted); margin-bottom: 0; margin-top: 10px; font-weight: 500; }
        .sidebar .nav-link {
            color: var(--text-muted) !important; padding: 12px 25px; margin: 5px 15px;
            border-radius: 10px; background: transparent; font-weight: 500; transition: all 0.3s ease;
        }
        .sidebar .nav-link:hover { background: var(--primary-light) !important; color: var(--primary) !important; transform: translateX(5px); }
        .sidebar .nav-link.active { background: var(--primary) !important; color: white !important; box-shadow: 0 4px 10px rgba(249, 115, 22, 0.3); }
        .sidebar .nav-link i { width: 28px; margin-right: 12px; color: inherit; }

        /* Layout Principal */
        .main-content { margin-left: 280px; padding: 20px; transition: margin-left 0.3s ease; }
        
        .top-bar, .form-card { 
            background: var(--surface-color); 
            border-radius: 15px; 
            border: 1px solid var(--border-color);
            box-shadow: var(--shadow-sm);
            transition: all 0.4s ease;
        }
        .top-bar { padding: 15px 20px; margin-bottom: 25px; }
        .form-card { padding: 30px; }

        /* Formulaires */
        .form-control, .form-select, .btn-light {
            background-color: var(--bg-color) !important;
            color: var(--text-main) !important;
            border-color: var(--border-color) !important;
            transition: all 0.3s ease;
        }
        .form-control:focus, .form-select:focus {
            border-color: var(--primary) !important;
            box-shadow: 0 0 0 0.25rem rgba(249, 115, 22, 0.25) !important;
        }
        .form-label { font-weight: 500; color: var(--text-main); }
        
        /* Bouton Thème */
        .theme-toggle-btn {
            background: var(--primary-light); border: none; color: var(--primary);
            width: 40px; height: 40px; border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            transition: all 0.3s ease; cursor: pointer;
        }
        .theme-toggle-btn:hover { background: var(--primary); color: white; transform: scale(1.05); }
        .theme-toggle-btn i { transition: transform 0.5s cubic-bezier(0.4, 0, 0.2, 1); }
        .theme-toggle-btn.rotating i { transform: rotate(360deg) scale(0.8); }

        /* Dropdown */
        .dropdown-menu { background-color: var(--surface-color); border-color: var(--border-color); }
        .dropdown-item { color: var(--text-main); font-weight: 500; }
        .dropdown-item:hover { background-color: var(--surface-hover); color: var(--text-main); }
        .dropdown-divider { border-top-color: var(--border-color); }

        @media (max-width: 768px) { .sidebar { width: 70px; } .sidebar .nav-link span, .sidebar .logo p, .sidebar .logo h4 { display: none; } .main-content { margin-left: 70px; } }
    </style>
</head>
<body>

    <div class="sidebar">
        <div class="logo">
            <i class="fas fa-chart-line fa-2x"></i>
            <h4>STOCK<span>FLOW</span></h4>
            <p>Gérant</p>
        </div>
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
        <div class="top-bar d-flex justify-content-between align-items-center">
            <h4 class="mb-0"><i class="fas fa-plus-circle me-2" style="color: var(--primary);"></i>Ajouter un produit</h4>
            
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
                        <li><a class="dropdown-item" href="profil_gerant.php"><i class="fas fa-user me-2"></i>Mon profil</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item text-danger" href="../../../deconnexion.php"><i class="fas fa-sign-out-alt me-2"></i>Déconnexion</a></li>
                    </ul>
                </div>
            </div>
        </div>

        <?php if (!empty($message)): ?><div class="alert alert-success shadow-sm border-0"><i class="fas fa-check-circle me-2"></i><?php echo $message; ?></div><?php endif; ?>
        <?php if (!empty($erreur)): ?><div class="alert alert-danger shadow-sm border-0"><i class="fas fa-exclamation-circle me-2"></i><?php echo $erreur; ?></div><?php endif; ?>

        <div class="form-card">
            <form method="POST">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Code-barres</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light"><i class="fas fa-barcode"></i></span>
                            <input type="text" name="code_barre" class="form-control" required placeholder="Ex: 3700012345678">
                        </div>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Nom du produit</label>
                        <input type="text" name="nom" class="form-control" required>
                    </div>
                    <div class="col-md-12 mb-3">
                        <label class="form-label">Description</label>
                        <textarea name="description" class="form-control" rows="2"></textarea>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Catégorie</label>
                        <select name="id_categorie" class="form-select" required>
                            <option value="">-- Choisir une catégorie --</option>
                            <?php foreach ($categories as $c): ?>
                                <option value="<?php echo $c['id_categorie']; ?>"><?php echo htmlspecialchars($c['nom']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Fournisseur</label>
                        <select name="id_fournisseur" class="form-select" required>
                            <option value="">-- Choisir un fournisseur --</option>
                            <?php foreach ($fournisseurs as $f): ?>
                                <option value="<?php echo $f['id_fournisseur']; ?>"><?php echo htmlspecialchars($f['nom']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Prix d'achat (FCFA)</label>
                        <input type="number" name="prix_achat" class="form-control" required min="0" step="1">
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Prix de vente (FCFA)</label>
                        <input type="number" name="prix_vente" class="form-control" required min="0" step="1">
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Jours de péremption par défaut</label>
                        <div class="input-group">
                            <input type="number" name="peremption_defaut" class="form-control" value="30" min="0">
                            <span class="input-group-text bg-light">jours</span>
                        </div>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Seuil d'alerte magasin</label>
                        <input type="number" name="seuil_magasin" class="form-control" value="10" min="0">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Seuil d'alerte rayon</label>
                        <input type="number" name="seuil_rayon" class="form-control" value="10" min="0">
                    </div>
                </div>
                <hr class="my-4" style="border-color: var(--border-color);">
                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary" style="background-color: var(--primary); border-color: var(--primary);"><i class="fas fa-save me-2"></i> Enregistrer le produit</button>
                    <a href="produits.php" class="btn btn-light">Annuler</a>
                </div>
            </form>
        </div>
    </div>

    <script src="../bootstrap-5.3.8-dist/js/bootstrap.bundle.min.js"></script>
    <script src="../../../public/js/responsive.js"></script>
    <script>
        // Script du thème
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
            if (savedTheme) applyTheme(savedTheme);
            else if (window.matchMedia('(prefers-color-scheme: dark)').matches) applyTheme('dark');

            themeToggleBtn.addEventListener('click', () => {
                const isDark = document.documentElement.getAttribute('data-theme') === 'dark';
                themeToggleBtn.classList.add('rotating');
                setTimeout(() => themeToggleBtn.classList.remove('rotating'), 500);
                applyTheme(isDark ? 'light' : 'dark');
            });
        });
    </script>
</body>
</html>