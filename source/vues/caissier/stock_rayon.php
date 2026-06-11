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
require_once __DIR__ . '/../../../source/modeles/StockRayon.php';

// Connexion
$db = new Database();
$conn = $db->connect();
$user = $_SESSION['utilisateur'];

// Récupérer le rayon de la caissière
$sql = "SELECT * FROM rayon WHERE id_caissiere = :id";
$stmt = $conn->prepare($sql);
$stmt->execute(['id' => $user['id_utilisateur']]);
$rayon = $stmt->fetch();

// Récupérer les produits du rayon
$produits = [];
if ($rayon) {
    $stockRayonModel = new StockRayon($conn);
    $produits = $stockRayonModel->getByRayon($rayon['id_rayon']);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>STOCKFLOW - Stock rayon</title>
    
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
            
            /* Badges Stock (Clair) */
            --badge-ok-bg: #d4edda;
            --badge-ok-text: #155724;
            --badge-bas-bg: #fff3cd;
            --badge-bas-text: #856404;
            --badge-critique-bg: #f8d7da;
            --badge-critique-text: #721c24;
        }

        [data-theme="dark"] {
            /* Thème Sombre */
            --bg-color: #0f172a;
            --surface-color: #1e293b;
            --text-main: #f8fafc;
            --text-muted: #94a3b8;
            --border-color: rgba(255, 255, 255, 0.08);
            --notif-bg: rgba(255, 255, 255, 0.03);
            
            /* Badges Stock (Sombre) */
            --badge-ok-bg: rgba(34, 197, 94, 0.15);
            --badge-ok-text: #86efac;
            --badge-bas-bg: rgba(234, 179, 8, 0.15);
            --badge-bas-text: #fef08a;
            --badge-critique-bg: rgba(239, 68, 68, 0.15);
            --badge-critique-text: #fca5a5;
        }

        * { font-family: 'Poppins', sans-serif; }
        
        body { 
            background-color: var(--bg-color); 
            color: var(--text-main);
            transition: background-color 0.3s ease, color 0.3s ease;
        }
        
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
            transition: background-color 0.3s ease, border-color 0.3s ease;
        }

        .content-card { 
            background: var(--surface-color); 
            border-radius: 15px; 
            padding: 20px; 
            box-shadow: 0 2px 10px rgba(0,0,0,0.02); 
            border: 1px solid var(--border-color);
        }
        .rayon-header { 
            background-color: var(--surface-color); 
            border-radius: 15px; 
            padding: 20px; 
            color: var(--text-main); 
            margin-bottom: 20px; 
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

        /* Badges d'état adaptatifs */
        .stock-ok { background: var(--badge-ok-bg); color: var(--badge-ok-text); padding: 5px 12px; border-radius: 20px; font-size: 12px; display: inline-block; font-weight: 500; }
        .stock-bas { background: var(--badge-bas-bg); color: var(--badge-bas-text); padding: 5px 12px; border-radius: 20px; font-size: 12px; display: inline-block; font-weight: 500; }
        .stock-critique { background: var(--badge-critique-bg); color: var(--badge-critique-text); padding: 5px 12px; border-radius: 20px; font-size: 12px; display: inline-block; font-weight: 500; }
        
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

        /* Éléments Bootstrap Dropdown */
        .btn-light {
            background-color: var(--bg-color);
            border-color: var(--border-color);
            color: var(--text-main);
        }
        .btn-light:hover, .btn-light:focus {
            background-color: var(--border-color);
            color: var(--text-main);
        }
        .dropdown-menu {
            background-color: var(--surface-color);
            border-color: var(--border-color);
        }
        .dropdown-item { color: var(--text-main); }
        .dropdown-item:hover { background-color: var(--bg-color); color: var(--text-main); }
        .dropdown-divider { border-top-color: var(--border-color); }

        /* --- FIX POUR LA LISIBILITÉ EN MODE SOMBRE --- */

        /* Fix des placeholders */
        input::placeholder, 
        textarea::placeholder {
            color: var(--text-muted) !important;
            opacity: 0.7 !important;
        }

        /* Fix global des champs de formulaire */
        .form-control, .form-select {
            background-color: var(--surface-color) !important;
            color: var(--text-main) !important;
            border-color: var(--border-color) !important;
        }

        /* Fix au focus (lorsqu'on clique dans un champ) */
        .form-control:focus, .form-select:focus {
            background-color: var(--surface-color) !important;
            color: var(--text-main) !important;
            border-color: var(--primary) !important;
        }

        /* Fix spécifique pour les labels ou textes grisés */
        .text-muted {
            color: var(--text-muted) !important;
        }

        /* Amélioration de la lisibilité de la sélection de texte */
        ::selection {
            background: var(--primary);
            color: white;
        }

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
            <a class="nav-link active" href="stock_rayon.php"><i class="fas fa-store"></i> <span>Stock rayon</span></a>
            <!-- <a class="nav-link" href="inventaire_rayon_importer.php"><i class="fas fa-upload"></i> <span>Importer inventaire rayon</span></a> -->
            <a class="nav-link" href="caisse.php"><i class="fas fa-cash-register"></i> <span>Caisse (vente)</span></a>
            <a class="nav-link" href="ventes_historique.php"><i class="fas fa-history"></i> <span>Historique ventes</span></a>
            <a class="nav-link" href="casse_perime_signaler.php"><i class="fas fa-trash-alt"></i> <span>Signaler casse/périmé</span></a>
            <a class="nav-link" href="notifications.php"><i class="fas fa-bell"></i> <span>Notifications</span></a>
            <a class="nav-link" href="profil.php"><i class="fas fa-user"></i> <span>Mon profil</span></a>
            <a class="nav-link" href="../../../deconnexion.php"><i class="fas fa-sign-out-alt"></i> <span>Déconnexion</span></a>
        </nav>
    </div>

    <div class="main-content">
        
        <div class="top-bar d-flex justify-content-between align-items-center">
            <h4 class="mb-0"><i class="fas fa-store me-2" style="color: var(--primary);"></i>Stock rayon</h4>
            
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
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item text-danger" href="../../../deconnexion.php"><i class="fas fa-sign-out-alt me-2"></i>Déconnexion</a></li>
                    </ul>
                </div>
            </div>
        </div>
        
        <div class="rayon-header shadow-sm">
            <h5 class="mb-1"><i class="fas fa-map-marker-alt me-2" style="color: var(--primary);"></i>
                <?php echo $rayon ? htmlspecialchars($rayon['nom']) : 'Rayon non assigné'; ?>
            </h5>
            <p class="mb-0 text-muted">
                <?php echo $rayon ? htmlspecialchars($rayon['emplacement'] ?? '') : 'Contactez l\'administrateur'; ?>
            </p>
        </div>

        <div class="content-card">
            <div class="d-flex flex-column flex-md-row justify-content-between gap-3 mb-4">
                <input type="text" class="form-control w-100 w-md-25" id="recherche" placeholder="🔍 Rechercher un produit..." onkeyup="filtrerProduits()">
                <div>
                    <!-- <a href="inventaire_rayon_importer.php" class="btn btn-sm btn-outline-success me-1">
                        <i class="fas fa-upload"></i> Importer
                    </a> -->
                    <a href="caisse.php" class="btn btn-sm btn-outline-primary">
                        <i class="fas fa-cash-register"></i> Vendre
                    </a>
                </div>
            </div>
            
            <div class="table-responsive">
                <table class="table table-hover align-middle" id="tableStock">
                    <thead>
                        <tr>
                            <th>Code-barres</th>
                            <th>Produit</th>
                            <th>Prix</th>
                            <th>Stock</th>
                            <th>Seuil</th>
                            <th>État</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($produits)): ?>
                            <?php foreach ($produits as $p): 
                                $etat = 'ok';
                                if ($p['quantite'] == 0) $etat = 'critique';
                                elseif ($p['quantite'] <= $p['seuil_alerte_rayon']) $etat = 'bas';
                            ?>
                                <tr>
                                    <td><code><?php echo htmlspecialchars($p['code_barre']); ?></code></td>
                                    <td><strong><?php echo htmlspecialchars($p['nom_produit']); ?></strong></td>
                                    <td><?php echo number_format($p['prix_vente'], 0, ',', ' '); ?> FCFA</td>
                                    <td class="<?php echo $etat === 'critique' ? 'text-danger fw-bold' : ($etat === 'bas' ? 'text-warning fw-bold' : ''); ?>">
                                        <?php echo $p['quantite']; ?>
                                    </td>
                                    <td><?php echo $p['seuil_alerte_rayon']; ?></td>
                                    <td>
                                        <?php if ($etat === 'ok'): ?>
                                            <span class="stock-ok">🟢 Stock OK</span>
                                        <?php elseif ($etat === 'bas'): ?>
                                            <span class="stock-bas">🟠 Stock bas</span>
                                        <?php else: ?>
                                            <span class="stock-critique">🔴 Rupture</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr id="empty-row">
                                <td colspan="6" class="text-center py-5 text-muted">
                                    <i class="fas fa-inbox fa-3x mb-3 d-block text-muted" style="opacity: 0.5;"></i>
                                    <p class="mb-3">Aucun produit disponible dans ce rayon.</p>
                                    <a href="inventaire_rayon_importer.php" class="btn btn-sm btn-primary">Importer l'inventaire</a>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script src="../bootstrap-5.3.8-dist/js/bootstrap.bundle.min.js"></script>
    <script src="../../../public/js/responsive.js"></script>
    <script>
        // Fonction de filtrage dynamique adaptative
        function filtrerProduits() {
            let recherche = document.getElementById('recherche').value.toLowerCase();
            let lignes = document.querySelectorAll('#tableStock tbody tr');
            
            lignes.forEach(ligne => {
                if (ligne.id === 'empty-row') return; // Ne pas filtrer la ligne d'état vide
                let texte = ligne.textContent.toLowerCase();
                ligne.style.display = texte.includes(recherche) ? '' : 'none';
            });
        }

        // Configuration et synchronisation du sélecteur de thème
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
            
            if (savedTheme) {
                applyTheme(savedTheme);
            } else if (systemPrefersDark) {
                applyTheme('dark');
            }

            themeToggleBtn.addEventListener('click', () => {
                const isDark = document.documentElement.getAttribute('data-theme') === 'dark';
                applyTheme(isDark ? 'light' : 'dark');
            });
        });
    </script>
</body>
</html>