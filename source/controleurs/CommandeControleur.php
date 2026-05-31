<?php
// ============================================================
// CONTRÔLEUR : CommandeControleur
// Fonctionnalité : Passer une commande fournisseur
// ============================================================

require_once __DIR__ . '/../modeles/BaseDeDonnees.php';
require_once __DIR__ . '/../modeles/CommandeFournisseur.php';
require_once __DIR__ . '/../modeles/LigneCommande.php';
require_once __DIR__ . '/../modeles/Produit.php';
require_once __DIR__ . '/../modeles/Fournisseur.php';
require_once __DIR__ . '/../modeles/Notification.php';
require_once __DIR__ . '/../modeles/Utilisateur.php';
require_once __DIR__ . '/../../configuration/constantes.php';

class CommandeControleur {
    
    private $commande;
    private $ligneCommande;
    private $produit;
    private $fournisseur;
    private $notification;
    private $utilisateur;
    private $conn;
    
    // Constructeur
    public function __construct() {
        $db = new Database();
        $this->conn = $db->connect();
        
        $this->commande = new CommandeFournisseur($this->conn);
        $this->ligneCommande = new LigneCommande($this->conn);
        $this->produit = new Produit($this->conn);
        $this->fournisseur = new Fournisseur($this->conn);
        $this->notification = new Notification($this->conn);
        $this->utilisateur = new Utilisateur($this->conn);
    }
    
    // --------------------------------------------------------
    // PASSER UNE COMMANDE FOURNISSEUR
    // --------------------------------------------------------
    public function passerCommande($id_fournisseur, $produits, $id_gestionnaire) {
        
        // 1. Vérifier que le fournisseur existe et est actif
        $fournisseur = $this->fournisseur->getById($id_fournisseur);
        if (!$fournisseur) {
            return "Fournisseur introuvable.";
        }
        if ($fournisseur['est_actif'] != 1) {  // ✅ CORRIGÉ : comparaison avec 1
            return "Ce fournisseur n'est plus actif.";
        }
        if ($fournisseur['est_en_rupture'] == 1) {  // ✅ CORRIGÉ : comparaison avec 1
            return "Ce fournisseur est actuellement en rupture.";
        }
        
        // 2. Vérifier qu'il y a au moins un produit
        if (empty($produits)) {
            return "Aucun produit sélectionné.";
        }
        
        // 3. Créer la commande
        $id_commande = $this->commande->creer($id_fournisseur, $id_gestionnaire);
        
        // 4. Ajouter chaque produit à la commande
        foreach ($produits as $p) {
            
            $produit = $this->produit->getById($p['id_produit']);
            if (!$produit) {
                continue;
            }
            
            if ($p['quantite'] <= 0) {
                continue;
            }
            
            if ($produit['id_fournisseur'] != $id_fournisseur) {
                continue;
            }
            
            $this->ligneCommande->ajouter(
                $id_commande,
                $p['id_produit'],
                $p['quantite'],
                $produit['prix_achat']
            );
        }
        
        // 5. Vérifier que la commande a au moins une ligne
        if ($this->ligneCommande->compter($id_commande) == 0) {
            $this->commande->supprimer($id_commande);
            return "Aucun produit valide ajouté à la commande.";
        }
        
        // 6. Notifier les autres gestionnaires
        $gestionnaires = $this->utilisateur->getByRole('GESTIONNAIRE');
        $numero = $this->commande->getById($id_commande)['numero_commande'];
        
        foreach ($gestionnaires as $g) {
            if ($g['id_utilisateur'] != $id_gestionnaire) {
                // ✅ CORRIGÉ : on utilise une requête directe car id_alerte est NOT NULL
                $sql = "INSERT INTO notification (message, id_alerte, id_utilisateur) 
                        VALUES (:message, 1, :id_utilisateur)";
                $stmt = $this->conn->prepare($sql);
                $stmt->execute([
                    'message' => "Nouvelle commande " . $numero . " passée auprès de " . $fournisseur['nom'],
                    'id_utilisateur' => $g['id_utilisateur']
                ]);
            }
        }
        
        return $id_commande;
    }
    
    // --------------------------------------------------------
    // MARQUER UNE COMMANDE COMME LIVRÉE
    // --------------------------------------------------------
    public function marquerLivree($id_commande, $id_gestionnaire) {
        
        $commande = $this->commande->getById($id_commande);
        if (!$commande) {
            return "Commande introuvable.";
        }
        
        if ($commande['statut'] === 'LIVREE') {
            return "Cette commande est déjà livrée.";
        }
        
        if ($commande['statut'] === 'ANNULEE') {
            return "Impossible de livrer une commande annulée.";
        }
        
        $this->commande->marquerLivree($id_commande);
        
        // Notifier les autres gestionnaires
        $gestionnaires = $this->utilisateur->getByRole('GESTIONNAIRE');
        foreach ($gestionnaires as $g) {
            if ($g['id_utilisateur'] != $id_gestionnaire) {
                $sql = "INSERT INTO notification (message, id_alerte, id_utilisateur) 
                        VALUES (:message, 1, :id_utilisateur)";
                $stmt = $this->conn->prepare($sql);
                $stmt->execute([
                    'message' => "Commande " . $commande['numero_commande'] . " marquée comme livrée",
                    'id_utilisateur' => $g['id_utilisateur']
                ]);
            }
        }
        
        return true;
    }
    
    // --------------------------------------------------------
    // MARQUER UN FOURNISSEUR EN RUPTURE
    // --------------------------------------------------------
    public function marquerFournisseurEnRupture($id_fournisseur) {
        // ✅ CORRIGÉ : utilise le modèle Fournisseur existant
        $sql = "UPDATE fournisseur SET est_en_rupture = 1 WHERE id_fournisseur = :id";
        $stmt = $this->conn->prepare($sql);
        return $stmt->execute(['id' => $id_fournisseur]);
    }

    // --------------------------------------------------------
    // GÉNÉRER UN BON DE COMMANDE (Export PDF via navigateur)
    // --------------------------------------------------------
    public function genererBonCommande($id_commande) {
        
        $commande = $this->commande->getById($id_commande);
        $lignes = $this->ligneCommande->getByCommande($id_commande);
        $total = $this->ligneCommande->getTotal($id_commande);
        
        // ✅ CORRIGÉ : sortie du PHP pour écrire le HTML proprement
        ?>
        <!DOCTYPE html>
        <html lang="fr">
        <head>
            <meta charset="UTF-8">
            <title>Bon de commande N° <?php echo $commande['numero_commande']; ?></title>
            <style>
                body {
                    font-family: Arial, sans-serif;
                    margin: 30px;
                    color: #000;
                }
                .entete {
                    text-align: center;
                    margin-bottom: 30px;
                    border-bottom: 2px solid #000;
                    padding-bottom: 15px;
                }
                .entete h1 { margin: 0; font-size: 20px; }
                .entete h2 { margin: 5px 0; font-size: 16px; }
                .infos { margin-bottom: 30px; }
                .infos p { margin: 3px 0; }
                table {
                    width: 100%;
                    border-collapse: collapse;
                    margin-bottom: 20px;
                }
                th, td {
                    border: 1px solid #000;
                    padding: 8px;
                    text-align: left;
                    font-size: 13px;
                }
                th { background-color: #e0e0e0; }
                .total {
                    text-align: right;
                    font-weight: bold;
                    font-size: 15px;
                }
                .signature {
                    margin-top: 100px;
                    display: flex;
                    justify-content: space-between;
                }
                .signature div {
                    width: 200px;
                    border-top: 1px solid #000;
                    text-align: center;
                    padding-top: 8px;
                }
                .boutons {
                    text-align: center;
                    margin-bottom: 30px;
                }
                .btn-imprimer {
                    padding: 12px 30px;
                    background-color: #007bff;
                    color: white;
                    border: none;
                    cursor: pointer;
                    font-size: 16px;
                    border-radius: 5px;
                }
                .btn-fermer {
                    padding: 12px 30px;
                    background-color: #6c757d;
                    color: white;
                    border: none;
                    cursor: pointer;
                    font-size: 16px;
                    border-radius: 5px;
                    margin-left: 10px;
                }
                @media print {
                    .boutons { display: none; }
                    body { margin: 10px; }
                }
            </style>
        </head>
        <body>
        
        <div class="boutons">
            <button class="btn-imprimer" onclick="window.print()">
                🖨️ Imprimer / Exporter en PDF
            </button>
            <button class="btn-fermer" onclick="window.close()">
                ❌ Fermer
            </button>
        </div>
        
        <div class="entete">
            <h1>🏪 SUPERSTOCK</h1>
            <p>Gestion de Stock - Supermarché</p>
            <h2>BON DE COMMANDE FOURNISSEUR</h2>
            <p>N° <?php echo $commande['numero_commande']; ?></p>
        </div>
        
        <div class="infos">
            <p><strong>Fournisseur :</strong> <?php echo $commande['nom_fournisseur']; ?></p>
            <p><strong>Email :</strong> <?php echo $commande['email_fournisseur']; ?></p>
            <p><strong>Téléphone :</strong> <?php echo $commande['tel_fournisseur']; ?></p>
            <p><strong>Date :</strong> <?php echo date('d/m/Y', strtotime($commande['date_commande'])); ?></p>
            <p><strong>Gestionnaire :</strong> <?php echo $commande['prenom_gestionnaire'] . ' ' . $commande['nom_gestionnaire']; ?></p>
        </div>
        
        <table>
            <thead>
                <tr>
                    <th>Produit</th>
                    <th>Quantité</th>
                    <th>Prix unitaire (FCFA)</th>
                    <th>Sous-total (FCFA)</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($lignes as $ligne): ?>
                <tr>
                    <td><?php echo $ligne['nom_produit']; ?></td>
                    <td><?php echo $ligne['quantite']; ?></td>
                    <td><?php echo number_format($ligne['prix_unitaire'], 0, ',', ' '); ?></td>
                    <td><?php echo number_format($ligne['sous_total'], 0, ',', ' '); ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        
        <p class="total">TOTAL : <?php echo number_format($total, 0, ',', ' '); ?> FCFA</p>
        
        <div class="signature">
            <div>Le Gestionnaire<br><?php echo $commande['prenom_gestionnaire'] . ' ' . $commande['nom_gestionnaire']; ?></div>
            <div>Le Fournisseur<br><?php echo $commande['nom_fournisseur']; ?></div>
        </div>
        
        <script>
            window.onload = function() {
                window.print();
            };
        </script>
        
        </body>
        </html>
        <?php
    }
}