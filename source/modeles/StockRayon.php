<?php
// ============================================================
// MODÈLE : StockRayon
// Table : stock_rayon
// ============================================================

class StockRayon {
    
    private $conn;
    
    // Constructeur
    public function __construct($conn) {
        $this->conn = $conn;
    }
    
    // --------------------------------------------------------
    // Récupérer TOUT le stock de tous les rayons
    // --------------------------------------------------------
    public function getAll() {
        $sql = "SELECT sr.*, p.nom AS nom_produit, p.code_barre, p.seuil_alerte_rayon, p.prix_vente,
                    r.nom AS nom_rayon
                FROM stock_rayon sr 
                JOIN produit p ON sr.id_produit = p.id_produit 
                JOIN rayon r ON sr.id_rayon = r.id_rayon 
                WHERE p.est_actif = 1
                ORDER BY r.nom ASC, p.nom ASC";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll();
    }
    
    // --------------------------------------------------------
    // Récupérer le stock d'UN rayon
    // --------------------------------------------------------
    public function getByRayon($id_rayon) {
        $sql = "SELECT sr.*, p.nom AS nom_produit, p.code_barre, p.seuil_alerte_rayon, p.prix_vente
                FROM stock_rayon sr 
                JOIN produit p ON sr.id_produit = p.id_produit 
                WHERE sr.id_rayon = :id_rayon AND p.est_actif = 1
                ORDER BY p.nom ASC";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute(['id_rayon' => $id_rayon]);
        return $stmt->fetchAll();
    }
    
    // --------------------------------------------------------
    // Récupérer un stock par son ID
    // --------------------------------------------------------
    public function getById($id) {
        $sql = "SELECT sr.*, p.nom AS nom_produit, p.code_barre, p.seuil_alerte_rayon,
                    r.nom AS nom_rayon
                FROM stock_rayon sr 
                JOIN produit p ON sr.id_produit = p.id_produit 
                JOIN rayon r ON sr.id_rayon = r.id_rayon 
                WHERE sr.id_stock_rayon = :id";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute(['id' => $id]);
        return $stmt->fetch();
    }
    
    // --------------------------------------------------------
    // Récupérer le stock d'un produit dans un rayon
    // --------------------------------------------------------
    public function getByProduitEtRayon($id_produit, $id_rayon) {
        $sql = "SELECT * FROM stock_rayon 
                WHERE id_produit = :id_produit AND id_rayon = :id_rayon";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([
            'id_produit' => $id_produit,
            'id_rayon' => $id_rayon
        ]);
        return $stmt->fetch();
    }
    
    // --------------------------------------------------------
    // Récupérer les produits en alerte dans un rayon
    // --------------------------------------------------------
    public function getProduitsEnAlerte($id_rayon) {
        $sql = "SELECT sr.*, p.nom AS nom_produit, p.code_barre, p.seuil_alerte_rayon, p.prix_vente
                FROM stock_rayon sr 
                JOIN produit p ON sr.id_produit = p.id_produit 
                WHERE sr.id_rayon = :id_rayon 
                    AND sr.quantite <= p.seuil_alerte_rayon 
                    AND p.est_actif = 1
                ORDER BY sr.quantite ASC";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute(['id_rayon' => $id_rayon]);
        return $stmt->fetchAll();
    }
    
    // --------------------------------------------------------
    // Ajouter du stock dans un rayon
    // --------------------------------------------------------
    public function ajouter($id_produit, $id_rayon, $quantite, $date_peremption) {
        // Vérifier si le produit existe déjà dans ce rayon
        $existant = $this->getByProduitEtRayon($id_produit, $id_rayon);
        
        if ($existant) {
            // Si existe → ajouter à la quantité existante
            $sql = "UPDATE stock_rayon 
                    SET quantite = quantite + :quantite, 
                        date_peremption = :date_peremption 
                    WHERE id_produit = :id_produit AND id_rayon = :id_rayon";
        } else {
            // Si n'existe pas → créer une nouvelle ligne
            $sql = "INSERT INTO stock_rayon (quantite, date_peremption, id_produit, id_rayon) 
                    VALUES (:quantite, :date_peremption, :id_produit, :id_rayon)";
        }
        
        $stmt = $this->conn->prepare($sql);
        return $stmt->execute([
            'quantite' => $quantite,
            'date_peremption' => $date_peremption,
            'id_produit' => $id_produit,
            'id_rayon' => $id_rayon
        ]);
    }
    
    // --------------------------------------------------------
    // Retirer du stock d'un rayon
    // Retourne true si réussi, false si stock insuffisant
    // --------------------------------------------------------
    public function retirer($id_produit, $id_rayon, $quantite) {
        // Vérifier le stock actuel
        $stock = $this->getByProduitEtRayon($id_produit, $id_rayon);
        
        if (!$stock || $stock['quantite'] < $quantite) {
            return false; // Stock insuffisant
        }
        
        $sql = "UPDATE stock_rayon 
                SET quantite = quantite - :quantite 
                WHERE id_produit = :id_produit AND id_rayon = :id_rayon";
        $stmt = $this->conn->prepare($sql);
        return $stmt->execute([
            'quantite' => $quantite,
            'id_produit' => $id_produit,
            'id_rayon' => $id_rayon
        ]);
    }
    
    // --------------------------------------------------------
    // Transférer du magasin vers un rayon
    // --------------------------------------------------------
    public function transfererVersRayon($id_produit, $id_rayon, $quantite, $date_peremption) {
        return $this->ajouter($id_produit, $id_rayon, $quantite, $date_peremption);
    }
    
    // --------------------------------------------------------
    // Récupérer les produits proches de la péremption dans un rayon
    // --------------------------------------------------------
    public function getProchesPeremption($id_rayon, $jours = 7) {
        $sql = "SELECT sr.*, p.nom AS nom_produit, p.code_barre,
                    DATEDIFF(sr.date_peremption, CURDATE()) AS jours_restants
                FROM stock_rayon sr 
                JOIN produit p ON sr.id_produit = p.id_produit 
                WHERE sr.id_rayon = :id_rayon
                    AND sr.date_peremption IS NOT NULL 
                    AND DATEDIFF(sr.date_peremption, CURDATE()) <= :jours
                    AND sr.quantite > 0
                ORDER BY jours_restants ASC";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([
            'id_rayon' => $id_rayon,
            'jours' => $jours
        ]);
        return $stmt->fetchAll();
    }
}