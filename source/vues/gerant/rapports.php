<?php
session_start();

if (!isset($_SESSION['utilisateur']) || $_SESSION['utilisateur']['role'] !== 'GESTIONNAIRE') {
    header('Location: ../authentification/connexion.php');
    exit;
}

require_once __DIR__ . '/../../../source/controleurs/RapportControleur.php';

$rapportCtrl = new RapportControleur();
$user = $_SESSION['utilisateur'];

$type = $_GET['type'] ?? '';
$date_debut = $_GET['date_debut'] ?? date('Y-m-d', strtotime('-30 days'));
$date_fin = $_GET['date_fin'] ?? date('Y-m-d');

$resultat = null;
$titre = '';

if (!empty($type)) {
    switch ($type) {
        case 'ventes':
            $resultat = $rapportCtrl->ventesParPeriode($date_debut, $date_fin);
            $titre = 'Ventes du ' . date('d/m/Y', strtotime($date_debut)) . ' au ' . date('d/m/Y', strtotime($date_fin));
            break;
        case 'top':
            $resultat = $rapportCtrl->produitsPlusVendus(10);
            $titre = 'Top 10 des produits les plus vendus';
            break;
        case 'ruptures':
            $resultat = $rapportCtrl->produitsEnAlerteMagasin();
            $titre = 'Produits en alerte stock magasin';
            break;
        case 'peremption':
            $resultat = $rapportCtrl->produitsProchesPeremptionMagasin(30);
            $titre = 'Produits proches de la péremption (30 jours)';
            break;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>STOCKFLOW - Rapports</title>
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
            --table-hover: rgba(0,0,0,0.05);
            --btn-theme-bg: #f8f9fa;
            --input-bg: #ffffff;
            --input-text: #212529;
            --report-card-border: #e2e8f0;
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
            --table-hover: rgba(255,255,255,0.05);
            --btn-theme-bg: #334155;
            --input-bg: #334155;
            --input-text: #f8fafc;
            --report-card-border: #334155;
        }

        /* APPLICATION DES VARIABLES */
        body { 
            background-color: var(--bg-body); 
            color: var(--text-main);
            transition: background-color 0.3s ease, color 0.3s ease;
        }
        
        .text-muted { color: var(--text-muted) !important; }
        
        .form-control { 
            background-color: var(--input-bg); 
            color: var(--input-text); 
            border-color: var(--border-color); 
        }
        .form-control:focus {
            background-color: var(--input-bg);
            color: var(--input-text);
        }

        .table { color: var(--text-main); }
        .table th, .table td { border-bottom-color: var(--border-color); color: var(--text-main); background: transparent; }
        .table-hover tbody tr:hover { background-color: var(--table-hover); color: var(--text-main); }
        .table-hover tbody tr:hover td { color: var(--text-main); background: transparent; }

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
        
        .content-card { 
            background: var(--bg-card); 
            border-radius: 15px; 
            padding: 20px; 
            margin-bottom: 20px; 
            box-shadow: 0 2px 10px var(--shadow-color);
            border: 1px solid var(--border-color);
            transition: background-color 0.3s;
        }
        
        .report-card { 
            background: var(--bg-card); 
            border-radius: 15px; 
            padding: 20px; 
            text-align: center; 
            cursor: pointer; 
            transition: all 0.3s; 
            border: 2px solid var(--report-card-border); 
            height: 100%; 
            text-decoration: none; 
            color: var(--text-main); 
            display: block; 
        }
        .report-card:hover { border-color: var(--primary); transform: translateY(-5px); color: var(--text-main); }
        .report-card.border-primary { border-color: var(--primary) !important; }
        .report-icon { font-size: 40px; color: var(--primary); margin-bottom: 15px; }
        
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
            <a class="nav-link active" href="rapports.php"><i class="fas fa-chart-line"></i> <span>Rapports</span></a>
            <a class="nav-link" href="profil_gerant.php"><i class="fas fa-user"></i> <span>Profil</span></a>
            <a class="nav-link" href="../../../deconnexion.php"><i class="fas fa-sign-out-alt"></i> <span>Déconnexion</span></a>
        </nav>
    </div>

    <div class="main-content">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h3><i class="fas fa-chart-line me-2" style="color: var(--primary);"></i>Rapports et analyses</h3>
            <div class="d-flex align-items-center">
                <button class="btn btn-theme-toggle rounded-circle" onclick="toggleTheme()" title="Basculer le thème">
                    <i id="theme-icon" class="fas fa-moon"></i>
                </button>
            </div>
        </div>

        <div class="row g-4 mb-4">
            <div class="col-md-3"><a href="?type=ventes" class="report-card <?php echo $type==='ventes'?'border-primary':''; ?>"><div class="report-icon"><i class="fas fa-chart-line"></i></div><h6>Ventes par période</h6></a></div>
            <div class="col-md-3"><a href="?type=top" class="report-card <?php echo $type==='top'?'border-primary':''; ?>"><div class="report-icon"><i class="fas fa-star"></i></div><h6>Top produits</h6></a></div>
            <div class="col-md-3"><a href="?type=ruptures" class="report-card <?php echo $type==='ruptures'?'border-primary':''; ?>"><div class="report-icon"><i class="fas fa-exclamation-triangle"></i></div><h6>Ruptures stock</h6></a></div>
            <div class="col-md-3"><a href="?type=peremption" class="report-card <?php echo $type==='peremption'?'border-primary':''; ?>"><div class="report-icon"><i class="fas fa-calendar-times"></i></div><h6>Proches péremption</h6></a></div>
        </div>

        <?php if (!empty($type)): ?>
        <div class="content-card">
            <form method="GET" class="row mb-3">
                <input type="hidden" name="type" value="<?php echo $type; ?>">
                <div class="col-md-4"><label class="form-label">Date début</label><input type="date" name="date_debut" class="form-control" value="<?php echo $date_debut; ?>"></div>
                <div class="col-md-4"><label class="form-label">Date fin</label><input type="date" name="date_fin" class="form-control" value="<?php echo $date_fin; ?>"></div>
                <div class="col-md-4 d-flex align-items-end"><button type="submit" class="btn btn-outline-primary w-100">Générer</button></div>
            </form>
        </div>

                <div class="content-card" id="rapport-content">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5 class="mb-0"><?php echo $titre; ?></h5>
                <?php if (!empty($resultat)): ?>
                    <button class="btn btn-outline-primary btn-sm" onclick="exporterPDF()">
                        <i class="fas fa-file-pdf me-1"></i> Exporter en PDF
                    </button>
                <?php endif; ?>
            </div>
            <?php if (!empty($resultat)): ?>
                <div class="table-responsive" id="rapport-table">
                    <table class="table table-hover">
                        <?php if ($type === 'ventes'): ?>
                            <thead><tr><th>Période</th><th>Nb ventes</th><th>Montant</th></tr></thead>
                            <tbody><tr><td><?php echo $titre; ?></td><td><?php echo $resultat['nb_ventes']; ?></td><td><?php echo number_format($resultat['montant_total'], 0, ',', ' '); ?> FCFA</td></tr></tbody>
                        <?php elseif ($type === 'top'): ?>
                            <thead><tr><th>Produit</th><th>Quantité vendue</th><th>Chiffre d'affaires</th></tr></thead>
                            <tbody><?php foreach ($resultat as $r): ?><tr><td><?php echo htmlspecialchars($r['nom']); ?></td><td><?php echo $r['total_vendu']; ?></td><td><?php echo number_format($r['chiffre_affaires'], 0, ',', ' '); ?> FCFA</td></tr><?php endforeach; ?></tbody>
                        <?php elseif ($type === 'ruptures'): ?>
                            <thead><tr><th>Produit</th><th>Stock</th><th>Seuil</th><th>Péremption</th></tr></thead>
                            <tbody><?php foreach ($resultat as $r): ?><tr><td><?php echo htmlspecialchars($r['nom_produit']); ?></td><td><?php echo $r['quantite']; ?></td><td><?php echo $r['seuil_alerte_magasin']; ?></td><td><?php echo $r['date_peremption'] ? date('d/m/Y', strtotime($r['date_peremption'])) : 'N/A'; ?></td></tr><?php endforeach; ?></tbody>
                        <?php elseif ($type === 'peremption'): ?>
                            <thead><tr><th>Produit</th><th>Stock</th><th>Jours restants</th><th>Péremption</th></tr></thead>
                            <tbody><?php foreach ($resultat as $r): ?><tr><td><?php echo htmlspecialchars($r['nom_produit']); ?></td><td><?php echo $r['quantite']; ?></td><td><?php echo $r['jours_restants'] ?? 'N/A'; ?></td><td><?php echo $r['date_peremption'] ? date('d/m/Y', strtotime($r['date_peremption'])) : 'N/A'; ?></td></tr><?php endforeach; ?></tbody>
                        <?php endif; ?>
                    </table>
                </div>
            <?php else: ?>
                <p class="text-muted mb-0">Aucune donnée.</p>
            <?php endif; ?>
        </div>
        <?php else: ?>
        <div class="content-card text-center text-muted py-5">
            <i class="fas fa-chart-line fa-3x mb-3"></i>
            <h5>Aucun rapport généré</h5>
            <p class="mb-0">Sélectionnez un type de rapport ci-dessus</p>
        </div>
        <?php endif; ?>
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

    <script>
    function exporterPDF() {
    // Récupérer le contenu du tableau
    const rapportContent = document.getElementById('rapport-content');
    const titre = rapportContent.querySelector('h5').innerHTML;
    const tableau = rapportContent.querySelector('table').cloneNode(true);
    
    // Créer la fenêtre d'impression
    const printWindow = window.open('', '_blank', 'width=900,height=700');
    printWindow.document.write(`
        <!DOCTYPE html>
        <html lang="fr">
        <head>
            <meta charset="UTF-8">
            <title>Rapport - STOCKFLOW</title>
            <style>
                body { 
                    font-family: Arial, sans-serif; 
                    padding: 30px; 
                    color: #1e293b;
                }
                h5 { 
                    color: #f97316; 
                    font-size: 18px;
                    margin-bottom: 20px; 
                }
                table { 
                    width: 100%; 
                    border-collapse: collapse; 
                    margin-top: 20px; 
                }
                th, td { 
                    border: 1px solid #dee2e6; 
                    padding: 10px; 
                    text-align: left; 
                }
                th { 
                    background-color: #f8f9fa; 
                    font-weight: 600; 
                }
                .header { 
                    text-align: center; 
                    margin-bottom: 30px; 
                    border-bottom: 2px solid #f97316; 
                    padding-bottom: 15px; 
                }
                .header h2 { 
                    color: #f97316; 
                    margin: 0;
                }
                .header p {
                    color: #6c757d;
                    margin: 5px 0 0 0;
                }
                .footer { 
                    text-align: center; 
                    margin-top: 30px; 
                    font-size: 12px; 
                    color: #6c757d; 
                    border-top: 1px solid #dee2e6;
                    padding-top: 10px;
                }
                @media print { 
                    body { padding: 0; } 
                }
            </style>
        </head>
        <body>
            <div class="header">
                <h2>🏪 STOCKFLOW</h2>
                <p>Rapport généré le <?php echo date('d/m/Y à H:i'); ?></p>
            </div>
            <h5>${titre}</h5>
            ${tableau.outerHTML}
            // <div class="footer">
            //     <p>© <?php echo date('Y'); ?> STOCKFLOW - Gestion de Stock Supermarché</p>
            // </div>
        </body>
        </html>
    `);
    printWindow.document.close();
    
    // Attendre le chargement puis imprimer
    setTimeout(() => {
        printWindow.print();
    }, 500);
}
</script>
</body>
</html>