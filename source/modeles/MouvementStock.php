<?php
// ============================================================
// MODÈLE : MouvementStock
// Table : mouvement_stock
// ============================================================

class MouvementStock {
    
    private $conn;
    
    // Constructeur
    public function __construct($conn) {
        $this->conn = $conn;
    }
    
    // --------------------------------------------------------
    // Récupérer TOUS les mouvements
    // --------------------------------------------------------
    public function getAll() {
        $sql = "SELECT m.*, p.nom AS nom_produit, p.code_barre,
                    u.nom AS nom_utilisateur, u.prenom AS prenom_utilisateur,
                    r.nom AS nom_rayon
                FROM mouvement_stock m 
                JOIN produit p ON m.id_produit = p.id_produit 
                JOIN utilisateur u ON m.id_utilisateur = u.id_utilisateur 
                LEFT JOIN rayon r ON m.id_rayon = r.id_rayon 
                ORDER BY m.date_mouvement DESC";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll();
    }
    
    // --------------------------------------------------------
    // Récupérer UN mouvement par son ID
    // --------------------------------------------------------
    public function getById($id) {
        $sql = "SELECT m.*, p.nom AS nom_produit, p.code_barre,
                    u.nom AS nom_utilisateur, u.prenom AS prenom_utilisateur,
                    r.nom AS nom_rayon
                FROM mouvement_stock m 
                JOIN produit p ON m.id_produit = p.id_produit 
                JOIN utilisateur u ON m.id_utilisateur = u.id_utilisateur 
                LEFT JOIN rayon r ON m.id_rayon = r.id_rayon 
                WHERE m.id_mouvement = :id";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute(['id' => $id]);
        return $stmt->fetch();
    }
    
    // --------------------------------------------------------
    // Récupérer les mouvements d'un produit
    // --------------------------------------------------------
    public function getByProduit($id_produit) {
        $sql = "SELECT m.*, u.nom AS nom_utilisateur, u.prenom AS prenom_utilisateur,
                    r.nom AS nom_rayon
                FROM mouvement_stock m 
                JOIN utilisateur u ON m.id_utilisateur = u.id_utilisateur 
                LEFT JOIN rayon r ON m.id_rayon = r.id_rayon 
                WHERE m.id_produit = :id_produit 
                ORDER BY m.date_mouvement DESC";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute(['id_produit' => $id_produit]);
        return $stmt->fetchAll();
    }
    
    // --------------------------------------------------------
    // Récupérer les mouvements par type
    // --------------------------------------------------------
    public function getByType($type) {
        $sql = "SELECT m.*, p.nom AS nom_produit, p.code_barre,
                    u.nom AS nom_utilisateur, u.prenom AS prenom_utilisateur,
                    r.nom AS nom_rayon
                FROM mouvement_stock m 
                JOIN produit p ON m.id_produit = p.id_produit 
                JOIN utilisateur u ON m.id_utilisateur = u.id_utilisateur 
                LEFT JOIN rayon r ON m.id_rayon = r.id_rayon 
                WHERE m.type_mouvement = :type 
                ORDER BY m.date_mouvement DESC";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute(['type' => $type]);
        return $stmt->fetchAll();
    }
    
    // --------------------------------------------------------
    // Récupérer les mouvements par période
    // --------------------------------------------------------
    public function getByPeriode($date_debut, $date_fin) {
        $sql = "SELECT m.*, p.nom AS nom_produit, p.code_barre,
                    u.nom AS nom_utilisateur, u.prenom AS prenom_utilisateur,
                    r.nom AS nom_rayon
                FROM mouvement_stock m 
                JOIN produit p ON m.id_produit = p.id_produit 
                JOIN utilisateur u ON m.id_utilisateur = u.id_utilisateur 
                LEFT JOIN rayon r ON m.id_rayon = r.id_rayon 
                WHERE DATE(m.date_mouvement) BETWEEN :date_debut AND :date_fin 
                ORDER BY m.date_mouvement DESC";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([
            'date_debut' => $date_debut,
            'date_fin' => $date_fin
        ]);
        return $stmt->fetchAll();
    }
    
    // --------------------------------------------------------
    // Enregistrer un mouvement
    // --------------------------------------------------------
    public function enregistrer($type_mouvement, $localisation, $quantite, $stock_avant, $stock_apres, $id_produit, $id_utilisateur, $id_rayon = null, $raison = null) {
        $sql = "INSERT INTO mouvement_stock (type_mouvement, localisation, quantite, stock_avant, stock_apres, raison, id_produit, id_utilisateur, id_rayon) 
                VALUES (:type_mouvement, :localisation, :quantite, :stock_avant, :stock_apres, :raison, :id_produit, :id_utilisateur, :id_rayon)";
        $stmt = $this->conn->prepare($sql);
        return $stmt->execute([
            'type_mouvement' => $type_mouvement,
            'localisation' => $localisation,
            'quantite' => $quantite,
            'stock_avant' => $stock_avant,
            'stock_apres' => $stock_apres,
            'raison' => $raison,
            'id_produit' => $id_produit,
            'id_utilisateur' => $id_utilisateur,
            'id_rayon' => $id_rayon
        ]);
    }
    
    // --------------------------------------------------------
    // Récupérer les entrées du jour
    // --------------------------------------------------------
    public function getEntreesDuJour() {
        $sql = "SELECT COUNT(*) AS nb_entrees, COALESCE(SUM(quantite), 0) AS total_quantite
                FROM mouvement_stock 
                WHERE type_mouvement = 'ENTREE' 
                    AND DATE(date_mouvement) = CURDATE()";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute();
        return $stmt->fetch();
    }
    
    // --------------------------------------------------------
    // Récupérer les sorties du jour
    // --------------------------------------------------------
    public function getSortiesDuJour() {
        $sql = "SELECT COUNT(*) AS nb_sorties, COALESCE(SUM(quantite), 0) AS total_quantite
                FROM mouvement_stock 
                WHERE type_mouvement IN ('VENTE', 'SORTIE') 
                    AND DATE(date_mouvement) = CURDATE()";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute();
        return $stmt->fetch();
    }
}