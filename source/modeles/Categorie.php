<?php
// ============================================================
// MODÈLE : Categorie
// Table : categorie
// ============================================================

class Categorie {
    
    private $conn;
    
    // Constructeur : récupère la connexion à la base
    public function __construct($conn) {
        $this->conn = $conn;
    }
    
    // --------------------------------------------------------
    // Récupérer TOUTES les catégories
    // --------------------------------------------------------
    public function getAll() {
        $sql = "SELECT * FROM categorie ORDER BY nom ASC";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll();
    }
    
    // --------------------------------------------------------
    // Récupérer UNE catégorie par son ID
    // --------------------------------------------------------
    public function getById($id) {
        $sql = "SELECT * FROM categorie WHERE id_categorie = :id";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute(['id' => $id]);
        return $stmt->fetch();
    }
    
    // --------------------------------------------------------
    // Ajouter une catégorie
    // --------------------------------------------------------
    public function ajouter($nom, $description) {
        $sql = "INSERT INTO categorie (nom, description) VALUES (:nom, :description)";
        $stmt = $this->conn->prepare($sql);
        return $stmt->execute([
            'nom' => $nom,
            'description' => $description
        ]);
    }
    
    // --------------------------------------------------------
    // Modifier une catégorie
    // --------------------------------------------------------
    public function modifier($id, $nom, $description) {
        $sql = "UPDATE categorie SET nom = :nom, description = :description WHERE id_categorie = :id";
        $stmt = $this->conn->prepare($sql);
        return $stmt->execute([
            'id' => $id,
            'nom' => $nom,
            'description' => $description
        ]);
    }
    
    // --------------------------------------------------------
    // Supprimer une catégorie
    // --------------------------------------------------------
    public function supprimer($id) {
        $sql = "DELETE FROM categorie WHERE id_categorie = :id";
        $stmt = $this->conn->prepare($sql);
        return $stmt->execute(['id' => $id]);
    }
}