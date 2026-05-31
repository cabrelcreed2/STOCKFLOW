<?php
session_start();

// Vérification de la session et du rôle
if (!isset($_SESSION['utilisateur']) || $_SESSION['utilisateur']['role'] !== 'MAGASINIER') {
    header('Location: ../authentification/connexion.php');
    exit;
}

require_once __DIR__ . '/../../../source/controleurs/StockControleur.php';
require_once __DIR__ . '/../../../source/modeles/BaseDeDonnees.php';
require_once __DIR__ . '/../../../source/modeles/StockMagasin.php';
require_once __DIR__ . '/../../../source/modeles/Produit.php';
require_once __DIR__ . '/../../../source/modeles/Categorie.php';
require_once __DIR__ . '/../../../source/modeles/Fournisseur.php';

$db = new Database();
$conn = $db->connect();
$user = $_SESSION['utilisateur'];

$produitModel = new Produit($conn);
$categorieModel = new Categorie($conn);
$fournisseurModel = new Fournisseur($conn);
$stockMagasinModel = new StockMagasin($conn);

$message = '';
$erreur = '';
$details = [];
$codes_deja_vus = []; // Pour traquer les doublons à l'intérieur du fichier

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
function traiterLigne($ligne, $ligne_numero, &$nb_importes, &$nb_erreurs, &$codes_deja_vus) {
    global $conn, $produitModel, $categorieModel, $fournisseurModel, $stockMagasinModel, $details;
    
    // Ignorer les lignes vides
    if (!$ligne || !array_filter($ligne)) return;

    if (count($ligne) < 3) {
        $details[] = ["ligne" => $ligne_numero, "status" => "Erreur", "msg" => "Colonnes insuffisantes", "action" => "Rejeté"];
        $nb_erreurs++;
        return;
    }
    
    $code_barre = trim($ligne[0] ?? '');
    $nom = trim($ligne[1] ?? '');
    $quantite_raw = trim($ligne[2] ?? '0');
    $prix_achat_raw = trim($ligne[3] ?? '0');
    $prix_vente_raw = trim($ligne[4] ?? '0');
    $date_peremption = trim($ligne[5] ?? '');
    $categorie_nom = trim($ligne[6] ?? '');
    $fournisseur_nom = trim($ligne[7] ?? '');
    $fournisseur_email = trim($ligne[8] ?? '');
    $fournisseur_tel = trim($ligne[9] ?? '');
    
    // 1. Vérifier les champs obligatoires
    if (empty($code_barre) || empty($nom)) {
        $details[] = ["ligne" => $ligne_numero, "status" => "Erreur", "msg" => "Code-barre ou nom vide", "action" => "Rejeté"];
        $nb_erreurs++;
        return;
    }

    // 2. Détection doublon interne au fichier
    if (in_array($code_barre, $codes_deja_vus)) {
        $details[] = ["ligne" => $ligne_numero, "status" => "Doublon", "msg" => "Code [$code_barre] déjà présent dans ce fichier", "action" => "Ignoré"];
        $nb_erreurs++;
        return;
    }
    $codes_deja_vus[] = $code_barre;

    // 3. Validation numérique (Gestion des virgules et nombres)
    $prix_achat = floatval(str_replace(',', '.', $prix_achat_raw));
    $prix_vente = floatval(str_replace(',', '.', $prix_vente_raw));
    
    if (!is_numeric($quantite_raw) || intval($quantite_raw) <= 0) {
        $details[] = ["ligne" => $ligne_numero, "status" => "Erreur", "msg" => "Quantité '$quantite_raw' invalide", "action" => "Rejeté"];
        $nb_erreurs++;
        return;
    }
    $quantite = intval($quantite_raw);

    // 4. Alerte de cohérence prix
    if ($prix_achat > $prix_vente && $prix_vente > 0) {
        $details[] = ["ligne" => $ligne_numero, "status" => "Alerte", "msg" => "Prix achat > Prix vente", "action" => "Importé"];
    }
    
    // 5. Validation de la date
    if (!empty($date_peremption)) {
        $date_valide = validerDate($date_peremption);
        if (!$date_valide) {
            $details[] = ["ligne" => $ligne_numero, "status" => "Erreur", "msg" => "Date '$date_peremption' incorrecte", "action" => "Rejeté"];
            $nb_erreurs++;
            return;
        }
        $date_peremption = $date_valide;
    }
    
    // 6. Insertion en base de données
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
    // ✅ AJOUTÉ : Créer la catégorie si elle n'existe pas
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
        
        if (!$trouve) {
        // Créer le fournisseur
        $fournisseurModel->ajouter($fournisseur_nom, $fournisseur_email, $fournisseur_tel, '');
        $id_fournisseur = $conn->lastInsertId();
    } else {
        // Mettre à jour email et téléphone si renseignés
        if (!empty($fournisseur_email) || !empty($fournisseur_tel)) {
            $sql = "UPDATE fournisseur SET email = :email, telephone = :tel WHERE id_fournisseur = :id";
            $stmt = $conn->prepare($sql);
            $stmt->execute([
                'email' => !empty($fournisseur_email) ? $fournisseur_email : null,
                'tel' => $fournisseur_tel,
                'id' => $id_fournisseur
            ]);
        }
    }
}
            
            $produitModel->ajouter($code_barre, $nom, '', $prix_achat, $prix_vente, 10, 10, 30, $id_categorie, $id_fournisseur);
            $id_produit = $conn->lastInsertId();
        } else {
            $id_produit = $produit['id_produit'];
        }
        
        $stockMagasinModel->ajouter($id_produit, $quantite, $date_peremption ?: null);
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
    
    if ($fichier['error'] !== UPLOAD_ERR_OK) {
        $erreur = 'Erreur lors du téléchargement du fichier.';
    } else {
        $extension = strtolower(pathinfo($fichier['name'], PATHINFO_EXTENSION));
        if (!in_array($extension, ['csv', 'txt'])) {
            $erreur = 'Format non supporté. Utilisez un fichier CSV (.csv).';
        } else {
            $contenu = file_get_contents($fichier['tmp_name']);

               $nb_point_virgule = substr_count($contenu, ';');
                $nb_virgule = substr_count($contenu, ',');
                $nb_tab = substr_count($contenu, "\t");

                if ($nb_tab > $nb_point_virgule && $nb_tab > $nb_virgule) {
                    $separateur = "\t";
                } elseif ($nb_point_virgule >= $nb_virgule) {
                    $separateur = ';';
                } else {
                    $separateur = ',';
                }
            
            $handle = fopen($fichier['tmp_name'], 'r');
            $ligne_numero = 0;
            $nb_importes = 0;
            $nb_erreurs = 0;
            
            // --- DETECTION INTELLIGENTE DE L'EN-TETE ---
            $premiere_ligne = fgetcsv($handle, 1000, $separateur);
            if ($premiere_ligne) {
                $ligne_numero = 1;
                $col_code = strtolower(trim($premiere_ligne[0] ?? ''));
                $col_qte = trim($premiere_ligne[2] ?? '');

                // On vérifie si c'est un en-tête (soit par mot-clé, soit si la quantité est du texte)
                $est_entete = false;
                if (in_array($col_code, ['code_barre', 'code-barre', 'code', 'reference', 'ref'])) {
                    $est_entete = true;
                } elseif (!is_numeric(str_replace(',', '.', $col_qte)) && !empty($col_qte)) {
                    $est_entete = true;
                }

                if (!$est_entete) {
                    // Ce n'est pas un en-tête (ex: la ligne commence direct par un produit), on traite
                    traiterLigne($premiere_ligne, 1, $nb_importes, $nb_erreurs, $codes_deja_vus);
                }
            }
            
            // Traitement du reste du fichier
            while (($ligne = fgetcsv($handle, 1000, $separateur)) !== false) {
                $ligne_numero++;
                traiterLigne($ligne, $ligne_numero, $nb_importes, $nb_erreurs, $codes_deja_vus);
            }
            fclose($handle);
            
            if ($nb_importes > 0) {
                $message = "$nb_importes produit(s) importé(s) avec succès.";
            } else {
                $erreur = "Aucun produit importé. Vérifiez le contenu du fichier.";
            }
        }
    }
}

$stockActuel = $stockMagasinModel->getAll();
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>STOCKFLOW - Importer inventaire</title>
    
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
            
            /* Inputs et Upload */
            --input-bg: #ffffff;
            --input-text: #1e293b;
            --input-border: #ced4da;
            --upload-bg: #f8fafc;
            --upload-border: #cbd5e1;
            --upload-hover-bg: #fff7ed;
            
            /* Table */
            --table-text: #1e293b;
            --table-border: #dee2e6;
            --table-hover: rgba(0,0,0,0.05);

            /* Alertes / Badges */
            --alert-success-bg: #d1e7dd; --alert-success-text: #0f5132;
            --alert-danger-bg: #f8d7da; --alert-danger-text: #842029;
            --alert-warning-bg: #fff3cd; --alert-warning-text: #664d03;
            --badge-warning-bg: #fff7ed; --badge-warning-text: #c2410c; --badge-warning-border: #fdba74;
        }
        
        /* --- VARIABLES THÈME SOMBRE --- */
        [data-theme="dark"] {
            --bg-body: #0f172a;
            --bg-surface: #1e293b;
            --text-main: #f8fafc;
            --text-muted: #94a3b8;
            --border-color: rgba(255,255,255,0.1);

            /* Inputs et Upload */
            --input-bg: #0f172a;
            --input-text: #f8fafc;
            --input-border: #334155;
            --upload-bg: #0f172a;
            --upload-border: #334155;
            --upload-hover-bg: rgba(249, 115, 22, 0.1);

            /* Table */
            --table-text: #f8fafc;
            --table-border: #334155;
            --table-hover: rgba(255,255,255,0.05);

            /* Alertes / Badges */
            --alert-success-bg: rgba(15, 81, 50, 0.4); --alert-success-text: #75b798;
            --alert-danger-bg: rgba(132, 32, 41, 0.4); --alert-danger-text: #ea868f;
            --alert-warning-bg: rgba(102, 77, 3, 0.4); --alert-warning-text: #ffda6a;
            --badge-warning-bg: rgba(194, 65, 12, 0.2); --badge-warning-text: #fdba74; --badge-warning-border: rgba(253, 186, 116, 0.3);
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
        .sidebar .nav-link:hover, .sidebar .nav-link.active { background: var(--primary) !important; color: white !important; }
        .sidebar .nav-link i { width: 28px; margin-right: 12px; color: var(--primary); }
        .sidebar .nav-link:hover i, .sidebar .nav-link.active i { color: white; }
        
        .main-content { margin-left: 280px; padding: 20px; }
        
        /* Cards */
        .form-card { 
            background: var(--bg-surface); 
            border-radius: 15px; padding: 30px; 
            box-shadow: 0 2px 10px rgba(0,0,0,0.05); margin-bottom: 25px; 
            transition: background-color 0.3s ease;
        }
        
        /* Zone Upload */
        .upload-zone {
            border: 2px dashed var(--upload-border); border-radius: 15px; padding: 40px;
            text-align: center; background: var(--upload-bg); transition: all 0.3s;
        }
        .upload-zone:hover { border-color: var(--primary); background: var(--upload-hover-bg); }
        
        /* Inputs */
        .form-control { background-color: var(--input-bg); color: var(--input-text); border-color: var(--input-border); }
        .form-control:focus { background-color: var(--input-bg); color: var(--input-text); border-color: var(--primary); box-shadow: 0 0 0 0.25rem rgba(249, 115, 22, 0.25); }

        /* Tables */
        .table { color: var(--table-text); border-color: var(--table-border); }
        .table th, .table td { background-color: var(--bg-surface); color: var(--table-text); border-bottom-color: var(--table-border); }
        .table-hover tbody tr:hover td, .table-hover tbody tr:hover th { background-color: var(--table-hover); color: var(--table-text); }
        
        /* Alerts & Badges */
        .alert-success { background-color: var(--alert-success-bg); color: var(--alert-success-text); border-color: var(--border-color); }
        .alert-danger { background-color: var(--alert-danger-bg); color: var(--alert-danger-text); border-color: var(--border-color); }
        .alert-warning { background-color: var(--alert-warning-bg); color: var(--alert-warning-text); border-color: var(--border-color); }
        .badge-alerte { background: var(--badge-warning-bg); color: var(--badge-warning-text); border: 1px solid var(--badge-warning-border); }

        /* Theme Toggle Button */
        .theme-toggle-btn {
            background: transparent; border: none; color: var(--text-muted); font-size: 1.5rem; cursor: pointer; transition: color 0.3s ease;
        }
        .theme-toggle-btn:hover { color: var(--primary); }

        @media (max-width: 768px) { .sidebar { width: 70px; } .sidebar .nav-link span, .sidebar .logo h4, .sidebar .logo p { display: none; } .main-content { margin-left: 70px; } }
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
            <a class="nav-link active" href="inventaire_importer.php"><i class="fas fa-upload"></i> <span>Importer inventaire</span></a>
            <a class="nav-link" href="transfert_effectuer.php"><i class="fas fa-exchange-alt"></i> <span>Transfert</span></a>
            <a class="nav-link" href="casse_perime_signaler.php"><i class="fas fa-trash-alt"></i> <span>Casse/périmé</span></a>
            <a class="nav-link" href="alertes_magasinier.php"><i class="fas fa-bell"></i> <span>Alertes</span></a>
            <a class="nav-link" href="../../../deconnexion.php"><i class="fas fa-sign-out-alt"></i> <span>Déconnexion</span></a>
        </nav>
    </div>

    <div class="main-content">
        
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h3 class="m-0"><i class="fas fa-upload me-2" style="color: var(--primary);"></i>Importer inventaire</h3>
            <button id="theme-toggle" class="theme-toggle-btn" title="Basculer le thème">
                <i id="theme-icon" class="fas fa-moon"></i>
            </button>
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
                                $badge = ($d['status'] === 'Erreur') ? 'danger' : (($d['status'] === 'Alerte') ? 'warning text-dark' : 'info');
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
            <h5 class="mb-3"><i class="fas fa-file-upload me-2" style="color:var(--primary);"></i>Sélection du fichier</h5>
            <form method="POST" enctype="multipart/form-data">
                <div class="upload-zone mb-3">
                    <i class="fas fa-cloud-upload-alt fa-3x mb-3" style="color:var(--primary);"></i>
                    <h5>Sélectionnez votre fichier CSV</h5>
                    <p class="text-muted small">Séparateurs supportés : Point-virgule (;) ou Virgule (,)</p>
                    <input type="file" name="fichier" class="form-control mt-2" accept=".csv,.txt" required>
                </div>
                <div class="alert alert-warning py-2 small">
                    <strong>Format :</strong> code_barre ; nom ; quantite ; prix_achat ; prix_vente ; date_peremption ; categorie ; fournisseur
                </div>
                <button type="submit" class="btn btn-primary" style="background:var(--primary); border:none;">Lancer l'importation</button>
                <a href="stock_magasinier.php" class="btn btn-outline-secondary">Annuler</a>
            </form>
        </div>

        <div class="form-card">
            <h6 class="mb-3"><i class="fas fa-warehouse me-2" style="color:var(--primary);"></i>Aperçu stock actuel </h6>
            <div class="table-responsive">
                <table class="table table-sm">
                    <thead><tr><th>Produit</th><th>Quantité</th><th>Expiration</th></tr></thead>
                    <tbody>
                        <?php foreach (array_slice($stockActuel, 0, 10) as $s): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($s['nom_produit']); ?></td>
                                <td><span class="fw-bold"><?php echo $s['quantite']; ?></span></td>
                                <td><?php echo $s['date_peremption'] ? date('d/m/Y', strtotime($s['date_peremption'])) : '<span class="text-muted">N/A</span>'; ?></td>
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