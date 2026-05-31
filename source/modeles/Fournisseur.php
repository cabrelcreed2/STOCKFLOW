<?php
// ============================================================
// MODÈLE : Fournisseur
// Table : fournisseur
// ============================================================

class Fournisseur {
    
    private $conn;
    
    // Constructeur
    public function __construct($conn) {
        $this->conn = $conn;
    }
    
    // --------------------------------------------------------
    // Récupérer TOUS les fournisseurs
    // --------------------------------------------------------
    public function getAll() {
        $sql = "SELECT * FROM fournisseur ORDER BY nom ASC";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll();
    }
    
    // --------------------------------------------------------
    // Récupérer UN fournisseur par son ID
    // --------------------------------------------------------
    public function getById($id) {
        $sql = "SELECT * FROM fournisseur WHERE id_fournisseur = :id";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute(['id' => $id]);
        return $stmt->fetch();
    }
    
    // --------------------------------------------------------
    // Ajouter un fournisseur
    // --------------------------------------------------------
    public function ajouter($nom, $email = null, $telephone = '', $adresse = '') {
    // Si email est vide, on ne l'insère pas (pour éviter doublon chaîne vide)
    if (empty($email)) {
        $sql = "INSERT INTO fournisseur (nom, telephone, adresse) 
                VALUES (:nom, :telephone, :adresse)";
        $stmt = $this->conn->prepare($sql);
        return $stmt->execute([
            'nom' => $nom,
            'telephone' => $telephone,
            'adresse' => $adresse
        ]);
    } else {
        $sql = "INSERT INTO fournisseur (nom, email, telephone, adresse) 
                VALUES (:nom, :email, :telephone, :adresse)";
        $stmt = $this->conn->prepare($sql);
        return $stmt->execute([
            'nom' => $nom,
            'email' => $email,
            'telephone' => $telephone,
            'adresse' => $adresse
        ]);
    }
}
    
    // --------------------------------------------------------
    // Modifier un fournisseur
    // --------------------------------------------------------
    public function modifier($id, $nom, $email, $telephone, $adresse) {
        $sql = "UPDATE fournisseur 
                SET nom = :nom, email = :email, telephone = :telephone, adresse = :adresse 
                WHERE id_fournisseur = :id";
        $stmt = $this->conn->prepare($sql);
        return $stmt->execute([
            'id' => $id,
            'nom' => $nom,
            'email' => $email,
            'telephone' => $telephone,
            'adresse' => $adresse
        ]);
    }
    
    // --------------------------------------------------------
    // Supprimer un fournisseur
    // --------------------------------------------------------
    public function supprimer($id) {
        $sql = "DELETE FROM fournisseur WHERE id_fournisseur = :id";
        $stmt = $this->conn->prepare($sql);
        return $stmt->execute(['id' => $id]);
    }
    
    // --------------------------------------------------------
    // Récupérer les fournisseurs actifs uniquement
    // --------------------------------------------------------
    public function getActifs() {
        $sql = "SELECT * FROM fournisseur WHERE est_actif = 1 ORDER BY nom ASC";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll();
    }
}