<?php
// ============================================================
// MODÈLE : LigneVente
// Table : ligne_vente
// ============================================================

class LigneVente {
    
    private $conn;
    
    // Constructeur
    public function __construct($conn) {
        $this->conn = $conn;
    }
    
    // --------------------------------------------------------
    // Récupérer les lignes d'une vente
    // --------------------------------------------------------
    public function getByVente($id_vente) {
        $sql = "SELECT lv.*, p.nom AS nom_produit, p.code_barre,
                    (lv.quantite * lv.prix_unitaire) AS sous_total
                FROM ligne_vente lv 
                JOIN produit p ON lv.id_produit = p.id_produit 
                WHERE lv.id_vente = :id_vente 
                ORDER BY p.nom ASC";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute(['id_vente' => $id_vente]);
        return $stmt->fetchAll();
    }
    
    // --------------------------------------------------------
    // Ajouter une ligne à une vente
    // --------------------------------------------------------
    public function ajouter($id_vente, $id_produit, $quantite, $prix_unitaire) {
        // Vérifier si le produit existe déjà dans la vente
        if ($this->produitExiste($id_vente, $id_produit)) {
            // Si existe → mettre à jour la quantité
            $sql = "UPDATE ligne_vente 
                    SET quantite = quantite + :quantite 
                    WHERE id_vente = :id_vente AND id_produit = :id_produit";
        } else {
            // Si n'existe pas → créer une nouvelle ligne
            $sql = "INSERT INTO ligne_vente (id_vente, id_produit, quantite, prix_unitaire) 
                    VALUES (:id_vente, :id_produit, :quantite, :prix_unitaire)";
        }
        
        $stmt = $this->conn->prepare($sql);
        return $stmt->execute([
            'id_vente' => $id_vente,
            'id_produit' => $id_produit,
            'quantite' => $quantite,
            'prix_unitaire' => $prix_unitaire
        ]);
    }
    
    // --------------------------------------------------------
    // Modifier une ligne de vente
    // --------------------------------------------------------
    public function modifier($id_vente, $id_produit, $quantite, $prix_unitaire) {
        $sql = "UPDATE ligne_vente 
                SET quantite = :quantite, prix_unitaire = :prix_unitaire 
                WHERE id_vente = :id_vente AND id_produit = :id_produit";
        $stmt = $this->conn->prepare($sql);
        return $stmt->execute([
            'id_vente' => $id_vente,
            'id_produit' => $id_produit,
            'quantite' => $quantite,
            'prix_unitaire' => $prix_unitaire
        ]);
    }
    
    // --------------------------------------------------------
    // Supprimer une ligne d'une vente
    // --------------------------------------------------------
    public function supprimer($id_vente, $id_produit) {
        $sql = "DELETE FROM ligne_vente 
                WHERE id_vente = :id_vente AND id_produit = :id_produit";
        $stmt = $this->conn->prepare($sql);
        return $stmt->execute([
            'id_vente' => $id_vente,
            'id_produit' => $id_produit
        ]);
    }
    
    // --------------------------------------------------------
    // Supprimer toutes les lignes d'une vente
    // --------------------------------------------------------
    public function supprimerTout($id_vente) {
        $sql = "DELETE FROM ligne_vente WHERE id_vente = :id_vente";
        $stmt = $this->conn->prepare($sql);
        return $stmt->execute(['id_vente' => $id_vente]);
    }
    
    // --------------------------------------------------------
    // Calculer le total d'une vente
    // --------------------------------------------------------
    public function getTotal($id_vente) {
        $sql = "SELECT COALESCE(SUM(quantite * prix_unitaire), 0) AS total
                FROM ligne_vente 
                WHERE id_vente = :id_vente";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute(['id_vente' => $id_vente]);
        $resultat = $stmt->fetch();
        return $resultat['total'];
    }
    
    // --------------------------------------------------------
    // Compter le nombre de lignes d'une vente
    // --------------------------------------------------------
    public function compter($id_vente) {
        $sql = "SELECT COUNT(*) FROM ligne_vente WHERE id_vente = :id_vente";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute(['id_vente' => $id_vente]);
        return $stmt->fetchColumn();
    }
    
    // --------------------------------------------------------
    // Compter le nombre total de produits vendus dans une vente
    // --------------------------------------------------------
    public function getQuantiteTotale($id_vente) {
        $sql = "SELECT COALESCE(SUM(quantite), 0) AS total_quantite
                FROM ligne_vente 
                WHERE id_vente = :id_vente";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute(['id_vente' => $id_vente]);
        $resultat = $stmt->fetch();
        return $resultat['total_quantite'];
    }
    
    // --------------------------------------------------------
    // Vérifier si un produit est déjà dans une vente
    // --------------------------------------------------------
    public function produitExiste($id_vente, $id_produit) {
        $sql = "SELECT COUNT(*) FROM ligne_vente 
                WHERE id_vente = :id_vente AND id_produit = :id_produit";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([
            'id_vente' => $id_vente,
            'id_produit' => $id_produit
        ]);
        return $stmt->fetchColumn() > 0;
    }
    
    // --------------------------------------------------------
    // Produits les plus vendus (pour les rapports)
    // --------------------------------------------------------
    public function getPlusVendus($limite = 10) {
        $sql = "SELECT p.id_produit, p.nom, p.code_barre,
                    SUM(lv.quantite) AS total_vendu,
                    SUM(lv.quantite * lv.prix_unitaire) AS chiffre_affaires
                FROM ligne_vente lv 
                JOIN produit p ON lv.id_produit = p.id_produit 
                GROUP BY lv.id_produit 
                ORDER BY total_vendu DESC 
                LIMIT :limite";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(':limite', (int)$limite, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }
}