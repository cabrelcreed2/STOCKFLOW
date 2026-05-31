<?php
// ============================================================
// MODÈLE : Alerte
// Table : alerte
// ============================================================

class Alerte {
    
    private $conn;
    
    // Constructeur
    public function __construct($conn) {
        $this->conn = $conn;
    }
    
    // --------------------------------------------------------
    // Récupérer TOUTES les alertes
    // --------------------------------------------------------
    public function getAll() {
        $sql = "SELECT a.*, p.nom AS nom_produit, p.code_barre,
                    r.nom AS nom_rayon,
                    u.nom AS nom_utilisateur, u.prenom AS prenom_utilisateur
                FROM alerte a 
                JOIN produit p ON a.id_produit = p.id_produit 
                LEFT JOIN rayon r ON a.id_rayon = r.id_rayon 
                LEFT JOIN utilisateur u ON a.id_utilisateur = u.id_utilisateur 
                ORDER BY a.date_creation DESC";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll();
    }
    
    // --------------------------------------------------------
    // Récupérer UNE alerte par son ID
    // --------------------------------------------------------
    public function getById($id) {
        $sql = "SELECT a.*, p.nom AS nom_produit, p.code_barre,
                    r.nom AS nom_rayon,
                    u.nom AS nom_utilisateur, u.prenom AS prenom_utilisateur
                FROM alerte a 
                JOIN produit p ON a.id_produit = p.id_produit 
                LEFT JOIN rayon r ON a.id_rayon = r.id_rayon 
                LEFT JOIN utilisateur u ON a.id_utilisateur = u.id_utilisateur 
                WHERE a.id_alerte = :id";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute(['id' => $id]);
        return $stmt->fetch();
    }
    
    // --------------------------------------------------------
    // Récupérer les alertes non traitées
    // --------------------------------------------------------
    public function getNonTraitees() {
        $sql = "SELECT a.*, p.nom AS nom_produit, p.code_barre,
                    r.nom AS nom_rayon
                FROM alerte a 
                JOIN produit p ON a.id_produit = p.id_produit 
                LEFT JOIN rayon r ON a.id_rayon = r.id_rayon 
                WHERE a.est_traitee = 0 
                ORDER BY 
                    CASE a.niveau 
                        WHEN 'CRITIQUE' THEN 1 
                        WHEN 'ATTENTION' THEN 2 
                        WHEN 'INFO' THEN 3 
                    END,
                    a.date_creation DESC";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll();
    }
    
    // --------------------------------------------------------
    // Récupérer les alertes par niveau
    // --------------------------------------------------------
    public function getByNiveau($niveau) {
        $sql = "SELECT a.*, p.nom AS nom_produit, p.code_barre,
                    r.nom AS nom_rayon
                FROM alerte a 
                JOIN produit p ON a.id_produit = p.id_produit 
                LEFT JOIN rayon r ON a.id_rayon = r.id_rayon 
                WHERE a.niveau = :niveau AND a.est_traitee = 0 
                ORDER BY a.date_creation DESC";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute(['niveau' => $niveau]);
        return $stmt->fetchAll();
    }
    
    // --------------------------------------------------------
    // Récupérer les alertes par type
    // --------------------------------------------------------
    public function getByType($type_alerte) {
        $sql = "SELECT a.*, p.nom AS nom_produit, p.code_barre,
                    r.nom AS nom_rayon
                FROM alerte a 
                JOIN produit p ON a.id_produit = p.id_produit 
                LEFT JOIN rayon r ON a.id_rayon = r.id_rayon 
                WHERE a.type_alerte = :type_alerte 
                ORDER BY a.date_creation DESC";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute(['type_alerte' => $type_alerte]);
        return $stmt->fetchAll();
    }
    
    // --------------------------------------------------------
    // Récupérer les alertes d'un produit
    // --------------------------------------------------------
    public function getByProduit($id_produit) {
        $sql = "SELECT * FROM alerte 
                WHERE id_produit = :id_produit 
                ORDER BY date_creation DESC";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute(['id_produit' => $id_produit]);
        return $stmt->fetchAll();
    }
    
    // --------------------------------------------------------
    // Créer une alerte
    // --------------------------------------------------------
    public function creer($type_alerte, $niveau, $message, $id_produit, $id_rayon = null) {
        $sql = "INSERT INTO alerte (type_alerte, niveau, message, id_produit, id_rayon) 
                VALUES (:type_alerte, :niveau, :message, :id_produit, :id_rayon)";
        $stmt = $this->conn->prepare($sql);
        return $stmt->execute([
            'type_alerte' => $type_alerte,
            'niveau' => $niveau,
            'message' => $message,
            'id_produit' => $id_produit,
            'id_rayon' => $id_rayon
        ]);
    }
    
    // --------------------------------------------------------
    // Marquer une alerte comme traitée
    // --------------------------------------------------------
    public function traiter($id, $id_utilisateur) {
        $sql = "UPDATE alerte 
                SET est_traitee = 1, date_traitement = NOW(), id_utilisateur = :id_utilisateur 
                WHERE id_alerte = :id";
        $stmt = $this->conn->prepare($sql);
        return $stmt->execute([
            'id' => $id,
            'id_utilisateur' => $id_utilisateur
        ]);
    }
    
    // --------------------------------------------------------
    // Compter les alertes non traitées
    // --------------------------------------------------------
    public function compterNonTraitees() {
        $sql = "SELECT COUNT(*) FROM alerte WHERE est_traitee = 0";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute();
        return $stmt->fetchColumn();
    }
    
    // --------------------------------------------------------
    // Compter les alertes par niveau
    // --------------------------------------------------------
    public function compterParNiveau($niveau) {
        $sql = "SELECT COUNT(*) FROM alerte WHERE niveau = :niveau AND est_traitee = 0";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute(['niveau' => $niveau]);
        return $stmt->fetchColumn();
    }
    
    // --------------------------------------------------------
    // Supprimer une alerte
    // --------------------------------------------------------
    public function supprimer($id) {
        $sql = "DELETE FROM alerte WHERE id_alerte = :id";
        $stmt = $this->conn->prepare($sql);
        return $stmt->execute(['id' => $id]);
    }
}