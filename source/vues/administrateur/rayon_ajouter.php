<?php
session_start();

// Vérifier connexion
if (!isset($_SESSION['utilisateur'])) {
    header('Location: ../authentification/connexion.php');
    exit;
}

// Vérifier le rôle
if ($_SESSION['utilisateur']['role'] !== 'ADMIN') {
    header('Location: ../authentification/connexion.php');
    exit;
}

// Inclure les contrôleurs
require_once __DIR__ . '/../../../source/controleurs/AdministrationControleur.php';

$adminCtrl = new AdministrationControleur();
$caissieres = $adminCtrl->listeCaissieres();
$user = $_SESSION['utilisateur'];

$message = '';
$erreur = '';

// Traitement
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nom = $_POST['nom'] ?? '';
    $emplacement = $_POST['emplacement'] ?? '';
    $id_caissiere = $_POST['id_caissiere'] ?? '';
    
    if (empty($nom)) {
        $erreur = 'Le nom du rayon est obligatoire.';
    } else {
        $resultat = $adminCtrl->ajouterRayon($nom, $emplacement);
        
        if ($resultat) {
            // Assigner la caissière si sélectionnée
            if (!empty($id_caissiere)) {
                // Attention : Assurez-vous que $conn est accessible ou que ajouterRayon renvoie l'ID
                $adminCtrl->assignerCaissiere($conn->lastInsertId(), $id_caissiere);
            }
            $message = 'Rayon ajouté avec succès !';
        } else {
            $erreur = 'Erreur lors de l\'ajout.';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>STOCKFLOW - Ajouter rayon</title>
    
    <script>
        const savedTheme = localStorage.getItem('theme') || 'light';
        document.documentElement.setAttribute('data-theme', savedTheme);
    </script>
    
    <link href="../bootstrap-5.3.8-dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../bootstrap-5.3.8-dist/fontawesome-free-6.7.2-web/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="../../../public/css/responsive.css" rel="stylesheet">
    
    <style>
        :root { 
            --primary: #f97316; 
            --primary-dark: #ea580c; 
            
            /* Thème Clair */
            --bg-body: #f8fafc;
            --bg-surface: #ffffff;
            --text-main: #1e293b;
            --text-muted: #64748b;
            --border-color: rgba(0,0,0,0.1);
            --shadow-color: rgba(0,0,0,0.05);
        }

        [data-theme="dark"] {
            /* Thème Sombre */
            --bg-body: #0f172a;
            --bg-surface: #1e293b;
            --text-main: #f1f5f9;
            --text-muted: #94a3b8;
            --border-color: rgba(255,255,255,0.1);
            --shadow-color: rgba(0,0,0,0.3);
        }

        * { font-family: 'Poppins', sans-serif; }
        
        body { 
            background-color: var(--bg-body); 
            color: var(--text-main);
            transition: background-color 0.3s ease, color 0.3s ease;
        }
        
        .sidebar {
            background: var(--bg-surface) !important; 
            min-height: 100vh; color: var(--text-main);
            position: fixed; top: 0; left: 0; bottom: 0; width: 280px; overflow-y: auto;
            box-shadow: 2px 0 10px var(--shadow-color);
            transition: background-color 0.3s ease;
            z-index: 1000;
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
        
        .form-card { 
            background: var(--bg-surface); 
            border-radius: 15px; 
            padding: 30px; 
            box-shadow: 0 2px 10px var(--shadow-color); 
            transition: background-color 0.3s ease;
        }

        /* --- Styles des formulaires en mode sombre --- */
        [data-theme="dark"] .form-control,
        [data-theme="dark"] .form-select {
            background-color: var(--bg-body);
            border-color: var(--border-color);
            color: var(--text-main);
        }
        [data-theme="dark"] .form-control:focus,
        [data-theme="dark"] .form-select:focus {
            background-color: var(--bg-body);
            color: var(--text-main);
            border-color: var(--primary);
            box-shadow: 0 0 0 0.25rem rgba(249, 115, 22, 0.25);
        }
        [data-theme="dark"] .form-control::placeholder {
            color: var(--text-muted);
        }
        [data-theme="dark"] hr {
            border-color: var(--border-color);
            opacity: 0.2;
        }
        
        @media (max-width: 768px) { 
            .sidebar { width: 70px; } 
            .sidebar .nav-link span, .sidebar .logo p, .sidebar .logo h4 { display: none; } 
            .main-content { margin-left: 70px; } 
        }
    </style>
</head>
<body>

    <div class="sidebar">
        <div class="logo">
            <i class="fas fa-chart-line fa-2x"></i>
            <h4>STOCK<span>FLOW</span></h4>
            <p>Administrateur</p>
        </div>
        <nav class="nav flex-column mt-3">
            <a class="nav-link" href="tableau_de_bord_admin.php"><i class="fas fa-tachometer-alt"></i> <span>Tableau de bord</span></a>
            <a class="nav-link" href="utilisateur.php"><i class="fas fa-users"></i> <span>Utilisateurs</span></a>
            <a class="nav-link active" href="rayons.php"><i class="fas fa-layer-group"></i> <span>Rayons</span></a>
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
            <h3 class="mb-0"><i class="fas fa-plus-circle me-2" style="color: var(--primary);"></i>Ajouter un rayon</h3>
            
            <div class="d-flex align-items-center">
                <button id="theme-toggle" class="btn btn-outline-secondary me-3 rounded-circle" style="width: 40px; height: 40px; display: flex; align-items: center; justify-content: center;">
                    <i class="fas fa-moon"></i>
                </button>
                <a href="rayons.php" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Retour</a>
            </div>
        </div>

        <div class="form-card">
            <form method="POST" action="">
                <div class="mb-3">
                    <label class="form-label">Nom du rayon</label>
                    <input type="text" name="nom" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Emplacement</label>
                    <input type="text" name="emplacement" class="form-control" placeholder="Ex: Allée A - Gauche">
                </div>
                <div class="mb-3">
                    <label class="form-label">Caissière assignée</label>
                    <select name="id_caissiere" class="form-select">
                        <option value="">Aucune</option>
                        <?php foreach ($caissieres as $c): ?>
                            <option value="<?php echo $c['id_utilisateur']; ?>">
                                <?php echo htmlspecialchars($c['prenom'] . ' ' . $c['nom']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <hr>
                <button type="submit" class="btn btn-outline-primary"><i class="fas fa-save"></i> Enregistrer</button>
                <a href="rayons.php" class="btn btn-secondary">Annuler</a>
            </form>
        </div>
    </div>

    <script src="../bootstrap-5.3.8-dist/js/bootstrap.bundle.min.js"></script>
    <script src="../../../public/js/responsive.js"></script>
    
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const themeToggle = document.getElementById('theme-toggle');
            const htmlEl = document.documentElement;
            const icon = themeToggle.querySelector('i');

            if (htmlEl.getAttribute('data-theme') === 'dark') {
                icon.classList.remove('fa-moon');
                icon.classList.add('fa-sun');
            }

            themeToggle.addEventListener('click', () => {
                const currentTheme = htmlEl.getAttribute('data-theme');
                const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
                
                htmlEl.setAttribute('data-theme', newTheme);
                localStorage.setItem('theme', newTheme);
                
                if (newTheme === 'dark') {
                    icon.classList.remove('fa-moon');
                    icon.classList.add('fa-sun');
                } else {
                    icon.classList.remove('fa-sun');
                    icon.classList.add('fa-moon');
                }
            });
        });
    </script>
</body>
</html>