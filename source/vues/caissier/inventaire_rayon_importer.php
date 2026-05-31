<?php
session_start();

// Vérification de la session et du rôle CAISSIERE
if (!isset($_SESSION['utilisateur']) || $_SESSION['utilisateur']['role'] !== 'CAISSIERE') {
    header('Location: ../authentification/connexion.php');
    exit;
}

require_once __DIR__ . '/../../../source/controleurs/StockControleur.php';
require_once __DIR__ . '/../../../source/modeles/BaseDeDonnees.php';
require_once __DIR__ . '/../../../source/modeles/StockRayon.php';
require_once __DIR__ . '/../../../source/modeles/Produit.php';
require_once __DIR__ . '/../../../source/modeles/Categorie.php';
require_once __DIR__ . '/../../../source/modeles/Fournisseur.php';

$db = new Database();
$conn = $db->connect();
$user = $_SESSION['utilisateur'];

// Récupérer le rayon de la caissière
$sql = "SELECT * FROM rayon WHERE id_caissiere = :id";
$stmt = $conn->prepare($sql);
$stmt->execute(['id' => $user['id_utilisateur']]);
$rayon = $stmt->fetch();

$produitModel = new Produit($conn);
$categorieModel = new Categorie($conn);
$fournisseurModel = new Fournisseur($conn);
$stockRayonModel = new StockRayon($conn);

$message = '';
$erreur = '';
$details = [];
$codes_deja_vus = []; 

// ============================================================
// FONCTION DE VALIDATION DE DATE
// ============================================================
function validerDate($date_str) {
    $timestamp = strtotime($date_str);
    if ($timestamp && preg_match('/^\d{4}-\d{2}-\d{2}$/', $date_str)) {
        return date('Y-m-d', $timestamp);
    }
    
    if (preg_match('/^(\d{2})\/(\d{2})\/(\d{4})$/', $date_str, $m)) {
        $jour = $m[1]; $mois = $m[2]; $annee = $m[3];
        if (checkdate($mois, $jour, $annee)) {
            return date('Y-m-d', mktime(0, 0, 0, $mois, $jour, $annee));
        }
    }
    
    if (preg_match('/^(\d{2})-(\d{2})-(\d{4})$/', $date_str, $m)) {
        $jour = $m[1]; $mois = $m[2]; $annee = $m[3];
        if (checkdate($mois, $jour, $annee)) {
            return date('Y-m-d', mktime(0, 0, 0, $mois, $jour, $annee));
        }
    }
    
    return false;
}

// ============================================================
// FONCTION DE TRAITEMENT D'UNE LIGNE
// ============================================================
function traiterLigne($ligne, $ligne_numero, $id_rayon, &$nb_importes, &$nb_erreurs, &$codes_deja_vus) {
    global $conn, $produitModel, $categorieModel, $fournisseurModel, $stockRayonModel, $details;
    
    if (!$ligne || !array_filter($ligne)) return;

    if (count($ligne) < 3) {
        $details[] = ["ligne" => $ligne_numero, "status" => "Erreur", "msg" => "Colonnes insuffisantes", "action" => "Rejeté"];
        $nb_erreurs++;
        return;
    }
    
    $code_barre = trim($ligne[0] ?? '');
    $nom = trim($ligne[1] ?? '');
    $quantite_raw = trim($ligne[2] ?? '0');
    $prix_vente_raw = trim($ligne[3] ?? '0');
    $date_peremption = trim($ligne[4] ?? '');
    $categorie_nom = trim($ligne[5] ?? '');
    $fournisseur_nom = trim($ligne[6] ?? '');

    if (empty($code_barre) || empty($nom)) {
        $details[] = ["ligne" => $ligne_numero, "status" => "Erreur", "msg" => "Code-barre ou nom vide", "action" => "Rejeté"];
        $nb_erreurs++;
        return;
    }

    if (in_array($code_barre, $codes_deja_vus)) {
        $details[] = ["ligne" => $ligne_numero, "status" => "Doublon", "msg" => "Code [$code_barre] déjà présent dans ce fichier", "action" => "Ignoré"];
        $nb_erreurs++;
        return;
    }
    $codes_deja_vus[] = $code_barre;

    $prix_vente = floatval(str_replace(',', '.', $prix_vente_raw));
    
    // Nettoyage pour la validation numérique
    $qte_check = str_replace(',', '.', $quantite_raw);
    if (!is_numeric($qte_check) || intval($qte_check) <= 0) {
        $details[] = ["ligne" => $ligne_numero, "status" => "Erreur", "msg" => "Quantité '$quantite_raw' invalide", "action" => "Rejeté"];
        $nb_erreurs++;
        return;
    }
    $quantite = intval($qte_check);

    if (!empty($date_peremption)) {
        $date_valide = validerDate($date_peremption);
        if (!$date_valide) {
            $details[] = ["ligne" => $ligne_numero, "status" => "Erreur", "msg" => "Date '$date_peremption' incorrecte", "action" => "Rejeté"];
            $nb_erreurs++;
            return;
        }
        $date_peremption = $date_valide;
    }
    
    try {
        $produit = $produitModel->getByCodeBarre($code_barre);
        
        if (!$produit) {
            // Catégorie
            $id_categorie = 1;
            if (!empty($categorie_nom)) {
                $categories = $categorieModel->getAll();
                $trouve = false;
                foreach ($categories as $c) {
                    if (strtolower($c['nom']) === strtolower($categorie_nom)) {
                        $id_categorie = $c['id_categorie'];
                        $trouve = true;
                        break;
                    }
                }
                // ✅ Créer la catégorie si elle n'existe pas
                if (!$trouve) {
                    $categorieModel->ajouter($categorie_nom, 'Importé automatiquement');
                    $id_categorie = $conn->lastInsertId();
                }
            }
            
            // Fournisseur
            $id_fournisseur = 1;
            if (!empty($fournisseur_nom)) {
                $fournisseurs = $fournisseurModel->getAll();
                $trouve = false;
                foreach ($fournisseurs as $f) {
                    if (strtolower($f['nom']) === strtolower($fournisseur_nom)) {
                        $id_fournisseur = $f['id_fournisseur'];
                        $trouve = true;
                        break;
                    }
                }
                // ✅ Créer le fournisseur s'il n'existe pas
                if (!$trouve) {
                    $fournisseurModel->ajouter($fournisseur_nom, '', '', '');
                    $id_fournisseur = $conn->lastInsertId();
                }
            }
            
            $produitModel->ajouter($code_barre, $nom, '', 0, $prix_vente, 10, 10, 30, $id_categorie, $id_fournisseur);
            $id_produit = $conn->lastInsertId();
        } else {
            $id_produit = $produit['id_produit'];
        }
        
        $stockRayonModel->ajouter($id_produit, $id_rayon, $quantite, $date_peremption ?: null);
        $nb_importes++;
        
    } catch (Exception $e) {
        $details[] = ["ligne" => $ligne_numero, "status" => "BDD", "msg" => "Erreur SQL : " . $e->getMessage(), "action" => "Échec"];
        $nb_erreurs++;
    }
}

// ============================================================
// TRAITEMENT DE L'UPLOAD
// ============================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['fichier'])) {
    $fichier = $_FILES['fichier'];
    
    if (!$rayon) {
        $erreur = 'Aucun rayon assigné. Contactez l\'administrateur.';
    } elseif ($fichier['error'] !== UPLOAD_ERR_OK) {
        $erreur = 'Erreur lors du téléchargement du fichier.';
    } else {
        $extension = strtolower(pathinfo($fichier['name'], PATHINFO_EXTENSION));
        if (!in_array($extension, ['csv', 'txt'])) {
            $erreur = 'Format non supporté. Utilisez un fichier CSV (.csv).';
        } else {
            $contenu = file_get_contents($fichier['tmp_name']);
            $separateur = (substr_count($contenu, ';') >= substr_count($contenu, ',')) ? ';' : ',';
            
            $handle = fopen($fichier['tmp_name'], 'r');
            $ligne_numero = 0;
            $nb_importes = 0;
            $nb_erreurs = 0;
            
            // --- GESTION INTELLIGENTE DE L'EN-TÊTE ---
            $premiere_ligne = fgetcsv($handle, 1000, $separateur);
            if ($premiere_ligne) {
                $ligne_numero = 1;
                $col_1 = strtolower(trim($premiere_ligne[0] ?? ''));
                $col_qte = trim($premiere_ligne[2] ?? '');

                $est_titre = false;
                if (in_array($col_1, ['code_barre', 'code-barre', 'code', 'reference', 'ref'])) {
                    $est_titre = true;
                } elseif (!is_numeric(str_replace(',', '.', $col_qte)) && !empty($col_qte)) {
                    $est_titre = true;
                }

                if (!$est_titre) {
                    traiterLigne($premiere_ligne, 1, $rayon['id_rayon'], $nb_importes, $nb_erreurs, $codes_deja_vus);
                }
            }
            
            // Lecture du reste
            while (($ligne = fgetcsv($handle, 1000, $separateur)) !== false) {
                $ligne_numero++;
                traiterLigne($ligne, $ligne_numero, $rayon['id_rayon'], $nb_importes, $nb_erreurs, $codes_deja_vus);
            }
            fclose($handle);
            
            if ($nb_importes > 0) {
                $message = "$nb_importes produit(s) importé(s) dans le rayon " . htmlspecialchars($rayon['nom']) . ".";
            } else {
                $erreur = "Aucun produit importé. Vérifiez le contenu du fichier.";
            }
        }
    }
}

// Stock actuel
$stockActuel = $rayon ? $stockRayonModel->getByRayon($rayon['id_rayon']) : [];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>STOCKFLOW - Importer inventaire rayon</title>
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
            --upload-bg: #f8fafc;
            --upload-border: #cbd5e1;
        }

        [data-theme="dark"] {
            /* Thème Sombre */
            --bg-color: #0f172a;
            --surface-color: #1e293b;
            --text-main: #f8fafc;
            --text-muted: #94a3b8;
            --border-color: rgba(255, 255, 255, 0.08);
            --notif-bg: rgba(255, 255, 255, 0.03);
            --upload-bg: rgba(255, 255, 255, 0.02);
            --upload-border: #475569;
        }

        * { font-family: 'Poppins', sans-serif; }
        
        body { 
            background-color: var(--bg-color); 
            color: var(--text-main);
            transition: background-color 0.3s ease, color 0.3s ease;
        }
        
        /* Sidebar */
        .sidebar {
            background: var(--surface-color) !important; min-height: 100vh; color: var(--text-main);
            position: fixed; top: 0; left: 0; bottom: 0; width: 280px; overflow-y: auto;
            box-shadow: 2px 0 10px rgba(0,0,0,0.02); z-index: 1000;
            border-right: 1px solid var(--border-color);
            transition: all 0.3s ease;
        }
        .sidebar .logo { text-align: center; padding: 25px 0; border-bottom: 1px solid var(--border-color); }
        .sidebar .logo h4 { font-weight: 800; color: var(--primary); }
        .sidebar .logo i { color: var(--primary); background: rgba(249,115,22,0.1); padding: 12px; border-radius: 15px; }
        .sidebar .logo p { color: var(--text-muted); }
        .sidebar .nav-link {
            color: var(--text-muted) !important; padding: 12px 25px; margin: 5px 0;
            border-radius: 10px; transition: all 0.3s ease;
        }
        .sidebar .nav-link:hover, .sidebar .nav-link.active { background: var(--primary) !important; color: white !important; }
        .sidebar .nav-link i { width: 28px; margin-right: 12px; color: var(--primary); }
        .sidebar .nav-link:hover i, .sidebar .nav-link.active i { color: white; }
        
        /* Contenu */
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

        .form-card { 
            background: var(--surface-color); 
            border-radius: 15px; 
            padding: 30px; 
            box-shadow: 0 2px 10px rgba(0,0,0,0.02); 
            margin-bottom: 25px; 
            border: 1px solid var(--border-color);
        }
        
        .upload-zone { 
            border: 2px dashed var(--upload-border); 
            border-radius: 15px; 
            padding: 40px; 
            text-align: center; 
            background: var(--upload-bg); 
            transition: all 0.3s; 
        }
        .upload-zone:hover { border-color: var(--primary); background: rgba(249,115,22,0.05); }
        
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

        /* Bootstrap Dropdown & Elements */
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

        /* Doublons ou alertes */
        .badge.bg-warning.text-dark {
            background-color: #ffc107 !important;
            color: #212529 !important;
        }

        

        @media (max-width: 768px) { .sidebar { width: 70px; } .sidebar .nav-link span, .sidebar .logo p, .sidebar .logo h4 { display: none; } .main-content { margin-left: 70px; } }
    </style>
</head>
<body>

    <div class="sidebar">
        <div class="logo"><i class="fas fa-chart-line fa-2x"></i><h4>STOCK<span>FLOW</span></h4><p>Caissier<?php echo $rayon ? ' - ' . htmlspecialchars($rayon['nom']) : ''; ?></p></div>
        <nav class="nav flex-column mt-3">
            <a class="nav-link" href="tableau_de_bord_caissier.php"><i class="fas fa-tachometer-alt"></i> <span>Tableau de bord</span></a>
            <a class="nav-link" href="stock_rayon.php"><i class="fas fa-store"></i> <span>Stock rayon</span></a>
            <!-- <a class="nav-link active" href="inventaire_rayon_importer.php"><i class="fas fa-upload"></i> <span>Importer inventaire</span></a> -->
            <a class="nav-link" href="caisse.php"><i class="fas fa-cash-register"></i> <span>Caisse</span></a>
            <a class="nav-link" href="ventes_historique.php"><i class="fas fa-history"></i> <span>Historique ventes</span></a>
            <a class="nav-link" href="casse_perime_signaler.php"><i class="fas fa-trash-alt"></i> <span>Casse/Périmé</span></a>
            <a class="nav-link" href="notifications.php"><i class="fas fa-bell"></i> <span>Notifications</span></a>
            <a class="nav-link" href="profil.php"><i class="fas fa-user"></i> <span>Profil</span></a>
            <a class="nav-link" href="../../../deconnexion.php"><i class="fas fa-sign-out-alt"></i> <span>Déconnexion</span></a>
        </nav>
    </div>

    <div class="main-content">
        
        <div class="top-bar d-flex justify-content-between align-items-center">
            <h4 class="mb-0"><i class="fas fa-upload me-2" style="color: var(--primary);"></i>Importer inventaire</h4>
            
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
        
        <?php if ($message): ?><div class="alert alert-success"><i class="fas fa-check-circle me-2"></i><?php echo $message; ?></div><?php endif; ?>
        <?php if ($erreur): ?><div class="alert alert-danger"><i class="fas fa-exclamation-triangle me-2"></i><?php echo $erreur; ?></div><?php endif; ?>
        
        <?php if (!empty($details)): ?>
            <div class="form-card">
                <h6 class="mb-3">Rapport détaillé de l'importation</h6>
                <div class="table-responsive">
                    <table class="table table-sm table-hover">
                        <thead>
                            <tr><th>Ligne</th><th>Statut</th><th>Message</th><th>Action</th></tr>
                        </thead>
                        <tbody>
                            <?php foreach ($details as $d): 
                                $badge = ($d['status'] === 'Erreur') ? 'danger' : (($d['status'] === 'Doublon') ? 'warning text-dark' : 'info');
                            ?>
                                <tr>
                                    <td><?php echo $d['ligne']; ?></td>
                                    <td><span class="badge bg-<?php echo $badge; ?>"><?php echo $d['status']; ?></span></td>
                                    <td><?php echo htmlspecialchars($d['msg']); ?></td>
                                    <td><small class="text-muted"><?php echo $d['action']; ?></small></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php endif; ?>

        <div class="form-card">
            <h5 class="mb-3"><i class="fas fa-file-upload me-2" style="color:var(--primary);"></i>Importer (CSV) - Rayon <?php echo $rayon ? htmlspecialchars($rayon['nom']) : 'Non assigné'; ?></h5>
            
            <form method="POST" enctype="multipart/form-data">
                <div class="upload-zone mb-3">
                    <i class="fas fa-cloud-upload-alt fa-3x mb-3" style="color:var(--primary);"></i>
                    <h5>Déposez votre fichier ici</h5>
                    <p class="text-muted small">Séparateurs supportés : Point-virgule (;) ou Virgule (,)</p>
                    <input type="file" name="fichier" class="form-control mt-2" accept=".csv,.txt" required>
                </div>
                
                <div class="alert alert-warning py-2 small">
                    <strong>Format attendu :</strong><br>
                    <code>code_barre ; nom ; quantite ; prix_vente ; date_peremption ; categorie ; fournisseur</code>
                </div>
                
                <button type="submit" class="btn btn-primary" style="background:var(--primary); border:none;"><i class="fas fa-upload me-1"></i> Lancer l'importation</button>
                <a href="stock_rayon.php" class="btn btn-outline-secondary">Retour</a>
            </form>
        </div>

        <div class="form-card">
            <h6 class="mb-3"><i class="fas fa-store me-2" style="color:var(--primary);"></i>Aperçu du stock actuel</h6>
            <div class="table-responsive">
                <table class="table table-sm">
                    <thead><tr><th>Produit</th><th>Quantité</th><th>Péremption</th></tr></thead>
                    <tbody>
                        <?php foreach (array_slice($stockActuel, 0, 10) as $s): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($s['nom_produit']); ?></td>
                                <td><span class="fw-bold"><?php echo $s['quantite']; ?></span></td>
                                <td><?php echo $s['date_peremption'] ? date('d/m/Y', strtotime($s['date_peremption'])) : '<span class="text-muted">N/A</span>'; ?></td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (empty($stockActuel)): ?><tr><td colspan="3" class="text-center text-muted">Aucun produit en rayon.</td></tr><?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script src="../bootstrap-5.3.8-dist/js/bootstrap.bundle.min.js"></script>
    <script src="../../../public/js/responsive.js"></script>
    <script>
        // Script d'activation et d'écouteur pour le thème
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