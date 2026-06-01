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

$id_commande = $_GET['id'] ?? 0;
$commande = $commandeModel->getById($id_commande);

if (!$commande) {
    header('Location: commandes.php');
    exit;
}

$lignes = $ligneModel->getByCommande($id_commande);
$total = $ligneModel->getTotal($id_commande);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>STOCKFLOW - Détail commande</title>
    <link href="../bootstrap-5.3.8-dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../bootstrap-5.3.8-dist/fontawesome-free-6.7.2-web/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    
    <script>
        const savedTheme = localStorage.getItem('stockflow_theme') || 'light';
        document.documentElement.setAttribute('data-theme', savedTheme);
    </script>

    <style>
        * { font-family: 'Poppins', sans-serif; }
        
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
        }
        
        [data-theme="dark"] {
            --bg-body: #0f172a;
            --bg-sidebar: #1e293b;
            --bg-card: #1e293b;
            --text-main: #f8fafc;
            --text-muted: #94a3b8;
            --border-color: rgba(255,255,255,0.1);
            --shadow-color: rgba(0,0,0,0.2);
            --btn-theme-bg: #334155;
        }

        body { background-color: var(--bg-body); color: var(--text-main); transition: 0.3s; }
        
        .sidebar {
            background: var(--bg-sidebar) !important; color: var(--text-main);
            position: fixed; top: 0; left: 0; bottom: 0; width: 280px; overflow-y: auto;
            box-shadow: 2px 0 10px var(--shadow-color); border-right: 1px solid var(--border-color);
        }
        .sidebar .logo { text-align: center; padding: 25px 0; border-bottom: 1px solid var(--border-color); }
        .sidebar .logo h4 { font-weight: 800; color: var(--primary); }
        .sidebar .logo i { color: var(--primary); background: rgba(249,115,22,0.1); padding: 12px; border-radius: 15px; }
        .sidebar .logo p { color: var(--text-muted); }
        .sidebar .nav-link { color: var(--text-muted) !important; padding: 12px 25px; margin: 5px 0; border-radius: 10px; transition: 0.3s; }
        .sidebar .nav-link:hover, .sidebar .nav-link.active { background: var(--primary) !important; color: white !important; }
        .sidebar .nav-link i { width: 28px; margin-right: 12px; color: var(--primary); }
        .sidebar .nav-link:hover i, .sidebar .nav-link.active i { color: white; }
        
        .main-content { margin-left: 280px; padding: 20px; }
        .content-card { background: var(--bg-card); border-radius: 15px; padding: 25px; box-shadow: 0 2px 10px var(--shadow-color); border: 1px solid var(--border-color); margin-bottom: 20px; }
        
        .table { color: var(--text-main); }
        .table th, .table td { border-bottom-color: var(--border-color); color: var(--text-main); background: transparent; }
        
        .info-badge { display: inline-block; padding: 8px 16px; border-radius: 20px; font-size: 13px; font-weight: 600; }
        
        .btn-theme-toggle { background-color: var(--btn-theme-bg); color: var(--text-main); border: 1px solid var(--border-color); width: 40px; height: 40px; }
        
        @media (max-width: 768px) { .sidebar { width: 70px; } .sidebar .nav-link span, .sidebar .logo p, .sidebar .logo h4 { display: none; } .main-content { margin-left: 70px; } }
        
        @media print {
            .sidebar, .btn, .no-print { display: none !important; }
            .main-content { margin-left: 0 !important; }
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
            <a class="nav-link active" href="commandes.php"><i class="fas fa-shopping-cart"></i> <span>Commandes</span></a>
            <a class="nav-link" href="commandes_historique.php"><i class="fas fa-history"></i> <span>Historique</span></a>
            <a class="nav-link" href="alertes.php"><i class="fas fa-bell"></i> <span>Alertes</span></a>
            <a class="nav-link" href="rapports.php"><i class="fas fa-chart-line"></i> <span>Rapports</span></a>
            <a class="nav-link" href="profil_gerant.php"><i class="fas fa-user"></i> <span>Profil</span></a>
            <a class="nav-link" href="../../../deconnexion.php"><i class="fas fa-sign-out-alt"></i> <span>Déconnexion</span></a>
        </nav>
    </div>

    <div class="main-content">
        
        <div class="d-flex justify-content-between align-items-center mb-4 no-print">
            <h3 class="m-0"><i class="fas fa-file-invoice me-2" style="color: var(--primary);"></i>Détail commande</h3>
            <div class="d-flex gap-2">
                <button class="btn btn-theme-toggle rounded-circle" onclick="toggleTheme()" title="Basculer le thème">
                    <i id="theme-icon" class="fas fa-moon"></i>
                </button>
                <button class="btn btn-outline-primary" onclick="window.print()"><i class="fas fa-print me-1"></i> Imprimer</button>
                <a href="commandes.php" class="btn btn-outline-secondary"><i class="fas fa-arrow-left me-1"></i> Retour</a>
            </div>
        </div>

        <!-- Infos commande -->
        <div class="content-card">
            <div class="row">
                <div class="col-md-6">
                    <h5 class="mb-3" style="color: var(--primary);">Informations commande</h5>
                    <table class="table table-borderless">
                        <tr><td class="text-muted" width="180">N° commande</td><td><strong><?php echo htmlspecialchars($commande['numero_commande']); ?></strong></td></tr>
                        <tr><td class="text-muted">Date</td><td><?php echo date('d/m/Y à H:i', strtotime($commande['date_commande'])); ?></td></tr>
                        <tr><td class="text-muted">Statut</td><td>
                            <?php 
                                $statutClass = $commande['statut'] === 'LIVREE' ? 'bg-success' : ($commande['statut'] === 'ENVOYEE' ? 'bg-primary' : ($commande['statut'] === 'ANNULEE' ? 'bg-danger' : 'bg-warning text-dark'));
                            ?>
                            <span class="info-badge <?php echo $statutClass; ?> text-white"><?php echo $commande['statut']; ?></span>
                        </td></tr>
                        <tr><td class="text-muted">Gérant</td><td><?php echo htmlspecialchars($commande['prenom_gestionnaire'] . ' ' . $commande['nom_gestionnaire']); ?></td></tr>
                    </table>
                </div>
                <div class="col-md-6">
                    <h5 class="mb-3" style="color: var(--primary);">Informations fournisseur</h5>
                    <table class="table table-borderless">
                        <tr><td class="text-muted" width="180">Nom</td><td><strong><?php echo htmlspecialchars($commande['nom_fournisseur']); ?></strong></td></tr>
                        <tr><td class="text-muted">Email</td><td><?php echo htmlspecialchars($commande['email_fournisseur'] ?? 'N/A'); ?></td></tr>
                        <tr><td class="text-muted">Téléphone</td><td><?php echo htmlspecialchars($commande['tel_fournisseur'] ?? 'N/A'); ?></td></tr>
                    </table>
                </div>
            </div>
        </div>

        <!-- Produits commandés -->
        <div class="content-card">
            <h5 class="mb-3" style="color: var(--primary);">Produits commandés</h5>
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Produit</th>
                            <th>Code-barre</th>
                            <th>Quantité</th>
                            <th>Prix unitaire</th>
                            <th>Sous-total</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($lignes)): ?>
                            <?php foreach ($lignes as $l): ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($l['nom_produit']); ?></strong></td>
                                    <td><code><?php echo htmlspecialchars($l['code_barre'] ?? 'N/A'); ?></code></td>
                                    <td><?php echo $l['quantite']; ?></td>
                                    <td><?php echo number_format($l['prix_unitaire'], 0, ',', ' '); ?> FCFA</td>
                                    <td><strong><?php echo number_format($l['sous_total'], 0, ',', ' '); ?> FCFA</strong></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="5" class="text-center py-4 text-muted">Aucun produit dans cette commande.</td></tr>
                        <?php endif; ?>
                    </tbody>
                    <?php if (!empty($lignes)): ?>
                        <tfoot>
                            <tr style="border-top: 2px solid var(--primary);">
                                <td colspan="4" class="text-end fw-bold">TOTAL</td>
                                <td><strong style="font-size: 18px; color: var(--primary);"><?php echo number_format($total, 0, ',', ' '); ?> FCFA</strong></td>
                            </tr>
                        </tfoot>
                    <?php endif; ?>
                </table>
            </div>
        </div>

        <!-- Actions -->
        <?php if ($commande['statut'] === 'EN_ATTENTE' || $commande['statut'] === 'ENVOYEE'): ?>
        <div class="content-card no-print">
            <h5 class="mb-3" style="color: var(--primary);">Actions</h5>
            <div class="d-flex gap-2">
                <?php if ($commande['statut'] === 'EN_ATTENTE'): ?>
                    <a href="commandes.php?action=annuler&id=<?php echo $commande['id_commande']; ?>" class="btn btn-danger" onclick="return confirm('Annuler cette commande ?')">
                        <i class="fas fa-times me-1"></i> Annuler la commande
                    </a>
                <?php endif; ?>
                <?php if ($commande['statut'] === 'ENVOYEE'): ?>
                    <a href="commandes.php?action=livrer&id=<?php echo $commande['id_commande']; ?>" class="btn btn-success" onclick="return confirm('Marquer comme livrée ?')">
                        <i class="fas fa-check me-1"></i> Marquer comme livrée
                    </a>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <script src="../bootstrap-5.3.8-dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function toggleTheme() {
            const current = document.documentElement.getAttribute('data-theme');
            const next = current === 'dark' ? 'light' : 'dark';
            document.documentElement.setAttribute('data-theme', next);
            localStorage.setItem('stockflow_theme', next);
            document.getElementById('theme-icon').className = next === 'dark' ? 'fas fa-sun' : 'fas fa-moon';
        }
        document.addEventListener('DOMContentLoaded', () => {
            const saved = localStorage.getItem('stockflow_theme') || 'light';
            document.getElementById('theme-icon').className = saved === 'dark' ? 'fas fa-sun' : 'fas fa-moon';
        });
    </script>
</body>
</html>