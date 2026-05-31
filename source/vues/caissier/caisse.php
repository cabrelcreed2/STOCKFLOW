<?php
session_start();

// Vérifier connexion
if (!isset($_SESSION['utilisateur'])) {
    header('Location: ../authentification/connexion.php');
    exit;
}

// Vérifier le rôle
if ($_SESSION['utilisateur']['role'] !== 'CAISSIERE') {
    header('Location: ../authentification/connexion.php');
    exit;
}

// Inclure les contrôleurs et modèles
require_once __DIR__ . '/../../../source/controleurs/VenteControleur.php';
require_once __DIR__ . '/../../../source/modeles/BaseDeDonnees.php';
require_once __DIR__ . '/../../../source/modeles/StockRayon.php';
require_once __DIR__ . '/../../../source/modeles/Produit.php';

// Connexion BDD
$db = new Database();
$conn = $db->connect();

// Récupérer le rayon de la caissière
$user = $_SESSION['utilisateur'];
$sql = "SELECT * FROM rayon WHERE id_caissiere = :id";
$stmt = $conn->prepare($sql);
$stmt->execute(['id' => $user['id_utilisateur']]);
$rayon = $stmt->fetch();

// Récupérer les produits du rayon
$stockRayonModel = new StockRayon($conn);
$produitsRayon = [];
if ($rayon) {
    $produitsRayon = $stockRayonModel->getByRayon($rayon['id_rayon']);
}

// Traitement de la vente
$message = '';
$erreur = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'vendre') {
    error_log("POST reçu: " . print_r($_POST, true));
    
    $produits = json_decode($_POST['produits'], true);
    $mode_paiement = $_POST['mode_paiement'] ?? '';
    
    if ($produits === null && $_POST['produits'] !== '') {
        error_log("Erreur JSON: " . json_last_error_msg());
        $erreur = "Erreur lors du traitement des données. JSON invalide.";
    } elseif (empty($produits)) {
        $erreur = "Le panier est vide. Veuillez ajouter des produits.";
    } else {
        error_log("Produits décodés: " . print_r($produits, true));
        error_log("Caissière ID: " . $user['id_utilisateur']);
        
        $venteCtrl = new VenteControleur();
        $resultat = $venteCtrl->effectuerVente($produits, $mode_paiement, $user['id_utilisateur']);
        
        error_log("Résultat vente: " . print_r($resultat, true));
        
        if (is_array($resultat) && isset($resultat['succes'])) {
            $message = "Vente n°" . $resultat['id_vente'] . " enregistrée ! Total : " . number_format($resultat['total'], 0, ',', ' ') . " FCFA";
        } else {
            $erreur = $resultat;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>STOCKFLOW - Caisse</title>
    
    <!-- Bootstrap CSS -->
    <link href="../bootstrap-5.3.8-dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="../bootstrap-5.3.8-dist/fontawesome-free-6.7.2-web/css/all.min.css">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <!-- Responsive CSS -->
    <link href="../../../public/css/responsive.css" rel="stylesheet">
    
    <script>
        if (localStorage.getItem('stockflow_theme') === 'dark' || (!localStorage.getItem('stockflow_theme') && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
            document.documentElement.setAttribute('data-theme', 'dark');
        }
    </script>

    <style>
        :root {
            --primary: #f97316;
            --primary-dark: #ea580c;
            --bg-color: #f8fafc;
            --surface-color: #ffffff;
            --text-main: #1e293b;
            --text-muted: #64748b;
            --border-color: rgba(0, 0, 0, 0.08);
            --scan-bg: #f8fafc;
            --row-hover: #fff7ed;
            --input-bg: #ffffff;
        }

        [data-theme="dark"] {
            --bg-color: #0f172a;
            --surface-color: #1e293b;
            --text-main: #f8fafc;
            --text-muted: #94a3b8;
            --border-color: rgba(255, 255, 255, 0.08);
            --scan-bg: rgba(255, 255, 255, 0.02);
            --row-hover: rgba(249, 115, 22, 0.05);
            --input-bg: #0f172a;
        }

        * { font-family: 'Poppins', sans-serif; }
        body { background-color: var(--bg-color); color: var(--text-main); height: 100vh; overflow: hidden; transition: background 0.3s, color 0.3s; }
        
        .sidebar {
            background: var(--surface-color) !important; min-height: 100vh; color: var(--text-main);
            position: fixed; top: 0; left: 0; bottom: 0; width: 280px; overflow-y: auto;
            box-shadow: 2px 0 10px rgba(0,0,0,0.02); border-right: 1px solid var(--border-color); z-index: 1000;
        }
        .sidebar .logo { text-align: center; padding: 25px 0; border-bottom: 1px solid var(--border-color); }
        .sidebar .logo h4 { font-weight: 800; color: var(--primary); margin-bottom: 0; }
        .sidebar .logo i { color: var(--primary); background: rgba(249,115,22,0.1); padding: 12px; border-radius: 15px; }
        .sidebar .logo p { color: var(--text-muted); margin-top: 5px; margin-bottom: 0; }
        .sidebar .nav-link {
            color: var(--text-muted) !important; padding: 12px 25px; margin: 5px 0;
            border-radius: 10px; transition: all 0.3s ease;
        }
        .sidebar .nav-link:hover { background: var(--primary) !important; color: white !important; transform: translateX(5px); }
        .sidebar .nav-link.active { background: var(--primary) !important; color: white !important; }
        .sidebar .nav-link i { width: 28px; margin-right: 12px; color: var(--primary); }
        .sidebar .nav-link:hover i, .sidebar .nav-link.active i { color: white; }
        
        .main-content { margin-left: 280px; padding: 20px; height: 100vh; display: flex; flex-direction: column; }
        .top-bar { background: var(--surface-color); border-radius: 15px; padding: 15px 20px; margin-bottom: 20px; border: 1px solid var(--border-color); }
        
        .caisse-container { display: flex; gap: 20px; flex: 1; min-height: 0; }
        .scan-section { flex: 1; background: var(--surface-color); border-radius: 20px; padding: 20px; display: flex; flex-direction: column; overflow: hidden; border: 1px solid var(--border-color); }
        .ticket-section { width: 400px; background: var(--surface-color); border-radius: 20px; display: flex; flex-direction: column; overflow: hidden; border: 1px solid var(--border-color); }
        
        .scan-area { background: var(--scan-bg); border-radius: 15px; padding: 15px; text-align: center; border: 2px dashed var(--border-color); margin-bottom: 20px; }
        .scan-input { font-size: 16px; padding: 12px; background-color: var(--input-bg) !important; color: var(--text-main) !important; border: 1px solid var(--border-color); }
        .scan-input:focus { border-color: var(--primary); box-shadow: 0 0 0 0.25rem rgba(249, 115, 22, 0.15); }
        
        .product-item { display: flex; justify-content: space-between; align-items: center; padding: 12px; border-bottom: 1px solid var(--border-color); cursor: pointer; transition: all 0.2s; }
        .product-item:hover { background: var(--row-hover); transform: translateX(3px); }
        
        .ticket-header { background: linear-gradient(135deg, #f97316, #ea580c); color: white; padding: 18px; text-align: center; }
        .ticket-items { flex: 1; overflow-y: auto; padding: 15px; }
        .ticket-item { display: flex; justify-content: space-between; align-items: center; padding: 12px 0; border-bottom: 1px solid var(--border-color); font-size: 14px; }
        .ticket-total { background: var(--scan-bg); padding: 15px; border-top: 1px solid var(--border-color); }
        .total-grand { font-size: 22px; color: var(--primary); font-weight: 700; border-top: 2px dashed var(--border-color); margin-top: 10px; padding-top: 10px; }
        .ticket-actions { padding: 15px; display: flex; gap: 10px; border-top: 1px solid var(--border-color); background: var(--surface-color); }
        
        .btn-payment { background: linear-gradient(135deg, #22c55e, #16a34a); border: none; flex: 2; color: white; font-weight: 600; padding: 12px; border-radius: 12px; }
        .btn-payment:hover { filter: brightness(1.1); }
        .btn-clear { background: rgba(239, 68, 68, 0.1); border: 1px solid rgba(239, 68, 68, 0.2); width: 50px; color: #ef4444; border-radius: 12px; }
        .btn-clear:hover { background: #ef4444; color: white; }
        
        .item-qty-btn { background: var(--bg-color); border: 1px solid var(--border-color); color: var(--text-main); border-radius: 6px; width: 26px; height: 26px; font-weight: bold; line-height: 1; }
        .item-qty-btn:hover { background: var(--primary); color: white; border-color: var(--primary); }
        .item-remove { color: #ef4444; cursor: pointer; margin-left: 10px; transition: transform 0.1s; }
        .item-remove:hover { transform: scale(1.15); }
        .empty-cart { text-align: center; padding: 60px 20px; color: var(--text-muted); opacity: 0.6; }
        
        .payment-method-card { border: 2px solid var(--border-color); border-radius: 15px; padding: 20px; text-align: center; cursor: pointer; transition: all 0.2s; background: var(--bg-color); }
        .payment-method-card:hover { border-color: var(--primary); background: var(--row-hover); }
        .payment-method-card i { color: var(--primary); margin-bottom: 10px; }
        
        .theme-toggle-btn { background: transparent; border: none; color: var(--text-main); cursor: pointer; padding: 8px; border-radius: 50%; display: flex; align-items: center; justify-content: center; }
        .theme-toggle-btn:hover { background-color: var(--scan-bg); color: var(--primary); }
        .btn-light { background-color: var(--scan-bg); border-color: var(--border-color); color: var(--text-main); }
        .dropdown-menu { background-color: var(--surface-color); border-color: var(--border-color); }
        .dropdown-item { color: var(--text-main); }
        .dropdown-item:hover { background-color: var(--bg-color); }

        @media (max-width: 992px) { .caisse-container { flex-direction: column; overflow-y: auto; } .ticket-section { width: 100%; height: 500px; } body { overflow: auto; } }
        @media (max-width: 768px) { .sidebar { width: 70px; } .sidebar .nav-link span, .sidebar .logo p, .sidebar .logo h4 { display: none; } .main-content { margin-left: 70px; } }
    </style>
</head>
<body>

    <div class="sidebar">
        <div class="logo">
            <i class="fas fa-chart-line fa-2x"></i>
            <h4>STOCK<span>FLOW</span></h4>
            <p>Caissier<?php echo $rayon ? ' - ' . htmlspecialchars($rayon['nom']) : ''; ?></p>
        </div>
        <nav class="nav flex-column mt-3">
            <a class="nav-link" href="tableau_de_bord_caissier.php"><i class="fas fa-tachometer-alt"></i> <span>Tableau de bord</span></a>
            <a class="nav-link" href="stock_rayon.php"><i class="fas fa-store"></i> <span>Stock rayon</span></a>
            <!-- <a class="nav-link" href="inventaire_rayon_importer.php"><i class="fas fa-upload"></i> <span>Importer inventaire rayon</span></a> -->
            <a class="nav-link active" href="caisse.php"><i class="fas fa-cash-register"></i> <span>Caisse (vente)</span></a>
            <a class="nav-link" href="ventes_historique.php"><i class="fas fa-history"></i> <span>Historique ventes</span></a>
            <a class="nav-link" href="casse_perime_signaler.php"><i class="fas fa-trash-alt"></i> <span>Signaler casse/périmé</span></a>
            <a class="nav-link" href="notifications.php"><i class="fas fa-bell"></i> <span>Notifications</span></a>
            <a class="nav-link" href="profil.php"><i class="fas fa-user"></i> <span>Mon profil</span></a>
            <a class="nav-link" href="../../../deconnexion.php"><i class="fas fa-sign-out-alt"></i> <span>Déconnexion</span></a>
        </nav>
    </div>

    <div class="main-content">
        <div class="top-bar d-flex justify-content-between align-items-center">
            <h5 class="mb-0"><i class="fas fa-cash-register me-2" style="color: var(--primary);"></i>Terminal Caisse</h5>
            
            <div class="d-flex align-items-center gap-3">
                <button id="theme-toggle" class="theme-toggle-btn" aria-label="Changer de thème">
                    <i class="fas fa-moon fs-5"></i>
                </button>
                <div class="dropdown">
                    <button class="btn btn-light dropdown-toggle" data-bs-toggle="dropdown">
                        <i class="fas fa-user-circle me-2"></i> <?php echo htmlspecialchars($user['prenom'] . ' ' . $user['nom']); ?>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li><a class="dropdown-item" href="profil.php">Mon profil</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item text-danger" href="../../../deconnexion.php">Déconnexion</a></li>
                    </ul>
                </div>
            </div>
        </div>

        <?php if (!empty($message)): ?>
            <div class="alert alert-success alert-dismissible fade show border-0 shadow-sm mb-3">
                <i class="fas fa-check-circle me-2"></i><?php echo $message; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        <?php if (!empty($erreur)): ?>
            <div class="alert alert-danger alert-dismissible fade show border-0 shadow-sm mb-3">
                <i class="fas fa-exclamation-triangle me-2"></i><?php echo $erreur; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <div class="caisse-container">
            <div class="scan-section">
                <div class="scan-area">
                    <div class="input-group">
                        <span class="input-group-text border-end-0" style="background: var(--input-bg); color: var(--text-muted);"><i class="fas fa-search"></i></span>
                        <input type="text" class="form-control scan-input border-start-0 ps-0" id="scanInput" placeholder="Scanner un code-barres ou saisir un nom de produit..." autocomplete="off">
                    </div>
                </div>
                <h6 class="text-muted mb-3 fw-medium" style="font-size: 14px;">Produits disponibles dans le rayon</h6>
                <div style="overflow-y: auto; flex: 1;" id="productsList">
                    <?php foreach ($produitsRayon as $p): ?>
                        <div class="product-item" data-search-pool="<?php echo htmlspecialchars(strtolower($p['nom_produit'] . ' ' . $p['code_barre'])); ?>" onclick="ajouterAuPanier(<?php echo $p['id_produit']; ?>, '<?php echo addslashes($p['nom_produit']); ?>', <?php echo $p['prix_vente']; ?>, <?php echo $p['quantite']; ?>)">
                            <div>
                                <span class="fw-semibold d-block"><?php echo htmlspecialchars($p['nom_produit']); ?></span>
                                <small class="text-muted"><code><?php echo $p['code_barre']; ?></code></small>
                            </div>
                            <div class="text-end">
                                <span class="fw-bold text-dark d-block" style="color: var(--text-main) !important;"><?php echo number_format($p['prix_vente'], 0, ',', ' '); ?> FCFA</span>
                                <small class="<?php echo $p['quantite'] <= $p['seuil_alerte_rayon'] ? 'text-warning fw-bold' : 'text-muted'; ?>">Stock: <?php echo $p['quantite']; ?></small>
                            </div>
                        </div>
                    <?php endforeach; ?>
                    <?php if (empty($produitsRayon)): ?>
                        <div class="text-center py-5 text-muted">
                            <i class="fas fa-boxes fa-2x mb-2" style="opacity: 0.3;"></i>
                            <p>Aucun produit affecté à votre rayon.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="ticket-section">
                <div class="ticket-header">
                    <h5 class="mb-1 fw-bold"><i class="fas fa-receipt me-2"></i>Ticket de caisse</h5>
                    <p class="mb-0 opacity-75" id="ticketNumber">#T-<?php echo str_pad(rand(1,9999), 4, '0', STR_PAD_LEFT); ?></p>
                </div>
                <div class="ticket-items" id="ticketItems">
                    <div class="empty-cart"><i class="fas fa-shopping-cart fa-2x mb-2"></i><p class="mb-0">Le panier est vide</p></div>
                </div>
                <div class="ticket-total" id="ticketTotal" style="display: none;">
                    <div class="d-flex justify-content-between total-grand">
                        <span>TOTAL</span>
                        <span id="total">0 FCFA</span>
                    </div>
                </div>
                
                <div class="ticket-actions" id="ticketActions" style="display: none;">
                    <button type="button" class="btn btn-success btn-payment" onclick="ouvrirModalPaiement()">
                        <i class="fas fa-calculator me-2"></i> Encaisser
                    </button>
                    <button class="btn btn-clear" onclick="viderPanier()" title="Vider le panier"><i class="fas fa-trash-alt"></i></button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Paiement -->
    <div class="modal fade" id="paiementModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content" style="background: var(--surface-color); color: var(--text-main); border: 1px solid var(--border-color);">
                <div class="modal-header border-0 pb-0">
                    <h5 class="modal-title fw-bold"><i class="fas fa-wallet me-2 text-primary"></i>Mode de règlement</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body py-4">
                    <div class="text-center mb-4">
                        <span class="text-muted small text-uppercase fw-semibold d-block">Montant à percevoir</span>
                        <h2 class="fw-bold text-success" id="modalTotalAffichage">0 FCFA</h2>
                    </div>
                    
                    <div class="row g-3">
                        <div class="col-4">
                            <div class="payment-method-card" onclick="finaliserVente('ESPECES')">
                                <i class="fas fa-money-bill-wave fa-2x d-block"></i>
                                <span class="fw-medium small">Espèces</span>
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="payment-method-card" onclick="finaliserVente('CARTE')">
                                <i class="fas fa-credit-card fa-2x d-block"></i>
                                <span class="fw-medium small">Carte</span>
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="payment-method-card" onclick="finaliserVente('MIXTE')">
                                <i class="fas fa-layer-group fa-2x d-block"></i>
                                <span class="fw-medium small">Mixte</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Formulaire caché pour la vente -->
    <form method="POST" id="venteForm" style="display:none;">
        <input type="hidden" name="action" value="vendre">
        <input type="hidden" name="produits" id="produitsInput" value="">
        <input type="hidden" name="mode_paiement" id="modePaiement" value="ESPECES">
    </form>

        <!-- Bootstrap JS -->
    <script src="../bootstrap-5.3.8-dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Responsive JS -->
    <script src="../../../public/js/responsive.js"></script>
    
    <!-- Script Caisse -->
    <script>
        // Initialisation du modal Bootstrap
        var bsPaiementModal = new bootstrap.Modal(document.getElementById('paiementModal'));
        
        let panier = [];
        
        function ajouterAuPanier(id, nom, prix, stock) {
            let item = panier.find(i => i.id === id);
            if (item) {
                if (item.qte + 1 > stock) { alert('Stock magasin insuffisant ! Réel disponible: ' + stock); return; }
                item.qte++;
            } else {
                if(stock <= 0) { alert('Impossible : rupture de stock totale.'); return; }
                panier.push({ id: id, nom: nom, prix: prix, stock: stock, qte: 1 });
            }
            mettreAJourTicket();
            clearSearch();
        }
        
        function modifierQuantite(id, delta) {
            let item = panier.find(i => i.id === id);
            if (item) {
                let nouvelle = item.qte + delta;
                if (nouvelle <= 0) panier = panier.filter(i => i.id !== id);
                else if (nouvelle <= item.stock) item.qte = nouvelle;
                else alert('Stock limite atteint !');
                mettreAJourTicket();
            }
        }
        
        function supprimerProduit(id) {
            panier = panier.filter(i => i.id !== id);
            mettreAJourTicket();
        }
        
        function mettreAJourTicket() {
            const container = document.getElementById('ticketItems');
            const totalDiv = document.getElementById('ticketTotal');
            const actionsDiv = document.getElementById('ticketActions');
            
            if (panier.length === 0) {
                container.innerHTML = '<div class="empty-cart"><i class="fas fa-shopping-cart fa-2x mb-2"></i><p class="mb-0">Le panier est vide</p></div>';
                totalDiv.style.display = 'none';
                actionsDiv.style.display = 'none';
                return;
            }
            
            let html = '', subtotal = 0;
            panier.forEach(item => {
                let totalItem = item.prix * item.qte;
                subtotal += totalItem;
                html += `
                <div class="ticket-item">
                    <div style="max-width: 55%;">
                        <span class="fw-semibold d-block text-truncate">${item.nom}</span>
                        <small class="text-muted">${item.prix.toLocaleString()} FCFA</small>
                    </div>
                    <div class="d-flex align-items-center gap-1">
                        <button class="item-qty-btn" onclick="modifierQuantite(${item.id}, -1)">-</button>
                        <span class="fw-bold px-1">${item.qte}</span>
                        <button class="item-qty-btn" onclick="modifierQuantite(${item.id}, 1)">+</button>
                        <i class="fas fa-trash-alt item-remove" onclick="supprimerProduit(${item.id})"></i>
                    </div>
                    <div class="text-end fw-semibold">${totalItem.toLocaleString()} F</div>
                </div>`;
            });
            
            container.innerHTML = html;
            const affichagePrix = subtotal.toLocaleString() + ' FCFA';
            document.getElementById('total').innerHTML = affichagePrix;
            document.getElementById('modalTotalAffichage').innerHTML = affichagePrix;
            
            document.getElementById('produitsInput').value = JSON.stringify(
                panier.map(p => ({ id_produit: p.id, quantite: p.qte }))
            );
            totalDiv.style.display = 'block';
            actionsDiv.style.display = 'flex';
        }
        
        function ouvrirModalPaiement() {
            if (panier.length === 0) {
                alert("Le panier est vide !");
                return;
            }
            bsPaiementModal.show();
        }

        function finaliserVente(mode) {
            document.getElementById('modePaiement').value = mode;
            document.getElementById('produitsInput').value = JSON.stringify(
                panier.map(p => ({ id_produit: p.id, quantite: p.qte }))
            );
            
            bsPaiementModal.hide();
            document.getElementById('venteForm').submit();
        }
        
        function viderPanier() {
            if(confirm('Voulez-vous annuler le ticket en cours ?')) {
                panier = [];
                mettreAJourTicket();
                clearSearch();
            }
        }

        function clearSearch() {
            const input = document.getElementById('scanInput');
            input.value = '';
            input.focus();
            document.querySelectorAll('.product-item').forEach(el => el.style.display = 'flex');
        }
        
        // Recherche et Scan en temps réel
        document.getElementById('scanInput').addEventListener('input', function() {
            let val = this.value.trim().toLowerCase();
            let items = document.querySelectorAll('.product-item');
            
            items.forEach(function(item) {
                let searchPool = item.getAttribute('data-search-pool');
                if (searchPool.includes(val)) {
                    item.style.setProperty('display', 'flex', 'important');
                } else {
                    item.style.setProperty('display', 'none', 'important');
                }
            });
        });

        // Validation par code barre direct
        document.getElementById('scanInput').addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                let visibleItems = Array.from(document.querySelectorAll('.product-item')).filter(i => i.style.display !== 'none');
                if (visibleItems.length > 0) {
                    visibleItems[0].click();
                } else {
                    alert('Produit introuvable ou non référencé dans votre rayon.');
                    this.value = '';
                }
            }
        });
        
        // Gestion Thème
        document.addEventListener('DOMContentLoaded', () => {
            document.getElementById('scanInput').focus();
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
            if (savedTheme) applyTheme(savedTheme);

            themeToggleBtn.addEventListener('click', () => {
                const isDark = document.documentElement.getAttribute('data-theme') === 'dark';
                applyTheme(isDark ? 'light' : 'dark');
            });
        });
    </script>
    
    <!-- Bootstrap JS (fin de body) -->
    <script src="../bootstrap-5.3.8-dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>