<?php
session_start();

if (!isset($_SESSION['utilisateur']) || $_SESSION['utilisateur']['role'] !== 'GESTIONNAIRE') {
    header('Location: ../authentification/connexion.php');
    exit;
}

require_once __DIR__ . '/../../../source/modeles/BaseDeDonnees.php';
require_once __DIR__ . '/../../../source/modeles/CommandeFournisseur.php';
require_once __DIR__ . '/../../../source/modeles/LigneCommande.php';

$db = new Database();
$conn = $db->connect();
$commandeModel = new CommandeFournisseur($conn);
$ligneModel = new LigneCommande($conn);
$user = $_SESSION['utilisateur'];

// Filtres
$statut = $_GET['statut'] ?? '';
$recherche = $_GET['recherche'] ?? '';

if (!empty($recherche)) {
    $commandes = $commandeModel->rechercher($recherche);
} elseif (!empty($statut)) {
    $commandes = $commandeModel->getByStatut($statut);
} else {
    $commandes = $commandeModel->getAll();
}

// Si on filtre par statut ET recherche
if (!empty($recherche) && !empty($statut)) {
    $toutes = $commandeModel->rechercher($recherche);
    $commandes = array_filter($toutes, function($c) use ($statut) {
        return $c['statut'] === $statut;
    });
}

// Stats
$totalCmd = count($commandes);
$totalLivrees = 0;
$montantTotal = 0;
foreach ($commandes as $c) {
    $montantTotal += $ligneModel->getTotal($c['id_commande']);
    if ($c['statut'] === 'LIVREE') $totalLivrees++;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>STOCKFLOW - Historique commandes</title>
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
        
        :root { 
            --primary: #f97316; 
            --bg-body: #f8fafc; --bg-sidebar: #ffffff; --bg-card: #ffffff;
            --text-main: #1e293b; --text-muted: #64748b;
            --border-color: rgba(0,0,0,0.1); --shadow-color: rgba(0,0,0,0.05);
            --input-bg: #ffffff; --input-text: #212529; --table-hover: rgba(0,0,0,0.05);
            --btn-theme-bg: #f8f9fa; --bg-filter: #f8fafc;
        }
        
        [data-theme="dark"] {
            --bg-body: #0f172a; --bg-sidebar: #1e293b; --bg-card: #1e293b;
            --text-main: #f8fafc; --text-muted: #94a3b8;
            --border-color: rgba(255,255,255,0.1); --shadow-color: rgba(0,0,0,0.2);
            --input-bg: #334155; --input-text: #f8fafc; --table-hover: rgba(255,255,255,0.05);
            --btn-theme-bg: #334155; --bg-filter: #0f172a;
        }

        body { background-color: var(--bg-body); color: var(--text-main); transition: background-color 0.3s ease, color 0.3s ease; }
        .text-muted { color: var(--text-muted) !important; }
        .form-select, .form-control { background-color: var(--input-bg); color: var(--input-text); border-color: var(--border-color); }
        .form-select:focus, .form-control:focus { background-color: var(--input-bg); color: var(--input-text); }
        .table { color: var(--text-main); }
        .table th, .table td { border-bottom-color: var(--border-color); color: var(--text-main); background: transparent; }
        .table-hover tbody tr:hover { background-color: var(--table-hover); color: var(--text-main); }
        .table-hover tbody tr:hover td { color: var(--text-main); background: transparent; }

        .sidebar {
            background: var(--bg-sidebar) !important; min-height: 100vh; color: var(--text-main);
            position: fixed; top: 0; left: 0; bottom: 0; width: 280px; overflow-y: auto;
            box-shadow: 2px 0 10px var(--shadow-color); border-right: 1px solid var(--border-color);
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
        .content-card { 
            background: var(--bg-card); border-radius: 15px; padding: 20px; 
            box-shadow: 0 2px 10px var(--shadow-color); border: 1px solid var(--border-color);
            transition: background-color 0.3s;
        }
        .filter-bar { 
            background: var(--bg-filter); border-radius: 15px; padding: 15px; margin-bottom: 20px; 
            border: 1px solid var(--border-color); transition: background-color 0.3s;
        }
        .btn-theme-toggle {
            background-color: var(--btn-theme-bg); color: var(--text-main);
            border: 1px solid var(--border-color); width: 40px; height: 40px;
            display: inline-flex; justify-content: center; align-items: center;
        }
        .dropdown-menu { background-color: var(--bg-card); border-color: var(--border-color); }
        .dropdown-item { color: var(--text-main); }
        .dropdown-item:hover { background-color: var(--table-hover); color: var(--text-main); }
        .dropdown-header { color: var(--text-muted); }
        
        @media (max-width: 768px) { 
            .sidebar { width: 70px; } 
            .sidebar .nav-link span, .sidebar .logo p, .sidebar .logo h4 { display: none; } 
            .main-content { margin-left: 70px; } 
        }
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
            <a class="nav-link active" href="commandes_historique.php"><i class="fas fa-history"></i> <span>Historique</span></a>
            <a class="nav-link" href="alertes.php"><i class="fas fa-bell"></i> <span>Alertes</span></a>
            <a class="nav-link" href="rapports.php"><i class="fas fa-chart-line"></i> <span>Rapports</span></a>
            <a class="nav-link" href="profil_gerant.php"><i class="fas fa-user"></i> <span>Profil</span></a>
            <a class="nav-link" href="../../../deconnexion.php"><i class="fas fa-sign-out-alt"></i> <span>Déconnexion</span></a>
        </nav>
    </div>

    <div class="main-content">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h3><i class="fas fa-history me-2" style="color: var(--primary);"></i>Historique des commandes</h3>
            <div class="d-flex align-items-center">
                <button class="btn btn-theme-toggle rounded-circle me-3" onclick="toggleTheme()" title="Basculer le thème">
                    <i id="theme-icon" class="fas fa-moon"></i>
                </button>
                <a href="commande_passer.php" class="btn btn-outline-primary"><i class="fas fa-plus"></i> Nouvelle commande</a>
            </div>
        </div>

        <div class="filter-bar">
            <form method="GET" class="row align-items-end">
                <div class="col-md-6 mb-2">
                    <label class="form-label">Recherche par numéro</label>
                    <input type="text" name="recherche" class="form-control" placeholder="N° commande..." value="<?php echo htmlspecialchars($_GET['recherche'] ?? ''); ?>">
                </div>
                <div class="col-md-3 mb-2">
                    <div class="dropdown w-100">
                        <?php
                        $boutonTexte = '<i class="fas fa-filter me-1"></i> Filtrer';
                        if ($statut === 'EN_ATTENTE') $boutonTexte = '<i class="fas fa-filter me-1"></i> 🟡 En attente';
                        elseif ($statut === 'ENVOYEE') $boutonTexte = '<i class="fas fa-filter me-1"></i> 🟢 Envoyée';
                        elseif ($statut === 'LIVREE') $boutonTexte = '<i class="fas fa-filter me-1"></i> ✅ Livrée';
                        elseif ($statut === 'ANNULEE') $boutonTexte = '<i class="fas fa-filter me-1"></i> ❌ Annulée';
                        ?>
                        <button class="btn btn-outline-primary dropdown-toggle w-100" type="button" data-bs-toggle="dropdown">
                            <?php echo $boutonTexte; ?>
                        </button>
                        <ul class="dropdown-menu w-100">
                            <li><a class="dropdown-item" href="?statut=">📋 Tous les statuts</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="?statut=EN_ATTENTE">🟡 En attente</a></li>
                            <li><a class="dropdown-item" href="?statut=ENVOYEE">🟢 Envoyée</a></li>
                            <li><a class="dropdown-item" href="?statut=LIVREE">✅ Livrée</a></li>
                            <li><a class="dropdown-item" href="?statut=ANNULEE">❌ Annulée</a></li>
                        </ul>
                    </div>
                </div>

                <div class="col-md-3 mb-2">
                    <a href="commandes_historique.php" class="btn btn-outline-secondary w-100"><i class="fas fa-times me-1"></i>Réinitialiser</a>
                </div>
            </form>
        </div>

        <div class="content-card">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead><tr><th>N° commande</th><th>Date</th><th>Fournisseur</th><th>Statut</th><th>Montant</th></tr></thead>
                    <tbody>
                        <?php foreach ($commandes as $c): 
                            $total = $ligneModel->getTotal($c['id_commande']);
                            $statutClass = $c['statut'] === 'LIVREE' ? 'bg-success' : ($c['statut'] === 'ENVOYEE' ? 'bg-primary' : ($c['statut'] === 'ANNULEE' ? 'bg-danger' : 'bg-warning text-dark'));
                        ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($c['numero_commande']); ?></strong></td>
                                <td><?php echo date('d/m/Y', strtotime($c['date_commande'])); ?></td>
                                <td><?php echo htmlspecialchars($c['nom_fournisseur']); ?></td>
                                <td><span class="badge <?php echo $statutClass; ?>"><?php echo $c['statut']; ?></span></td>
                                <td><?php echo number_format($total, 0, ',', ' '); ?> FCFA</td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (empty($commandes)): ?>
                            <tr><td colspan="5" class="text-center py-4 text-muted">Aucune commande.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <div class="row mt-4 pt-3 border-top" style="border-color: var(--border-color) !important;">
                <div class="col-md-4"><div class="alert alert-info mb-0">Total : <strong><?php echo $totalCmd; ?></strong></div></div>
                <div class="col-md-4"><div class="alert alert-success mb-0">Livrées : <strong><?php echo $totalLivrees; ?></strong></div></div>
                <div class="col-md-4"><div class="alert alert-warning mb-0">Montant : <strong><?php echo number_format($montantTotal, 0, ',', ' '); ?> FCFA</strong></div></div>
            </div>
        </div>
    </div>

    <script src="../bootstrap-5.3.8-dist/js/bootstrap.bundle.min.js"></script>
    <script src="../../../public/js/responsive.js"></script>
    <script>
        function updateThemeIcon(theme) {
            const icon = document.getElementById('theme-icon');
            if (icon) {
                if (theme === 'dark') { icon.classList.replace('fa-moon', 'fa-sun'); }
                else { icon.classList.replace('fa-sun', 'fa-moon'); }
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