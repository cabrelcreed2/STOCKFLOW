<?php
session_start();

// On vérifie si l'utilisateur est connecté ET s'il est bien GÉRANT
if (!isset($_SESSION['utilisateur']) || $_SESSION['utilisateur']['role'] !== 'GERANT') {
    // Si ce n'est pas un gérant, on le redirige (vers la page de connexion ou son dashboard)
    header('Location: ../../index.php'); 
    exit();
}

// Inclure les contrôleurs
require_once __DIR__ . '/../../../source/controleurs/AdministrationControleur.php';

$adminCtrl = new AdministrationControleur();
$rayons = $adminCtrl->listeRayons();
$user = $_SESSION['utilisateur'];

// Traitement suppression
$message = '';
if (isset($_GET['action']) && $_GET['action'] === 'supprimer' && isset($_GET['id'])) {
    $adminCtrl->supprimerRayon($_GET['id']);
    $message = 'Rayon supprimé.';
    $rayons = $adminCtrl->listeRayons();
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>STOCKFLOW - Rayons</title>
    
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
            border-radius: 15px; padding: 20px; 
            box-shadow: 0 2px 10px var(--shadow-color); 
            transition: background-color 0.3s ease;
        }

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
            <p>Gérant</p>
        </div>
        <nav class="nav flex-column mt-3">
            <a class="nav-link" href="tableau_de_bord_gerant.php"><i class="fas fa-tachometer-alt"></i> <span>Tableau de bord</span></a>
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

        <div class="d-flex justify-content-between align-items-center mb-4">
            <h3 class="mb-0"><i class="fas fa-layer-group me-2" style="color: var(--primary);"></i>Gestion des rayons</h3>
            
            <div class="d-flex align-items-center">
                <button id="theme-toggle" class="btn btn-outline-secondary me-3 rounded-circle" style="width: 40px; height: 40px; display: flex; align-items: center; justify-content: center;">
                    <i class="fas fa-moon"></i>
                </button>
                
                <a href="rayon_ajouter.php" class="btn btn-outline-primary"><i class="fas fa-plus"></i> Ajouter un rayon</a>
            </div>
        </div>

        <div class="content-card">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Nom</th>
                            <th>Emplacement</th>
                            <th>Caissière assignée</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($rayons)): ?>
                            <?php foreach ($rayons as $r): ?>
                                <tr>
                                    <td><?php echo $r['id_rayon']; ?></td>
                                    <td><strong><?php echo htmlspecialchars($r['nom']); ?></strong></td>
                                    <td><?php echo htmlspecialchars($r['emplacement'] ?? 'Non défini'); ?></td>
                                    <td>
                                        <?php if ($r['nom_caissiere']): ?>
                                            <?php echo htmlspecialchars($r['prenom_caissiere'] . ' ' . $r['nom_caissiere']); ?>
                                        <?php else: ?>
                                            <span class="text-muted">Aucune</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <a href="rayon_modifier.php?id=<?php echo $r['id_rayon']; ?>" class="btn btn-warning btn-sm"><i class="fas fa-edit"></i></a>
                                        <a href="?action=supprimer&id=<?php echo $r['id_rayon']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('Supprimer ce rayon ?')"><i class="fas fa-trash"></i></a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5" class="text-center py-4 text-muted">Aucun rayon trouvé.</td>
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