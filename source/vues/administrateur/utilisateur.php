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

// Initialiser
$adminCtrl = new AdministrationControleur();
$utilisateurs = $adminCtrl->listeUtilisateurs();
$user = $_SESSION['utilisateur'];

// Traitement activation/désactivation
$message = '';
if (isset($_GET['action']) && isset($_GET['id'])) {
    $id = $_GET['id'];
    if ($_GET['action'] === 'desactiver') {
        $adminCtrl->desactiverUtilisateur($id);
        $message = 'Utilisateur désactivé.';
    } elseif ($_GET['action'] === 'activer') {
        $adminCtrl->activerUtilisateur($id);
        $message = 'Utilisateur activé.';
    }
    // Rafraîchir la liste
    $utilisateurs = $adminCtrl->listeUtilisateurs();
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>STOCKFLOW - Utilisateurs</title>
    
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
            --table-hover: rgba(0,0,0,0.05);
        }

        [data-theme="dark"] {
            /* Thème Sombre */
            --bg-body: #0f172a;
            --bg-surface: #1e293b;
            --text-main: #f1f5f9;
            --text-muted: #94a3b8;
            --border-color: rgba(255,255,255,0.1);
            --shadow-color: rgba(0,0,0,0.3);
            --table-hover: rgba(255,255,255,0.05);
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
        
        .content-card { 
            background: var(--bg-surface); 
            border-radius: 15px; 
            padding: 20px; 
            box-shadow: 0 2px 10px var(--shadow-color); 
            transition: background-color 0.3s ease;
        }
        
        .status-badge { padding: 5px 12px; border-radius: 20px; font-size: 12px; font-weight: 600; }
        
        /* Ajustements Statuts */
        .status-actif { background: rgba(21, 87, 36, 0.2); color: #22c55e; } /* Vert plus universel */
        .status-inactif { background: rgba(114, 28, 36, 0.2); color: #ef4444; } /* Rouge plus universel */
        
        .btn-action { margin: 0 3px; padding: 5px 12px; font-size: 12px; }
        
        /* --- Styles du tableau en mode sombre --- */
        [data-theme="dark"] .table { color: var(--text-main); }
        [data-theme="dark"] .table th, 
        [data-theme="dark"] .table td { 
            background-color: var(--bg-surface); 
            border-color: var(--border-color); 
            color: var(--text-main); 
        }
        [data-theme="dark"] .table-hover tbody tr:hover td,
        [data-theme="dark"] .table-hover tbody tr:hover th {
            background-color: var(--table-hover);
        }
        [data-theme="dark"] .text-muted { color: var(--text-muted) !important; }

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
            <a class="nav-link active" href="utilisateur.php"><i class="fas fa-users"></i> <span>Utilisateurs</span></a>
            <a class="nav-link" href="rayons.php"><i class="fas fa-layer-group"></i> <span>Rayons</span></a>
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

        <div class="d-flex justify-content-between align-items-center mb-4">
            <h3 class="mb-0"><i class="fas fa-users me-2" style="color: var(--primary);"></i>Gestion des utilisateurs</h3>
            
            <div class="d-flex align-items-center">
                <button id="theme-toggle" class="btn btn-outline-secondary me-3 rounded-circle" style="width: 40px; height: 40px; display: flex; align-items: center; justify-content: center;">
                    <i class="fas fa-moon"></i>
                </button>
                
                <a href="utilisateur_ajouter.php" class="btn btn-outline-primary"><i class="fas fa-plus"></i> Ajouter</a>
            </div>
        </div>

        <div class="content-card">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Nom</th>
                            <th>Prénom</th>
                            <th>Email</th>
                            <th>Rôle</th>
                            <th>Statut</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($utilisateurs)): ?>
                            <?php foreach ($utilisateurs as $u): ?>
                                <tr>
                                    <td><?php echo $u['id_utilisateur']; ?></td>
                                    <td><?php echo htmlspecialchars($u['nom']); ?></td>
                                    <td><?php echo htmlspecialchars($u['prenom']); ?></td>
                                    <td><?php echo htmlspecialchars($u['email']); ?></td>
                                    <td><span class="badge bg-secondary"><?php echo $u['role']; ?></span></td>
                                    <td>
                                        <?php if ($u['est_actif']): ?>
                                            <span class="status-badge status-actif">Actif</span>
                                        <?php else: ?>
                                            <span class="status-badge status-inactif">Inactif</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <a href="utilisateur_modifier.php?id=<?php echo $u['id_utilisateur']; ?>" class="btn btn-warning btn-action"><i class="fas fa-edit"></i></a>
                                        <?php if ($u['est_actif']): ?>
                                            <a href="?action=desactiver&id=<?php echo $u['id_utilisateur']; ?>" class="btn btn-danger btn-action" onclick="return confirm('Désactiver cet utilisateur ?')"><i class="fas fa-ban"></i></a>
                                        <?php else: ?>
                                            <a href="?action=activer&id=<?php echo $u['id_utilisateur']; ?>" class="btn btn-success btn-action" onclick="return confirm('Activer cet utilisateur ?')"><i class="fas fa-check"></i></a>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="7" class="text-center py-4 text-muted">Aucun utilisateur trouvé.</td>
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
        document.addEventListener('DOMContentLoaded', () => {
            const themeToggle = document.getElementById('theme-toggle');
            const htmlEl = document.documentElement;
            const icon = themeToggle.querySelector('i');

            // Met à jour l'icône initiale en fonction du thème stocké
            if (htmlEl.getAttribute('data-theme') === 'dark') {
                icon.classList.remove('fa-moon');
                icon.classList.add('fa-sun');
            }

            themeToggle.addEventListener('click', () => {
                const currentTheme = htmlEl.getAttribute('data-theme');
                const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
                
                // Application du thème et sauvegarde dans le cache du navigateur
                htmlEl.setAttribute('data-theme', newTheme);
                localStorage.setItem('theme', newTheme);
                
                // Changement de l'icône
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