<?php
// ============================================================
// MODÈLE : Utilisateur
// Table : utilisateur
// ============================================================

class Utilisateur {
    
    private $conn;
    
    // Constructeur
    public function __construct($conn) {
        $this->conn = $conn;
    }
    
    // --------------------------------------------------------
    // Récupérer TOUS les utilisateurs
    // --------------------------------------------------------
    public function getAll() {
        $sql = "SELECT * FROM utilisateur ORDER BY nom ASC";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll();
    }
    
    // --------------------------------------------------------
    // Récupérer UN utilisateur par son ID
    // --------------------------------------------------------
    public function getById($id) {
        $sql = "SELECT * FROM utilisateur WHERE id_utilisateur = :id";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute(['id' => $id]);
        return $stmt->fetch();
    }
    
    // --------------------------------------------------------
    // Récupérer UN utilisateur par son email
    // --------------------------------------------------------
    public function getByEmail($email) {
        $sql = "SELECT * FROM utilisateur WHERE email = :email";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute(['email' => $email]);
        return $stmt->fetch();
    }
    
    // --------------------------------------------------------
    // Ajouter un utilisateur
    // --------------------------------------------------------
    public function ajouter($nom, $prenom, $email, $mot_de_passe, $role) {
        $sql = "INSERT INTO utilisateur (nom, prenom, email, mot_de_passe, role) 
                VALUES (:nom, :prenom, :email, :mot_de_passe, :role)";
        $stmt = $this->conn->prepare($sql);
        return $stmt->execute([
            'nom' => $nom,
            'prenom' => $prenom,
            'email' => $email,
            'mot_de_passe' => $mot_de_passe,
            'role' => $role
        ]);
    }
    
    // --------------------------------------------------------
    // Modifier un utilisateur
    // --------------------------------------------------------
    public function modifier($id, $nom, $prenom, $email, $role) {
        $sql = "UPDATE utilisateur 
                SET nom = :nom, prenom = :prenom, email = :email, role = :role 
                WHERE id_utilisateur = :id";
        $stmt = $this->conn->prepare($sql);
        return $stmt->execute([
            'id' => $id,
            'nom' => $nom,
            'prenom' => $prenom,
            'email' => $email,
            'role' => $role
        ]);
    }
    
    // --------------------------------------------------------
    // Modifier le mot de passe
    // --------------------------------------------------------
    public function modifierMotDePasse($id, $mot_de_passe) {
        $sql = "UPDATE utilisateur SET mot_de_passe = :mot_de_passe WHERE id_utilisateur = :id";
        $stmt = $this->conn->prepare($sql);
        return $stmt->execute([
            'id' => $id,
            'mot_de_passe' => $mot_de_passe
        ]);
    }
    
    // --------------------------------------------------------
    // Désactiver un utilisateur
    // --------------------------------------------------------
    public function desactiver($id) {
        $sql = "UPDATE utilisateur SET est_actif = 0 WHERE id_utilisateur = :id";
        $stmt = $this->conn->prepare($sql);
        return $stmt->execute(['id' => $id]);
    }
    
    // --------------------------------------------------------
    // Activer un utilisateur
    // --------------------------------------------------------
    public function activer($id) {
        $sql = "UPDATE utilisateur SET est_actif = 1 WHERE id_utilisateur = :id";
        $stmt = $this->conn->prepare($sql);
        return $stmt->execute(['id' => $id]);
    }
    
    // --------------------------------------------------------
    // Supprimer un utilisateur
    // --------------------------------------------------------
    public function supprimer($id) {
        $sql = "DELETE FROM utilisateur WHERE id_utilisateur = :id";
        $stmt = $this->conn->prepare($sql);
        return $stmt->execute(['id' => $id]);
    }
    
    // --------------------------------------------------------
    // Récupérer les utilisateurs par rôle
    // --------------------------------------------------------
    public function getByRole($role) {
        $sql = "SELECT * FROM utilisateur WHERE role = :role AND est_actif = 1 ORDER BY nom ASC";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute(['role' => $role]);
        return $stmt->fetchAll();
    }
    
    // --------------------------------------------------------
    // Vérifier si un email existe déjà
    // --------------------------------------------------------
    public function emailExiste($email, $id_exclu = null) {
        $sql = "SELECT COUNT(*) FROM utilisateur WHERE email = :email";
        $params = ['email' => $email];
        
        if ($id_exclu !== null) {
            $sql .= " AND id_utilisateur != :id";
            $params['id'] = $id_exclu;
        }
        
        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchColumn() > 0;
    }
}