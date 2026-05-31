<?php
// ============================================================
// CONTRÔLEUR : AdministrationControleur
// Fonctionnalités : Gérer utilisateurs, gérer rayons
// ============================================================

require_once __DIR__ . '/../modeles/BaseDeDonnees.php';
require_once __DIR__ . '/../modeles/Utilisateur.php';
require_once __DIR__ . '/../modeles/Rayon.php';
require_once __DIR__ . '/../../configuration/constantes.php';

class AdministrationControleur {
    
    private $utilisateur;
    private $rayon;
    private $conn;
    
    // Constructeur
    public function __construct() {
        $db = new Database();
        $this->conn = $db->connect();
        
        $this->utilisateur = new Utilisateur($this->conn);
        $this->rayon = new Rayon($this->conn);
    }
    
    // ============================================================
    // GESTION DES UTILISATEURS
    // ============================================================
    
    // --------------------------------------------------------
    // Récupérer tous les utilisateurs
    // --------------------------------------------------------
    public function listeUtilisateurs() {
        return $this->utilisateur->getAll();
    }
    
    // --------------------------------------------------------
    // Récupérer un utilisateur par son ID
    // --------------------------------------------------------
    public function getUtilisateur($id) {
        return $this->utilisateur->getById($id);
    }
    
    // --------------------------------------------------------
    // Ajouter un utilisateur
    // --------------------------------------------------------
    public function ajouterUtilisateur($nom, $prenom, $email, $mot_de_passe, $role) {
        
        // Vérifier que l'email n'existe pas déjà
        if ($this->utilisateur->emailExiste($email)) {
            return "Cet email est déjà utilisé.";
        }
        
        // Vérifier que le rôle est valide
        $roles_valides = [ROLE_ADMIN, ROLE_GESTIONNAIRE, ROLE_MAGASINIER, ROLE_CAISSIERE];
        if (!in_array($role, $roles_valides)) {
            return "Rôle invalide.";
        }
        
        // Ajouter l'utilisateur
        $resultat = $this->utilisateur->ajouter($nom, $prenom, $email, $mot_de_passe, $role);
        
        if ($resultat) {
            return $this->conn->lastInsertId();
        }
        return "Erreur lors de l'ajout.";
    }
    
    // --------------------------------------------------------
    // Modifier un utilisateur
    // --------------------------------------------------------
    public function modifierUtilisateur($id, $nom, $prenom, $email, $role) {
        
        // Vérifier que l'utilisateur existe
        $utilisateur = $this->utilisateur->getById($id);
        if (!$utilisateur) {
            return "Utilisateur introuvable.";
        }
        
        // Vérifier que l'email n'existe pas déjà (sauf pour cet utilisateur)
        if ($this->utilisateur->emailExiste($email, $id)) {
            return "Cet email est déjà utilisé par un autre utilisateur.";
        }
        
        // Vérifier que le rôle est valide
        $roles_valides = [ROLE_ADMIN, ROLE_GESTIONNAIRE, ROLE_MAGASINIER, ROLE_CAISSIERE];
        if (!in_array($role, $roles_valides)) {
            return "Rôle invalide.";
        }
        
        return $this->utilisateur->modifier($id, $nom, $prenom, $email, $role);
    }
    
    // --------------------------------------------------------
    // Désactiver un utilisateur
    // --------------------------------------------------------
    public function desactiverUtilisateur($id) {
        $utilisateur = $this->utilisateur->getById($id);
        if (!$utilisateur) {
            return "Utilisateur introuvable.";
        }
        return $this->utilisateur->desactiver($id);
    }
    
    // --------------------------------------------------------
    // Activer un utilisateur
    // --------------------------------------------------------
    public function activerUtilisateur($id) {
        $utilisateur = $this->utilisateur->getById($id);
        if (!$utilisateur) {
            return "Utilisateur introuvable.";
        }
        return $this->utilisateur->activer($id);
    }
    
    // --------------------------------------------------------
    // Supprimer un utilisateur
    // --------------------------------------------------------
    public function supprimerUtilisateur($id) {
        $utilisateur = $this->utilisateur->getById($id);
        if (!$utilisateur) {
            return "Utilisateur introuvable.";
        }
        return $this->utilisateur->supprimer($id);
    }
    
    // --------------------------------------------------------
    // Récupérer les utilisateurs par rôle
    // --------------------------------------------------------
    public function listeCaissieres() {
        return $this->utilisateur->getByRole(ROLE_CAISSIERE);
    }
    
    // ============================================================
    // GESTION DES RAYONS
    // ============================================================
    
    // --------------------------------------------------------
    // Récupérer tous les rayons
    // --------------------------------------------------------
    public function listeRayons() {
        return $this->rayon->getAll();
    }
    
    // --------------------------------------------------------
    // Récupérer un rayon par son ID
    // --------------------------------------------------------
    public function getRayon($id) {
        return $this->rayon->getById($id);
    }
    
    // --------------------------------------------------------
    // Ajouter un rayon
    // --------------------------------------------------------
    public function ajouterRayon($nom, $emplacement) {
        return $this->rayon->ajouter($nom, $emplacement);
    }
    
    // --------------------------------------------------------
    // Modifier un rayon
    // --------------------------------------------------------
    public function modifierRayon($id, $nom, $emplacement) {
        $rayon = $this->rayon->getById($id);
        if (!$rayon) {
            return "Rayon introuvable.";
        }
        return $this->rayon->modifier($id, $nom, $emplacement);
    }
    
    // --------------------------------------------------------
    // Supprimer un rayon
    // --------------------------------------------------------
    public function supprimerRayon($id) {
        $rayon = $this->rayon->getById($id);
        if (!$rayon) {
            return "Rayon introuvable.";
        }
        return $this->rayon->supprimer($id);
    }
    
    // --------------------------------------------------------
    // Assigner une caissière à un rayon
    // --------------------------------------------------------
    public function assignerCaissiere($id_rayon, $id_caissiere) {
        
        // Vérifier que le rayon existe
        $rayon = $this->rayon->getById($id_rayon);
        if (!$rayon) {
            return "Rayon introuvable.";
        }
        
        // Vérifier que la caissière existe
        $caissiere = $this->utilisateur->getById($id_caissiere);
        if (!$caissiere || $caissiere['role'] !== ROLE_CAISSIERE) {
            return "Caissière invalide.";
        }
        
        return $this->rayon->assignerCaissiere($id_rayon, $id_caissiere);
    }
}