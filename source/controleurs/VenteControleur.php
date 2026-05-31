<?php
// ============================================================
// CONTRÔLEUR : VenteControleur
// Fonctionnalité : Effectuer une vente
// ============================================================

require_once __DIR__ . '/../modeles/BaseDeDonnees.php';
require_once __DIR__ . '/../modeles/Vente.php';
require_once __DIR__ . '/../modeles/LigneVente.php';
require_once __DIR__ . '/../modeles/StockRayon.php';
require_once __DIR__ . '/../modeles/MouvementStock.php';
require_once __DIR__ . '/../modeles/Alerte.php';
require_once __DIR__ . '/../modeles/Notification.php';
require_once __DIR__ . '/../modeles/Utilisateur.php';
require_once __DIR__ . '/../../configuration/constantes.php';

class VenteControleur {
    
    private $vente;
    private $ligneVente;
    private $stockRayon;
    private $mouvementStock;
    private $alerte;
    private $notification;
    private $utilisateur;
    private $conn;
    
    // Constructeur
    public function __construct() {
        $db = new Database();
        $this->conn = $db->connect();
        
        $this->vente = new Vente($this->conn);
        $this->ligneVente = new LigneVente($this->conn);
        $this->stockRayon = new StockRayon($this->conn);
        $this->mouvementStock = new MouvementStock($this->conn);
        $this->alerte = new Alerte($this->conn);
        $this->notification = new Notification($this->conn);
        $this->utilisateur = new Utilisateur($this->conn);
    }
    
    // --------------------------------------------------------
    // EFFECTUER UNE VENTE
    // Appelé par : Caissière
    // --------------------------------------------------------
    public function effectuerVente($produits, $mode_paiement, $id_caissiere) {
        
        // $produits est un tableau :
        // [
        //   ['id_produit' => 1, 'quantite' => 2],
        //   ['id_produit' => 3, 'quantite' => 1],
        // ]
        
        // 1. Vérifier qu'il y a au moins un produit
        if (empty($produits)) {
            return "Aucun produit à vendre.";
        }
        
        // 2. Vérifier que le mode de paiement est valide
        if (!in_array($mode_paiement, [PAIEMENT_ESPECES, PAIEMENT_CARTE, PAIEMENT_MIXTE])) {
            return "Mode de paiement invalide.";
        }
        
        // 3. Récupérer le rayon de la caissière
        $rayon = $this->getRayonByCaissiere($id_caissiere);
        if (!$rayon) {
            return "Aucun rayon assigné à cette caissière.";
        }
        $id_rayon = $rayon['id_rayon'];
        
        // 4. Vérifier les stocks avant de commencer la vente
        foreach ($produits as $p) {
            
            // Vérifier que le produit existe et est actif
            $produit = $this->getProduitById($p['id_produit']);
            if (!$produit) {
                return "Le produit ID " . $p['id_produit'] . " n'existe pas.";
            }
            if (!$produit['est_actif']) {
                return "Le produit " . $produit['nom'] . " n'est plus en vente.";
            }
            
            // Vérifier la date de péremption
            $stock = $this->stockRayon->getByProduitEtRayon($p['id_produit'], $id_rayon);
            if ($stock && $stock['date_peremption']) {
                if (strtotime($stock['date_peremption']) <= time()) {
                    return "Le produit " . $produit['nom'] . " est périmé.";
                }
            }
            
            // Vérifier que le stock est suffisant
            $quantite_dispo = $stock ? $stock['quantite'] : 0;
            if ($quantite_dispo < $p['quantite']) {
                return "Stock insuffisant pour " . $produit['nom'] . ". Disponible : " . $quantite_dispo;
            }
        }
        
        // 5. Créer la vente
        $id_vente = $this->vente->creer($id_caissiere, $id_rayon, $mode_paiement);
        
        // 6. Traiter chaque produit
        foreach ($produits as $p) {
            
            // Récupérer les infos du produit
            $produit = $this->getProduitById($p['id_produit']);
            
            // Récupérer le stock avant
            $stock = $this->stockRayon->getByProduitEtRayon($p['id_produit'], $id_rayon);
            $stock_avant = $stock ? $stock['quantite'] : 0;
            
            // Ajouter la ligne de vente
            $this->ligneVente->ajouter(
                $id_vente,
                $p['id_produit'],
                $p['quantite'],
                $produit['prix_vente']
            );
            
            // Décrémenter le stock rayon
            $this->stockRayon->retirer($p['id_produit'], $id_rayon, $p['quantite']);
            
            // Stock après
            $stock_apres = $stock_avant - $p['quantite'];
            
            // Enregistrer le mouvement
            $this->mouvementStock->enregistrer(
                MVT_VENTE,
                'RAYON',
                $p['quantite'],
                $stock_avant,
                $stock_apres,
                $p['id_produit'],
                $id_caissiere,
                $id_rayon,
                'Vente en caisse'
            );
            
            // Vérifier si le stock est passé sous le seuil
            if ($stock_apres <= $produit['seuil_alerte_rayon']) {
                $this->gererAlerteStockBas($p['id_produit'], $id_rayon, $stock_apres, $produit);
            }
        }
        
        // 7. Calculer le total
        $total = $this->ligneVente->getTotal($id_vente);
        
        return [
            'succes' => true,
            'id_vente' => $id_vente,
            'total' => $total,
            'rayon' => $rayon['nom']
        ];
    }
    
    // --------------------------------------------------------
    // Gérer l'alerte de stock bas
    // --------------------------------------------------------
    private function gererAlerteStockBas($id_produit, $id_rayon, $stock_actuel, $produit) {
        
        // Créer l'alerte
        $message = "Stock bas rayon : " . $produit['nom'] . " (" . $stock_actuel . " unités restantes, seuil : " . $produit['seuil_alerte_rayon'] . ")";
        
        $this->alerte->creer(
            ALERTE_STOCK_BAS_RAYON,
            ALERTE_ATTENTION,
            $message,
            $id_produit,
            $id_rayon
        );
        
        // Récupérer l'ID de la dernière alerte créée
        $id_alerte = $this->conn->lastInsertId();
        
        // Notifier le magasinier pour qu'il réapprovisionne
        $magasiniers = $this->utilisateur->getByRole('MAGASINIER');
        foreach ($magasiniers as $m) {
            $sql = "INSERT INTO notification (message, id_alerte, id_utilisateur) 
                    VALUES (:message, :id_alerte, :id_utilisateur)";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([
                'message' => "URGENT : Réapprovisionnement nécessaire - " . $produit['nom'] . " (" . $stock_actuel . " restants)",
                'id_alerte' => $id_alerte,
                'id_utilisateur' => $m['id_utilisateur']
            ]);
        }
        
        // Notifier le(s) gestionnaire(s)
        $gestionnaires = $this->utilisateur->getByRole('GESTIONNAIRE');
        foreach ($gestionnaires as $g) {
            $sql = "INSERT INTO notification (message, id_alerte, id_utilisateur) 
                    VALUES (:message, :id_alerte, :id_utilisateur)";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([
                'message' => $message,
                'id_alerte' => $id_alerte,
                'id_utilisateur' => $g['id_utilisateur']
            ]);
        }
    }
    
    // --------------------------------------------------------
    // Récupérer le rayon d'une caissière
    // --------------------------------------------------------
    private function getRayonByCaissiere($id_caissiere) {
        $sql = "SELECT * FROM rayon WHERE id_caissiere = :id_caissiere";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute(['id_caissiere' => $id_caissiere]);
        return $stmt->fetch();
    }
    
    // --------------------------------------------------------
    // Récupérer un produit par son ID
    // --------------------------------------------------------
    private function getProduitById($id_produit) {
        $sql = "SELECT * FROM produit WHERE id_produit = :id";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute(['id' => $id_produit]);
        return $stmt->fetch();
    }
}