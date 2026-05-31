<?php
// ============================================================
// MODÈLE : Rayon
// Table : rayon
// ============================================================

class Rayon {
    
    private $conn;
    
    // Constructeur
    public function __construct($conn) {
        $this->conn = $conn;
    }
    
    // --------------------------------------------------------
    // Récupérer TOUS les rayons
    // --------------------------------------------------------
    public function getAll() {
        $sql = "SELECT r.*, u.nom AS nom_caissiere, u.prenom AS prenom_caissiere 
                FROM rayon r 
                LEFT JOIN utilisateur u ON r.id_caissiere = u.id_utilisateur 
                ORDER BY r.nom ASC";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll();
    }
    
    // --------------------------------------------------------
    // Récupérer UN rayon par son ID
    // --------------------------------------------------------
    public function getById($id) {
        $sql = "SELECT r.*, u.nom AS nom_caissiere, u.prenom AS prenom_caissiere 
                FROM rayon r 
                LEFT JOIN utilisateur u ON r.id_caissiere = u.id_utilisateur 
                WHERE r.id_rayon = :id";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute(['id' => $id]);
        return $stmt->fetch();
    }
    
    // --------------------------------------------------------
    // Ajouter un rayon
    // --------------------------------------------------------
    public function ajouter($nom, $emplacement) {
        $sql = "INSERT INTO rayon (nom, emplacement) VALUES (:nom, :emplacement)";
        $stmt = $this->conn->prepare($sql);
        return $stmt->execute([
            'nom' => $nom,
            'emplacement' => $emplacement
        ]);
    }
    
    // --------------------------------------------------------
    // Modifier un rayon
    // --------------------------------------------------------
    public function modifier($id, $nom, $emplacement) {
        $sql = "UPDATE rayon SET nom = :nom, emplacement = :emplacement WHERE id_rayon = :id";
        $stmt = $this->conn->prepare($sql);
        return $stmt->execute([
            'id' => $id,
            'nom' => $nom,
            'emplacement' => $emplacement
        ]);
    }
    
    // --------------------------------------------------------
    // Supprimer un rayon
    // --------------------------------------------------------
    public function supprimer($id) {
        $sql = "DELETE FROM rayon WHERE id_rayon = :id";
        $stmt = $this->conn->prepare($sql);
        return $stmt->execute(['id' => $id]);
    }
    
    // --------------------------------------------------------
    // Assigner une caissière à un rayon
    // --------------------------------------------------------
    public function assignerCaissiere($id_rayon, $id_caissiere) {
        $sql = "UPDATE rayon SET id_caissiere = :id_caissiere WHERE id_rayon = :id_rayon";
        $stmt = $this->conn->prepare($sql);
        return $stmt->execute([
            'id_rayon' => $id_rayon,
            'id_caissiere' => $id_caissiere
        ]);
    }
    
    // --------------------------------------------------------
    // Récupérer le rayon d'une caissière
    // --------------------------------------------------------
    public function getByCaissiere($id_caissiere) {
        $sql = "SELECT * FROM rayon WHERE id_caissiere = :id_caissiere";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute(['id_caissiere' => $id_caissiere]);
        return $stmt->fetch();
    }
}