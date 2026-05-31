<?php
// ============================================================
// CONTRÔLEUR : ProduitControleur
// Fonctionnalités : Gérer les produits (CRUD)
// ============================================================

require_once __DIR__ . '/../modeles/BaseDeDonnees.php';
require_once __DIR__ . '/../modeles/Produit.php';
require_once __DIR__ . '/../modeles/Categorie.php';
require_once __DIR__ . '/../modeles/Fournisseur.php';

class ProduitControleur {
    
    private $produit;
    private $categorie;
    private $fournisseur;
    private $conn;
    
    // Constructeur
    public function __construct() {
        $db = new Database();
        $this->conn = $db->connect();
        
        $this->produit = new Produit($this->conn);
        $this->categorie = new Categorie($this->conn);
        $this->fournisseur = new Fournisseur($this->conn);
    }
    
    // --------------------------------------------------------
    // Récupérer tous les produits
    // --------------------------------------------------------
    public function listeProduits() {
        return $this->produit->getAll();
    }
    
    // --------------------------------------------------------
    // Récupérer un produit
    // --------------------------------------------------------
    public function getProduit($id) {
        return $this->produit->getById($id);
    }
    
    // --------------------------------------------------------
    // Récupérer un produit par code-barre
    // --------------------------------------------------------
    public function getProduitByCodeBarre($code_barre) {
        return $this->produit->getByCodeBarre($code_barre);
    }
    
    // --------------------------------------------------------
    // Ajouter un produit
    // --------------------------------------------------------
    public function ajouterProduit($code_barre, $nom, $description, $prix_achat, $prix_vente, $seuil_alerte_magasin, $seuil_alerte_rayon, $date_peremption_defaut, $id_categorie, $id_fournisseur) {
        
        // Vérifier que le code-barre n'existe pas déjà
        $existant = $this->produit->getByCodeBarre($code_barre);
        if ($existant) {
            return "Ce code-barre existe déjà.";
        }
        
        // Vérifier que la catégorie existe
        $categorie = $this->categorie->getById($id_categorie);
        if (!$categorie) {
            return "Catégorie introuvable.";
        }
        
        // Vérifier que le fournisseur existe
        $fournisseur = $this->fournisseur->getById($id_fournisseur);
        if (!$fournisseur) {
            return "Fournisseur introuvable.";
        }
        
        // Vérifier les prix
        if ($prix_achat <= 0 || $prix_vente <= 0) {
            return "Les prix doivent être supérieurs à zéro.";
        }
        
        if ($prix_vente <= $prix_achat) {
            return "Le prix de vente doit être supérieur au prix d'achat.";
        }
        
        // Ajouter le produit
        $resultat = $this->produit->ajouter(
            $code_barre, $nom, $description, $prix_achat, $prix_vente,
            $seuil_alerte_magasin, $seuil_alerte_rayon, $date_peremption_defaut,
            $id_categorie, $id_fournisseur
        );
        
        if ($resultat) {
            return $this->conn->lastInsertId();
        }
        return "Erreur lors de l'ajout.";
    }
    
    // --------------------------------------------------------
    // Modifier un produit
    // --------------------------------------------------------
    public function modifierProduit($id, $code_barre, $nom, $description, $prix_achat, $prix_vente, $seuil_alerte_magasin, $seuil_alerte_rayon, $date_peremption_defaut, $id_categorie, $id_fournisseur) {
        
        // Vérifier que le produit existe
        $produit = $this->produit->getById($id);
        if (!$produit) {
            return "Produit introuvable.";
        }
        
        // Vérifier les prix
        if ($prix_achat <= 0 || $prix_vente <= 0) {
            return "Les prix doivent être supérieurs à zéro.";
        }
        
        if ($prix_vente <= $prix_achat) {
            return "Le prix de vente doit être supérieur au prix d'achat.";
        }
        
        return $this->produit->modifier(
            $id, $code_barre, $nom, $description, $prix_achat, $prix_vente,
            $seuil_alerte_magasin, $seuil_alerte_rayon, $date_peremption_defaut,
            $id_categorie, $id_fournisseur
        );
    }
    
    // --------------------------------------------------------
    // Désactiver un produit
    // --------------------------------------------------------
    public function desactiverProduit($id) {
        $produit = $this->produit->getById($id);
        if (!$produit) {
            return "Produit introuvable.";
        }
        return $this->produit->desactiver($id);
    }
    
    // --------------------------------------------------------
    // Activer un produit
    // --------------------------------------------------------
    public function activerProduit($id) {
        $produit = $this->produit->getById($id);
        if (!$produit) {
            return "Produit introuvable.";
        }
        return $this->produit->activer($id);
    }
    
    // --------------------------------------------------------
    // Supprimer un produit
    // --------------------------------------------------------
    public function supprimerProduit($id) {
        $produit = $this->produit->getById($id);
        if (!$produit) {
            return "Produit introuvable.";
        }
        return $this->produit->supprimer($id);
    }
    
    // --------------------------------------------------------
    // Rechercher des produits
    // --------------------------------------------------------
    public function rechercherProduits($recherche) {
        return $this->produit->rechercher($recherche);
    }
    
    // --------------------------------------------------------
    // Produits par catégorie
    // --------------------------------------------------------
    public function produitsParCategorie($id_categorie) {
        return $this->produit->getByCategorie($id_categorie);
    }
    
    // --------------------------------------------------------
    // Produits par fournisseur
    // --------------------------------------------------------
    public function produitsParFournisseur($id_fournisseur) {
        return $this->produit->getByFournisseur($id_fournisseur);
    }
    
    // --------------------------------------------------------
    // Récupérer toutes les catégories
    // --------------------------------------------------------
    public function listeCategories() {
        return $this->categorie->getAll();
    }
    
    // --------------------------------------------------------
    // Récupérer tous les fournisseurs actifs
    // --------------------------------------------------------
    public function listeFournisseursActifs() {
        return $this->fournisseur->getActifs();
    }
}