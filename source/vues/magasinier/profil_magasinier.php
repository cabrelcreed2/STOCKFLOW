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

// Inclure les modèles
require_once __DIR__ . '/../../../source/modeles/BaseDeDonnees.php';
require_once __DIR__ . '/../../../source/modeles/Utilisateur.php';

// Connexion
$db = new Database();
$conn = $db->connect();
$user = $_SESSION['utilisateur'];

// Traitement du formulaire
$message = '';
$erreur = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $utilisateurModel = new Utilisateur($conn);
    
    if (isset($_POST['action']) && $_POST['action'] === 'profil') {
        $nom = $_POST['nom'] ?? '';
        $prenom = $_POST['prenom'] ?? '';
        
        if (empty($nom) || empty($prenom)) {
            $erreur = 'Le nom et le prénom sont obligatoires.';
        } else {
            $resultat = $utilisateurModel->modifier($user['id_utilisateur'], $nom, $prenom, $user['email'], $user['role']);
            if ($resultat) {
                $user['nom'] = $nom;
                $user['prenom'] = $prenom;
                $_SESSION['utilisateur'] = $user;
                $message = 'Profil mis à jour avec succès.';
            } else {
                $erreur = 'Erreur lors de la mise à jour.';
            }
        }
    }
    
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
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>STOCKFLOW - Mon profil</title>
    
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
            --input-readonly-bg: #e9ecef;

            /* Badges & Alertes */
            --badge-role-bg: #f8fafc;
            --badge-role-text: #1e293b;
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
            --input-readonly-bg: #1e293b;

            /* Badges & Alertes */
            --badge-role-bg: #0f172a;
            --badge-role-text: #f8fafc;
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
            border-radius: 15px; padding: 30px; 
            box-shadow: 0 2px 10px rgba(0,0,0,0.05); 
            margin-bottom: 25px;
            transition: background-color 0.3s ease;
        }
        .profile-header { 
            background-color: var(--bg-surface); 
            box-shadow: 0 2px 10px rgba(0,0,0,0.05); 
            border-radius: 20px; padding: 30px; 
            text-align: center; margin-bottom: 25px; 
            transition: background-color 0.3s ease;
        }
        .profile-avatar { width: 80px; height: 80px; background: rgba(249,115,22,0.1); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 15px; }
        .profile-avatar i { font-size: 40px; color: var(--primary); }
        
        /* Inputs & Controls */
        .form-control { 
            background-color: var(--input-bg); 
            color: var(--input-text); 
            border-color: var(--input-border); 
        }
        .form-control:focus { 
            background-color: var(--input-bg); 
            color: var(--input-text); 
            border-color: var(--primary); 
            box-shadow: 0 0 0 0.25rem rgba(249, 115, 22, 0.25); 
        }
        .form-control[readonly] {
            background-color: var(--input-readonly-bg);
            border-color: var(--input-border);
            opacity: 0.85;
        }

        /* Badges & Buttons */
        .badge-role {
            background-color: var(--badge-role-bg);
            color: var(--badge-role-text);
            border: 1px solid var(--border-color) !important;
        }
        .btn-outline-primary {
            color: var(--primary);
            border-color: var(--primary);
        }
        .btn-outline-primary:hover {
            background-color: var(--primary);
            border-color: var(--primary);
            color: #ffffff;
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
            <a class="nav-link" href="casse_perime_signaler.php"><i class="fas fa-trash-alt"></i> <span>Signaler casse/périmé</span></a>
            <a class="nav-link" href="alertes_magasinier.php"><i class="fas fa-bell"></i> <span>Alertes</span></a>
            <a class="nav-link active" href="profil_magasinier.php"><i class="fas fa-user"></i> <span>Mon profil</span></a>
            <a class="nav-link" href="../../../deconnexion.php"><i class="fas fa-sign-out-alt"></i> <span>Déconnexion</span></a>
        </nav>
    </div>

    <div class="main-content">
        
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h3 class="m-0"><i class="fas fa-user me-2" style="color: var(--primary);"></i>Mon profil</h3>
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

        <div class="profile-header">
            <div class="profile-avatar"><i class="fas fa-user-gear"></i></div>
            <h4><?php echo htmlspecialchars($user['prenom'] . ' ' . $user['nom']); ?></h4>
            <p class="text-muted mb-3"><?php echo htmlspecialchars($user['email']); ?></p>
            <span class="badge badge-role p-2 fs-7">
                <i class="fas fa-warehouse me-1" style="color: var(--primary);"></i>
                Magasinier - Stock principal
            </span>
        </div>

        <div class="form-card">
            <h5 class="mb-3"><i class="fas fa-user-edit me-2" style="color: var(--primary);"></i>Modifier mes informations</h5>
            <form method="POST">
                <input type="hidden" name="action" value="profil">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Nom</label>
                        <input type="text" name="nom" class="form-control" 
                               value="<?php echo htmlspecialchars($user['nom']); ?>" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Prénom</label>
                        <input type="text" name="prenom" class="form-control" 
                               value="<?php echo htmlspecialchars($user['prenom']); ?>" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Email</label>
                        <input type="email" class="form-control" value="<?php echo htmlspecialchars($user['email']); ?>" readonly>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Rôle</label>
                        <input type="text" class="form-control" value="MAGASINIER" readonly>
                    </div>
                </div>
                <button type="submit" class="btn btn-outline-primary"><i class="fas fa-save me-1"></i> Mettre à jour</button>
            </form>
        </div>

        <div class="form-card">
            <h5 class="mb-3"><i class="fas fa-lock me-2" style="color: var(--primary);"></i>Changer le mot de passe</h5>
            <form method="POST">
                <input type="hidden" name="action" value="mdp">
                <div class="row">
                    <div class="col-md-4 mb-3"><label class="form-label">Ancien mot de passe</label><input type="password" name="ancien_mdp" class="form-control" required></div>
                    <div class="col-md-4 mb-3"><label class="form-label">Nouveau mot de passe</label><input type="password" name="nouveau_mdp" class="form-control" required minlength="4"></div>
                    <div class="col-md-4 mb-3"><label class="form-label">Confirmer</label><input type="password" name="confirmer_mdp" class="form-control" required minlength="4"></div>
                </div>
                <button type="submit" class="btn btn-outline-primary"><i class="fas fa-key me-1"></i> Changer le mot de passe</button>
            </form>
        </div>
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

            // Ajustement immédiat de l'icône selon l'état actuel
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