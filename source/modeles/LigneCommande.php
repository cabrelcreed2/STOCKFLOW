<?php
// ============================================================
// MODÈLE : LigneCommande
// Table : ligne_commande
// ============================================================

class LigneCommande {
    
    private $conn;
    
    // Constructeur
    public function __construct($conn) {
        $this->conn = $conn;
    }
    
    // --------------------------------------------------------
    // Récupérer les lignes d'une commande
    // --------------------------------------------------------
    public function getByCommande($id_commande) {
        $sql = "SELECT lc.*, p.nom AS nom_produit, p.code_barre, p.prix_achat,
                    (lc.quantite * lc.prix_unitaire) AS sous_total
                FROM ligne_commande lc 
                JOIN produit p ON lc.id_produit = p.id_produit 
                WHERE lc.id_commande = :id_commande 
                ORDER BY p.nom ASC";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute(['id_commande' => $id_commande]);
        return $stmt->fetchAll();
    }
    
    // --------------------------------------------------------
    // Ajouter une ligne à une commande
    // --------------------------------------------------------
    public function ajouter($id_commande, $id_produit, $quantite, $prix_unitaire) {
        $sql = "INSERT INTO ligne_commande (id_commande, id_produit, quantite, prix_unitaire) 
                VALUES (:id_commande, :id_produit, :quantite, :prix_unitaire)";
        $stmt = $this->conn->prepare($sql);
        return $stmt->execute([
            'id_commande' => $id_commande,
            'id_produit' => $id_produit,
            'quantite' => $quantite,
            'prix_unitaire' => $prix_unitaire
        ]);
    }
    
    // --------------------------------------------------------
    // Modifier une ligne de commande
    // --------------------------------------------------------
    public function modifier($id_commande, $id_produit, $quantite, $prix_unitaire) {
        $sql = "UPDATE ligne_commande 
                SET quantite = :quantite, prix_unitaire = :prix_unitaire 
                WHERE id_commande = :id_commande AND id_produit = :id_produit";
        $stmt = $this->conn->prepare($sql);
        return $stmt->execute([
            'id_commande' => $id_commande,
            'id_produit' => $id_produit,
            'quantite' => $quantite,
            'prix_unitaire' => $prix_unitaire
        ]);
    }
    
    // --------------------------------------------------------
    // Supprimer une ligne d'une commande
    // --------------------------------------------------------
    public function supprimer($id_commande, $id_produit) {
        $sql = "DELETE FROM ligne_commande 
                WHERE id_commande = :id_commande AND id_produit = :id_produit";
        $stmt = $this->conn->prepare($sql);
        return $stmt->execute([
            'id_commande' => $id_commande,
            'id_produit' => $id_produit
        ]);
    }
    
    // --------------------------------------------------------
    // Supprimer toutes les lignes d'une commande
    // --------------------------------------------------------
    public function supprimerTout($id_commande) {
        $sql = "DELETE FROM ligne_commande WHERE id_commande = :id_commande";
        $stmt = $this->conn->prepare($sql);
        return $stmt->execute(['id_commande' => $id_commande]);
    }
    
    // --------------------------------------------------------
    // Calculer le total d'une commande
    // --------------------------------------------------------
    public function getTotal($id_commande) {
        $sql = "SELECT COALESCE(SUM(quantite * prix_unitaire), 0) AS total
                FROM ligne_commande 
                WHERE id_commande = :id_commande";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute(['id_commande' => $id_commande]);
        $resultat = $stmt->fetch();
        return $resultat['total'];
    }
    
    // --------------------------------------------------------
    // Compter le nombre de lignes d'une commande
    // --------------------------------------------------------
    public function compter($id_commande) {
        $sql = "SELECT COUNT(*) FROM ligne_commande WHERE id_commande = :id_commande";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute(['id_commande' => $id_commande]);
        return $stmt->fetchColumn();
    }
    
    // --------------------------------------------------------
    // Vérifier si un produit est déjà dans une commande
    // --------------------------------------------------------
    public function produitExiste($id_commande, $id_produit) {
        $sql = "SELECT COUNT(*) FROM ligne_commande 
                WHERE id_commande = :id_commande AND id_produit = :id_produit";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([
            'id_commande' => $id_commande,
            'id_produit' => $id_produit
        ]);
        return $stmt->fetchColumn() > 0;
    }
}