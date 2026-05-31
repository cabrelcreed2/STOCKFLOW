<?php
// ============================================================
// MODÈLE : StockMagasin
// Table : stock_magasin
// ============================================================

class StockMagasin {
    
    private $conn;
    
    // Constructeur
    public function __construct($conn) {
        $this->conn = $conn;
    }
    
    // --------------------------------------------------------
    // Récupérer TOUT le stock magasin
    // --------------------------------------------------------
    public function getAll() {
        $sql = "SELECT sm.*, p.nom AS nom_produit, p.code_barre, p.seuil_alerte_magasin, p.prix_achat
                FROM stock_magasin sm 
                JOIN produit p ON sm.id_produit = p.id_produit 
                WHERE p.est_actif = 1
                ORDER BY p.nom ASC";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll();
    }
    
    // --------------------------------------------------------
    // Récupérer UN stock par son ID
    // --------------------------------------------------------
    public function getById($id) {
        $sql = "SELECT sm.*, p.nom AS nom_produit, p.code_barre, p.seuil_alerte_magasin 
                FROM stock_magasin sm 
                JOIN produit p ON sm.id_produit = p.id_produit 
                WHERE sm.id_stock_magasin = :id";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute(['id' => $id]);
        return $stmt->fetch();
    }
    
    // --------------------------------------------------------
    // Récupérer le stock d'un produit
    // --------------------------------------------------------
    public function getByProduit($id_produit) {
        $sql = "SELECT * FROM stock_magasin WHERE id_produit = :id_produit";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute(['id_produit' => $id_produit]);
        return $stmt->fetch();
    }
    
    // --------------------------------------------------------
    // Récupérer les produits en alerte (stock ≤ seuil)
    // --------------------------------------------------------
    public function getProduitsEnAlerte() {
        $sql = "SELECT sm.*, p.nom AS nom_produit, p.code_barre, p.seuil_alerte_magasin, p.prix_achat
                FROM stock_magasin sm 
                JOIN produit p ON sm.id_produit = p.id_produit 
                WHERE sm.quantite <= p.seuil_alerte_magasin AND p.est_actif = 1
                ORDER BY sm.quantite ASC";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll();
    }
    
    // --------------------------------------------------------
    // Ajouter du stock (entrée)
    // --------------------------------------------------------
    public function ajouter($id_produit, $quantite, $date_peremption) {
        // Vérifier si le produit existe déjà dans le stock magasin
        $existant = $this->getByProduit($id_produit);
        
        if ($existant) {
            // Si existe → ajouter à la quantité existante
            $sql = "UPDATE stock_magasin 
                    SET quantite = quantite + :quantite, 
                        date_peremption = :date_peremption 
                    WHERE id_produit = :id_produit";
        } else {
            // Si n'existe pas → créer une nouvelle ligne
            $sql = "INSERT INTO stock_magasin (quantite, date_peremption, id_produit) 
                    VALUES (:quantite, :date_peremption, :id_produit)";
        }
        
        $stmt = $this->conn->prepare($sql);
        return $stmt->execute([
            'quantite' => $quantite,
            'date_peremption' => $date_peremption,
            'id_produit' => $id_produit
        ]);
    }
    
    // --------------------------------------------------------
    // Retirer du stock (sortie)
    // Retourne true si réussi, false si stock insuffisant
    // --------------------------------------------------------
    public function retirer($id_produit, $quantite) {
        // Vérifier le stock actuel
        $stock = $this->getByProduit($id_produit);
        
        if (!$stock || $stock['quantite'] < $quantite) {
            return false; // Stock insuffisant
        }
        
        $sql = "UPDATE stock_magasin 
                SET quantite = quantite - :quantite 
                WHERE id_produit = :id_produit";
        $stmt = $this->conn->prepare($sql);
        return $stmt->execute([
            'quantite' => $quantite,
            'id_produit' => $id_produit
        ]);
    }
    
    // --------------------------------------------------------
    // Mettre à jour la date de péremption
    // --------------------------------------------------------
    public function updatePeremption($id_produit, $date_peremption) {
        $sql = "UPDATE stock_magasin SET date_peremption = :date_peremption WHERE id_produit = :id_produit";
        $stmt = $this->conn->prepare($sql);
        return $stmt->execute([
            'date_peremption' => $date_peremption,
            'id_produit' => $id_produit
        ]);
    }
    
    // --------------------------------------------------------
    // Récupérer les produits proches de la péremption
    // --------------------------------------------------------
    public function getProchesPeremption($jours = 7) {
        $sql = "SELECT sm.*, p.nom AS nom_produit, p.code_barre,
                    DATEDIFF(sm.date_peremption, CURDATE()) AS jours_restants
                FROM stock_magasin sm 
                JOIN produit p ON sm.id_produit = p.id_produit 
                WHERE sm.date_peremption IS NOT NULL 
                    AND DATEDIFF(sm.date_peremption, CURDATE()) <= :jours
                    AND sm.quantite > 0
                ORDER BY jours_restants ASC";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute(['jours' => $jours]);
        return $stmt->fetchAll();
    }
}