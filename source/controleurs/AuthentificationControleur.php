<?php
// ============================================================
// CONTRÔLEUR : AuthentificationControleur
// Fonctionnalités : Connexion, déconnexion
// ============================================================

require_once __DIR__ . '/../modeles/BaseDeDonnees.php';
require_once __DIR__ . '/../modeles/Utilisateur.php';
require_once __DIR__ . '/../../configuration/constantes.php';

class AuthentificationControleur {
    
    private $utilisateur;
    private $conn;
    
    // Constructeur
    public function __construct() {
        $db = new Database();
        $this->conn = $db->connect();
        $this->utilisateur = new Utilisateur($this->conn);
    }
    
    // --------------------------------------------------------
    // CONNEXION
    // --------------------------------------------------------
    public function connexion($email, $mot_de_passe) {
        
        // Vérifier que les champs ne sont pas vides
        if (empty($email) || empty($mot_de_passe)) {
            return [
                'succes' => false,
                'message' => 'Veuillez remplir tous les champs.'
            ];
        }
        
        // Chercher l'utilisateur par email
        $utilisateur = $this->utilisateur->getByEmail($email);
        
        // Vérifier si l'utilisateur existe
        if (!$utilisateur) {
            return [
                'succes' => false,
                'message' => 'Email ou mot de passe incorrect.'
            ];
        }
        
        // Vérifier si le compte est actif
        if (!$utilisateur['est_actif']) {
            return [
                'succes' => false,
                'message' => 'Votre compte est désactivé.'
            ];
        }
        
        // Vérifier le mot de passe
        $mot_de_passe_valide = false;
        
        // D'abord essayer avec password_verify() (pour les mots de passe hashés)
        if (password_verify($mot_de_passe, $utilisateur['mot_de_passe'])) {
            $mot_de_passe_valide = true;
        } 
        // Sinon, essayer comparaison en clair (pour les anciens mots de passe non hashés)
        elseif ($utilisateur['mot_de_passe'] === $mot_de_passe) {
            $mot_de_passe_valide = true;
            // Migration : hasher le mot de passe en BD pour la prochaine connexion
            $this->utilisateur->modifierMotDePasse($utilisateur['id_utilisateur'], $mot_de_passe);
            // Mettre à jour l'utilisateur en session avec le hash
            $utilisateur = $this->utilisateur->getByEmail($email);
        }
        
        if (!$mot_de_passe_valide) {
            return [
                'succes' => false,
                'message' => 'Email ou mot de passe incorrect.'
            ];
        }
        
        // Stocker l'utilisateur en session
        $_SESSION['utilisateur'] = $utilisateur;
        
        return [
            'succes' => true,
            'message' => 'Connexion réussie.',
            'utilisateur' => $utilisateur
        ];
    }
    
    // --------------------------------------------------------
    // DÉCONNEXION
    // --------------------------------------------------------
    public function deconnexion() {
        session_destroy();
        return [
            'succes' => true,
            'message' => 'Déconnexion réussie.'
        ];
    }
    
    // --------------------------------------------------------
    // VÉRIFIER SI UN UTILISATEUR EST CONNECTÉ
    // --------------------------------------------------------
    public function estConnecte() {
        return isset($_SESSION['utilisateur']);
    }
    
    // --------------------------------------------------------
    // RÉCUPÉRER L'UTILISATEUR CONNECTÉ
    // --------------------------------------------------------
    public function getUtilisateurConnecte() {
        if ($this->estConnecte()) {
            return $_SESSION['utilisateur'];
        }
        return null;
    }
    
    // --------------------------------------------------------
    // VÉRIFIER LE RÔLE DE L'UTILISATEUR
    // --------------------------------------------------------
    public function verifierRole($role_attendu) {
        $utilisateur = $this->getUtilisateurConnecte();
        if (!$utilisateur) {
            return false;
        }
        return $utilisateur['role'] === $role_attendu;
    }
    
    // --------------------------------------------------------
    // REDIRIGER VERS LE TABLEAU DE BORD SELON LE RÔLE
    // --------------------------------------------------------
    public function redirigerTableauDeBord() {
        $utilisateur = $this->getUtilisateurConnecte();
        if (!$utilisateur) {
            return 'source/vues/authentification/connexion.php';
        }
        
        switch ($utilisateur['role']) {
            case ROLE_ADMIN:
                return 'source/vues/tableaux_de_bord/administrateur.php';
            case ROLE_GESTIONNAIRE:
                return 'source/vues/tableaux_de_bord/gestionnaire.php';
            case ROLE_MAGASINIER:
                return 'source/vues/tableaux_de_bord/magasinier.php';
            case ROLE_CAISSIERE:
                return 'source/vues/tableaux_de_bord/caissiere.php';
            default:
                return 'source/vues/authentification/connexion.php';
        }
    }
}