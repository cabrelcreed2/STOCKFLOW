<?php
session_start();

// Vérifier connexion
if (!isset($_SESSION['utilisateur'])) {
    header('Location: ../authentification/connexion.php');
    exit;
}

// Inclure les modèles
require_once __DIR__ . '/../../../source/modeles/BaseDeDonnees.php';
require_once __DIR__ . '/../../../source/modeles/Utilisateur.php';

// Connexion
$db = new Database();
$conn = $db->connect();
$user = $_SESSION['utilisateur'];

// Récupérer le rayon
$sql = "SELECT * FROM rayon WHERE id_caissiere = :id";
$stmt = $conn->prepare($sql);
$stmt->execute(['id' => $user['id_utilisateur']]);
$rayon = $stmt->fetch();

// Traitement du formulaire
$message = '';
$erreur = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $utilisateurModel = new Utilisateur($conn);
    
    // Mise à jour du profil
    if (isset($_POST['action']) && $_POST['action'] === 'profil') {
        $nom = $_POST['nom'] ?? '';
        $prenom = $_POST['prenom'] ?? '';
        
        if (empty($nom) || empty($prenom)) {
            $erreur = 'Le nom et le prénom sont obligatoires.';
        } else {
            $resultat = $utilisateurModel->modifier($user['id_utilisateur'], $nom, $prenom, $user['email'], $user['role']);
            if ($resultat) {
                // Mettre à jour la session
                $user['nom'] = $nom;
                $user['prenom'] = $prenom;
                $_SESSION['utilisateur'] = $user;
                $message = 'Profil mis à jour avec succès.';
            } else {
                $erreur = 'Erreur lors de la mise à jour.';
            }
        }
    }
    
    // Changement de mot de passe
    if (isset($_POST['action']) && $_POST['action'] === 'mdp') {
        $ancien = $_POST['ancien_mdp'] ?? '';
        $nouveau = $_POST['nouveau_mdp'] ?? '';
        $confirmer = $_POST['confirmer_mdp'] ?? '';
        
        if (empty($ancien) || empty($nouveau) || empty($confirmer)) {
            $erreur = 'Tous les champs sont obligatoires.';
        } elseif ($ancien !== $user['mot_de_passe']) {
            $erreur = 'Ancien mot de passe incorrect.';
        } elseif ($nouveau !== $confirmer) {
            $erreur = 'Les mots de passe ne correspondent pas.';
        } elseif (strlen($nouveau) < 4) {
            $erreur = 'Le mot de passe doit faire au moins 4 caractères.';
        } else {
            $resultat = $utilisateurModel->modifierMotDePasse($user['id_utilisateur'], $nouveau);
            if ($resultat) {
                $user['mot_de_passe'] = $nouveau;
                $_SESSION['utilisateur'] = $user;
                $message = 'Mot de passe changé avec succès.';
            } else {
                $erreur = 'Erreur lors du changement de mot de passe.';
            }
        }
    }
}

// Icône selon le rôle
$icones = [
    'ADMIN' => 'fa-user-shield',
    'GESTIONNAIRE' => 'fa-user-tie',
    'MAGASINIER' => 'fa-user-gear',
    'CAISSIERE' => 'fa-cash-register'
];
$icone = $icones[$user['role']] ?? 'fa-user';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>STOCKFLOW - Mon profil</title>
    
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
            --input-bg: #ffffff;
            --input-disabled: #e2e8f0;
        }

        [data-theme="dark"] {
            /* Thème Sombre */
            --bg-color: #0f172a;
            --surface-color: #1e293b;
            --text-main: #f8fafc;
            --text-muted: #94a3b8;
            --border-color: rgba(255, 255, 255, 0.08);
            --input-bg: #0f172a;
            --input-disabled: #334155;
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
        .sidebar .logo p { color: var(--text-muted); margin-bottom: 0; margin-top: 10px; font-weight: 500;}
        .sidebar .nav-link {
            color: var(--text-muted) !important; padding: 12px 25px; margin: 5px 0;
            border-radius: 10px; transition: all 0.3s ease; background: transparent;
        }
        .sidebar .nav-link:hover { background: var(--primary) !important; color: white !important; transform: translateX(5px); }
        .sidebar .nav-link.active { background: var(--primary) !important; color: white !important; }
        .sidebar .nav-link i { width: 28px; margin-right: 12px; color: var(--primary); }
        .sidebar .nav-link:hover i, .sidebar .nav-link.active i { color: white; }
        
        /* Contenu principal */
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
        
        .profile-header { 
            background: var(--surface-color); 
            box-shadow: 0 2px 10px rgba(0,0,0,0.02); 
            border: 1px solid var(--border-color);
            border-radius: 20px; 
            padding: 30px; 
            text-align: center; 
            margin-bottom: 25px; 
        }
        .profile-avatar { 
            width: 80px; 
            height: 80px; 
            background: rgba(249,115,22,0.1); 
            border-radius: 50%; 
            display: flex; 
            align-items: center; 
            justify-content: center; 
            margin: 0 auto 15px; 
        }
        .profile-avatar i { font-size: 40px; color: var(--primary); }
        
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
            background-color: var(--bg-color);
            color: var(--primary);
        }

        /* Forms Override */
        .form-control {
            background-color: var(--input-bg);
            border-color: var(--border-color);
            color: var(--text-main);
        }
        .form-control:focus {
            background-color: var(--input-bg);
            color: var(--text-main);
            border-color: var(--primary);
            box-shadow: 0 0 0 0.25rem rgba(249, 115, 22, 0.25);
        }
        .form-control[readonly] {
            background-color: var(--input-disabled);
            color: var(--text-muted);
            opacity: 1;
        }

        /* Bootstrap UI Overrides */
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
        
        /* Badges et alertes en mode sombre */
        [data-theme="dark"] .bg-light { background-color: var(--bg-color) !important; color: var(--text-main) !important; border-color: var(--border-color) !important; }
        [data-theme="dark"] .alert-success { background-color: rgba(34, 197, 94, 0.15); border-color: rgba(34, 197, 94, 0.3); color: #86efac; }
        [data-theme="dark"] .alert-danger { background-color: rgba(220, 38, 38, 0.15); border-color: rgba(220, 38, 38, 0.3); color: #fca5a5; }

        @media (max-width: 768px) { .sidebar { width: 70px; } .sidebar .nav-link span, .sidebar .logo p, .sidebar .logo h4 { display: none; } .main-content { margin-left: 70px; } }
    </style>
</head>
<body>

    <div class="sidebar">
        <div class="logo">
            <i class="fas fa-chart-line fa-2x"></i>
            <h4>STOCK<span>FLOW</span></h4>
            <p><?php echo htmlspecialchars($user['role']); ?><?php echo $rayon ? ' - ' . htmlspecialchars($rayon['nom']) : ''; ?></p>
        </div>
        <nav class="nav flex-column mt-3">
            <a class="nav-link" href="tableau_de_bord_caissier.php"><i class="fas fa-tachometer-alt"></i> <span>Tableau de bord</span></a>
            <a class="nav-link" href="stock_rayon.php"><i class="fas fa-store"></i> <span>Stock rayon</span></a>
            <!-- <a class="nav-link" href="inventaire_rayon_importer.php"><i class="fas fa-upload"></i> <span>Importer inventaire rayon</span></a> -->
            <a class="nav-link" href="caisse.php"><i class="fas fa-cash-register"></i> <span>Caisse (vente)</span></a>
            <a class="nav-link" href="ventes_historique.php"><i class="fas fa-history"></i> <span>Historique ventes</span></a>
            <a class="nav-link" href="casse_perime_signaler.php"><i class="fas fa-trash-alt"></i> <span>Signaler casse/périmé</span></a>
            <a class="nav-link" href="notifications.php"><i class="fas fa-bell"></i> <span>Notifications</span></a>
            <a class="nav-link active" href="profil.php"><i class="fas fa-user"></i> <span>Mon profil</span></a>
            <a class="nav-link" href="../../../deconnexion.php"><i class="fas fa-sign-out-alt"></i> <span>Déconnexion</span></a>
        </nav>
    </div>

    <div class="main-content">
        
        <div class="top-bar d-flex justify-content-between align-items-center">
            <h4 class="mb-0"><i class="fas fa-user me-2" style="color: var(--primary);"></i>Mon profil</h4>
            
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
        
        <?php if (!empty($message)): ?>
            <div class="alert alert-success alert-dismissible fade show shadow-sm">
                <i class="fas fa-check-circle me-2"></i><?php echo $message; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        <?php if (!empty($erreur)): ?>
            <div class="alert alert-danger alert-dismissible fade show shadow-sm">
                <i class="fas fa-exclamation-triangle me-2"></i><?php echo $erreur; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <div class="profile-header">
            <div class="profile-avatar"><i class="fas <?php echo $icone; ?>"></i></div>
            <h4><?php echo htmlspecialchars($user['prenom'] . ' ' . $user['nom']); ?></h4>
            <p class="text-muted"><?php echo htmlspecialchars($user['email']); ?></p>
            <?php if ($rayon): ?>
                <span class="badge bg-light text-dark border p-2">
                    <i class="fas fa-store me-1" style="color: var(--primary);"></i>
                    Rayon assigné : <?php echo htmlspecialchars($rayon['nom']); ?>
                    <?php if ($rayon['emplacement']): ?>
                        (<?php echo htmlspecialchars($rayon['emplacement']); ?>)
                    <?php endif; ?>
                </span>
            <?php endif; ?>
        </div>

        <div class="form-card">
            <h5 class="mb-4"><i class="fas fa-user-edit me-2" style="color: var(--primary);"></i>Modifier mes informations</h5>
            <form method="POST">
                <input type="hidden" name="action" value="profil">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label text-muted">Nom</label>
                        <input type="text" name="nom" class="form-control" 
                               value="<?php echo htmlspecialchars($user['nom']); ?>" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label text-muted">Prénom</label>
                        <input type="text" name="prenom" class="form-control" 
                               value="<?php echo htmlspecialchars($user['prenom']); ?>" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label text-muted">Email</label>
                        <input type="email" class="form-control" value="<?php echo htmlspecialchars($user['email']); ?>" readonly>
                        <small class="text-muted"><i class="fas fa-info-circle me-1"></i> L'email ne peut pas être modifié.</small>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label text-muted">Rôle</label>
                        <input type="text" class="form-control" value="<?php echo htmlspecialchars($user['role']); ?>" readonly>
                    </div>
                </div>
                <button type="submit" class="btn btn-outline-primary mt-2">
                    <i class="fas fa-save me-1"></i> Mettre à jour
                </button>
            </form>
        </div>

        <div class="form-card">
            <h5 class="mb-4"><i class="fas fa-lock me-2" style="color: var(--primary);"></i>Changer le mot de passe</h5>
            <form method="POST">
                <input type="hidden" name="action" value="mdp">
                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label class="form-label text-muted">Ancien mot de passe</label>
                        <input type="password" name="ancien_mdp" class="form-control" required>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label text-muted">Nouveau mot de passe</label>
                        <input type="password" name="nouveau_mdp" class="form-control" required minlength="4">
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label text-muted">Confirmer le mot de passe</label>
                        <input type="password" name="confirmer_mdp" class="form-control" required minlength="4">
                    </div>
                </div>
                <button type="submit" class="btn btn-outline-primary mt-2">
                    <i class="fas fa-key me-1"></i> Mettre à jour le mot de passe
                </button>
            </form>
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