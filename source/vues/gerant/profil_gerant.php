<?php
session_start();

if (!isset($_SESSION['utilisateur']) || $_SESSION['utilisateur']['role'] !== 'GESTIONNAIRE') {
    header('Location: ../authentification/connexion.php');
    exit;
}

require_once __DIR__ . '/../../../source/modeles/BaseDeDonnees.php';
require_once __DIR__ . '/../../../source/modeles/Utilisateur.php';

$db = new Database();
$conn = $db->connect();
$user = $_SESSION['utilisateur'];

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
                $message = 'Profil mis à jour.';
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
                $message = 'Mot de passe changé.';
            }
        }
    }
}
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
        const savedTheme = localStorage.getItem('stockflow_theme') || 'light';
        document.documentElement.setAttribute('data-theme', savedTheme);
    </script>

    <style>
        * { font-family: 'Poppins', sans-serif; }
        
        /* VARIABLES THÈME CLAIR */
        :root { 
            --primary: #f97316; 
            --bg-body: #f8fafc;
            --bg-sidebar: #ffffff;
            --bg-card: #ffffff;
            --text-main: #1e293b;
            --text-muted: #64748b;
            --border-color: rgba(0,0,0,0.1);
            --shadow-color: rgba(0,0,0,0.05);
            --btn-theme-bg: #f8f9fa;
            --input-bg: #ffffff;
            --input-text: #212529;
            --input-readonly-bg: #e9ecef;
            --badge-bg: #f8f9fa;
            --badge-text: #212529;
            --avatar-bg: rgba(249,115,22,0.1);
        }
        
        /* VARIABLES THÈME SOMBRE */
        [data-theme="dark"] {
            --bg-body: #0f172a;
            --bg-sidebar: #1e293b;
            --bg-card: #1e293b;
            --text-main: #f8fafc;
            --text-muted: #94a3b8;
            --border-color: rgba(255,255,255,0.1);
            --shadow-color: rgba(0,0,0,0.2);
            --btn-theme-bg: #334155;
            --input-bg: #334155;
            --input-text: #f8fafc;
            --input-readonly-bg: #0f172a;
            --badge-bg: #334155;
            --badge-text: #f8fafc;
            --avatar-bg: rgba(249,115,22,0.2);
        }

        /* APPLICATION DES VARIABLES */
        body { 
            background-color: var(--bg-body); 
            color: var(--text-main);
            transition: background-color 0.3s ease, color 0.3s ease;
        }
        
        .text-muted { color: var(--text-muted) !important; }
        
        /* Formulaires */
        .form-label { color: var(--text-main); }
        .form-control { 
            background-color: var(--input-bg); 
            color: var(--input-text); 
            border-color: var(--border-color); 
        }
        .form-control:focus {
            background-color: var(--input-bg);
            color: var(--input-text);
            border-color: var(--primary);
            box-shadow: 0 0 0 0.25px rgba(249, 115, 22, 0.5);
        }
        .form-control[readonly] {
            background-color: var(--input-readonly-bg);
            color: var(--text-muted);
            opacity: 1;
        }

        /* Barre latérale */
        .sidebar {
            background: var(--bg-sidebar) !important; 
            min-height: 100vh; 
            color: var(--text-main);
            position: fixed; top: 0; left: 0; bottom: 0; width: 280px; overflow-y: auto;
            box-shadow: 2px 0 10px var(--shadow-color);
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
        
        /* Conteneurs de fiches */
        .form-card { 
            background: var(--bg-card); 
            border-radius: 15px; 
            padding: 30px; 
            box-shadow: 0 2px 10px var(--shadow-color); 
            margin-bottom: 25px; 
            border: 1px solid var(--border-color);
            transition: background-color 0.3s;
        }
        .profile-header { 
            background: var(--bg-card); 
            border-radius: 20px; 
            padding: 30px; 
            text-align: center; 
            margin-bottom: 25px; 
            box-shadow: 0 2px 10px var(--shadow-color); 
            border: 1px solid var(--border-color);
            transition: background-color 0.3s;
        }
        .profile-avatar { 
            width: 80px; 
            height: 80px; 
            background: var(--avatar-bg); 
            border-radius: 50%; 
            display: flex; align-items: center; justify-content: center; 
            margin: 0 auto 15px; 
            transition: background-color 0.3s;
        }
        .profile-avatar i { font-size: 40px; color: var(--primary); }
        
        .badge.bg-light {
            background-color: var(--badge-bg) !important;
            color: var(--badge-text) !important;
            border-color: var(--border-color) !important;
        }

        /* Bouton Switch Thème */
        .btn-theme-toggle {
            background-color: var(--btn-theme-bg);
            color: var(--text-main);
            border: 1px solid var(--border-color);
            width: 40px; height: 40px;
            display: inline-flex; justify-content: center; align-items: center;
        }

        @media (max-width: 768px) { .sidebar { width: 70px; } .sidebar .nav-link span, .sidebar .logo p, .sidebar .logo h4 { display: none; } .main-content { margin-left: 70px; } }
    </style>
</head>
<body>

    <div class="sidebar">
        <div class="logo"><i class="fas fa-chart-line fa-2x"></i><h4>STOCK<span>FLOW</span></h4><p>Gérant</p></div>
        <nav class="nav flex-column mt-3">
            <a class="nav-link" href="tableau_de_bord_gerant.php"><i class="fas fa-tachometer-alt"></i> <span>Tableau de bord</span></a>
            <a class="nav-link" href="produits.php"><i class="fas fa-boxes"></i> <span>Produits</span></a>
            <a class="nav-link" href="fournisseurs.php"><i class="fas fa-truck"></i> <span>Fournisseurs</span></a>
            <a class="nav-link" href="stocks_consulter.php"><i class="fas fa-warehouse"></i> <span>Stocks</span></a>
            <a class="nav-link" href="commandes.php"><i class="fas fa-shopping-cart"></i> <span>Commandes</span></a>
            <a class="nav-link" href="alertes.php"><i class="fas fa-bell"></i> <span>Alertes</span></a>
            <a class="nav-link" href="rapports.php"><i class="fas fa-chart-line"></i> <span>Rapports</span></a>
            <a class="nav-link active" href="profil_gerant.php"><i class="fas fa-user"></i> <span>Profil</span></a>
            <a class="nav-link" href="../../../deconnexion.php"><i class="fas fa-sign-out-alt"></i> <span>Déconnexion</span></a>
        </nav>
    </div>

    <div class="main-content">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h3 class="m-0"><i class="fas fa-user me-2" style="color: var(--primary);"></i>Mon profil</h3>
            <button class="btn btn-theme-toggle rounded-circle" onclick="toggleTheme()" title="Basculer le thème">
                <i id="theme-icon" class="fas fa-moon"></i>
            </button>
        </div>

        <?php if (!empty($message)): ?><div class="alert alert-success"><?php echo $message; ?></div><?php endif; ?>
        <?php if (!empty($erreur)): ?><div class="alert alert-danger"><?php echo $erreur; ?></div><?php endif; ?>

        <div class="profile-header">
            <div class="profile-avatar"><i class="fas fa-user-tie"></i></div>
            <h4><?php echo htmlspecialchars($user['prenom'] . ' ' . $user['nom']); ?></h4>
            <p class="text-muted"><?php echo htmlspecialchars($user['email']); ?></p>
            <span class="badge bg-light text-dark border"><i class="fas fa-briefcase me-1" style="color: var(--primary);"></i>Gérant</span>
        </div>

        <div class="form-card">
            <h5 class="mb-3"><i class="fas fa-user-edit me-2" style="color: var(--primary);"></i>Modifier mes informations</h5>
            <form method="POST">
                <input type="hidden" name="action" value="profil">
                <div class="row">
                    <div class="col-md-6 mb-3"><label class="form-label">Nom</label><input type="text" name="nom" class="form-control" value="<?php echo htmlspecialchars($user['nom']); ?>" required></div>
                    <div class="col-md-6 mb-3"><label class="form-label">Prénom</label><input type="text" name="prenom" class="form-control" value="<?php echo htmlspecialchars($user['prenom']); ?>" required></div>
                    <div class="col-md-6 mb-3"><label class="form-label">Email</label><input type="email" class="form-control" value="<?php echo htmlspecialchars($user['email']); ?>" readonly></div>
                    <div class="col-md-6 mb-3"><label class="form-label">Rôle</label><input type="text" class="form-control" value="Gérant" readonly></div>
                </div>
                <button type="submit" class="btn btn-outline-primary"><i class="fas fa-save me-1"></i>Mettre à jour</button>
            </form>
        </div>

        <div class="form-card">
            <h5 class="mb-3"><i class="fas fa-lock me-2" style="color: var(--primary);"></i>Changer le mot de passe</h5>
            <form method="POST">
                <input type="hidden" name="action" value="mdp">
                <div class="row">
                    <div class="col-md-4 mb-3"><label class="form-label">Ancien</label><input type="password" name="ancien_mdp" class="form-control" required></div>
                    <div class="col-md-4 mb-3"><label class="form-label">Nouveau</label><input type="password" name="nouveau_mdp" class="form-control" required minlength="4"></div>
                    <div class="col-md-4 mb-3"><label class="form-label">Confirmer</label><input type="password" name="confirmer_mdp" class="form-control" required minlength="4"></div>
                </div>
                <button type="submit" class="btn btn-outline-primary"><i class="fas fa-key me-1"></i>Changer</button>
            </form>
        </div>
    </div>

    <script src="../bootstrap-5.3.8-dist/js/bootstrap.bundle.min.js"></script>
    <script src="../../../public/js/responsive.js"></script>
    <script>
        function updateThemeIcon(theme) {
            const icon = document.getElementById('theme-icon');
            if (icon) {
                if (theme === 'dark') {
                    icon.classList.replace('fa-moon', 'fa-sun');
                } else {
                    icon.classList.replace('fa-sun', 'fa-moon');
                }
            }
        }

        function toggleTheme() {
            const currentTheme = document.documentElement.getAttribute('data-theme');
            const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
            
            document.documentElement.setAttribute('data-theme', newTheme);
            localStorage.setItem('stockflow_theme', newTheme);
            updateThemeIcon(newTheme);
        }

        document.addEventListener('DOMContentLoaded', () => {
            const savedTheme = localStorage.getItem('stockflow_theme') || 'light';
            updateThemeIcon(savedTheme);
        });
    </script>
</body>
</html>