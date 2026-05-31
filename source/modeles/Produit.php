<?php
// ============================================================
// MODÈLE : Produit
// Table : produit
// ============================================================

class Produit {
    
    private $conn;
    
    // Constructeur
    public function __construct($conn) {
        $this->conn = $conn;
    }
    
    // --------------------------------------------------------
    // Récupérer TOUS les produits
    // --------------------------------------------------------
    public function getAll() {
        $sql = "SELECT p.*, c.nom AS nom_categorie, f.nom AS nom_fournisseur 
                FROM produit p 
                LEFT JOIN categorie c ON p.id_categorie = c.id_categorie 
                LEFT JOIN fournisseur f ON p.id_fournisseur = f.id_fournisseur 
                ORDER BY p.nom ASC";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll();
    }
    
    // --------------------------------------------------------
    // Récupérer UN produit par son ID
    // --------------------------------------------------------
    public function getById($id) {
        $sql = "SELECT p.*, c.nom AS nom_categorie, f.nom AS nom_fournisseur 
                FROM produit p 
                LEFT JOIN categorie c ON p.id_categorie = c.id_categorie 
                LEFT JOIN fournisseur f ON p.id_fournisseur = f.id_fournisseur 
                WHERE p.id_produit = :id";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute(['id' => $id]);
        return $stmt->fetch();
    }
    
    // --------------------------------------------------------
    // Récupérer UN produit par son code-barre
    // --------------------------------------------------------
    public function getByCodeBarre($code_barre) {
        $sql = "SELECT * FROM produit WHERE code_barre = :code_barre";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute(['code_barre' => $code_barre]);
        return $stmt->fetch();
    }
    
    // --------------------------------------------------------
    // Ajouter un produit
    // --------------------------------------------------------
    public function ajouter($code_barre, $nom, $description, $prix_achat, $prix_vente, $seuil_alerte_magasin, $seuil_alerte_rayon, $date_peremption_defaut, $id_categorie, $id_fournisseur) {
        $sql = "INSERT INTO produit (code_barre, nom, description, prix_achat, prix_vente, seuil_alerte_magasin, seuil_alerte_rayon, date_peremption_defaut, id_categorie, id_fournisseur) 
                VALUES (:code_barre, :nom, :description, :prix_achat, :prix_vente, :seuil_alerte_magasin, :seuil_alerte_rayon, :date_peremption_defaut, :id_categorie, :id_fournisseur)";
        $stmt = $this->conn->prepare($sql);
        return $stmt->execute([
            'code_barre' => $code_barre,
            'nom' => $nom,
            'description' => $description,
            'prix_achat' => $prix_achat,
            'prix_vente' => $prix_vente,
            'seuil_alerte_magasin' => $seuil_alerte_magasin,
            'seuil_alerte_rayon' => $seuil_alerte_rayon,
            'date_peremption_defaut' => $date_peremption_defaut,
            'id_categorie' => $id_categorie,
            'id_fournisseur' => $id_fournisseur
        ]);
    }
    
    // --------------------------------------------------------
    // Modifier un produit
    // --------------------------------------------------------
    public function modifier($id, $code_barre, $nom, $description, $prix_achat, $prix_vente, $seuil_alerte_magasin, $seuil_alerte_rayon, $date_peremption_defaut, $id_categorie, $id_fournisseur) {
        $sql = "UPDATE produit 
                SET code_barre = :code_barre, nom = :nom, description = :description, 
                    prix_achat = :prix_achat, prix_vente = :prix_vente, 
                    seuil_alerte_magasin = :seuil_alerte_magasin, seuil_alerte_rayon = :seuil_alerte_rayon, 
                    date_peremption_defaut = :date_peremption_defaut, 
                    id_categorie = :id_categorie, id_fournisseur = :id_fournisseur 
                WHERE id_produit = :id";
        $stmt = $this->conn->prepare($sql);
        return $stmt->execute([
            'id' => $id,
            'code_barre' => $code_barre,
            'nom' => $nom,
            'description' => $description,
            'prix_achat' => $prix_achat,
            'prix_vente' => $prix_vente,
            'seuil_alerte_magasin' => $seuil_alerte_magasin,
            'seuil_alerte_rayon' => $seuil_alerte_rayon,
            'date_peremption_defaut' => $date_peremption_defaut,
            'id_categorie' => $id_categorie,
            'id_fournisseur' => $id_fournisseur
        ]);
    }
    
    // --------------------------------------------------------
    // Désactiver un produit
    // --------------------------------------------------------
    public function desactiver($id) {
        $sql = "UPDATE produit SET est_actif = 0 WHERE id_produit = :id";
        $stmt = $this->conn->prepare($sql);
        return $stmt->execute(['id' => $id]);
    }
    
    // --------------------------------------------------------
    // Activer un produit
    // --------------------------------------------------------
    public function activer($id) {
        $sql = "UPDATE produit SET est_actif = 1 WHERE id_produit = :id";
        $stmt = $this->conn->prepare($sql);
        return $stmt->execute(['id' => $id]);
    }
    
    // --------------------------------------------------------
    // Supprimer un produit
    // --------------------------------------------------------
    public function supprimer($id) {
        $sql = "DELETE FROM produit WHERE id_produit = :id";
        $stmt = $this->conn->prepare($sql);
        return $stmt->execute(['id' => $id]);
    }
    
    // --------------------------------------------------------
    // Récupérer les produits par catégorie
    // --------------------------------------------------------
    public function getByCategorie($id_categorie) {
        $sql = "SELECT p.*, c.nom AS nom_categorie, f.nom AS nom_fournisseur 
            FROM produit p 
            LEFT JOIN categorie c ON p.id_categorie = c.id_categorie 
            LEFT JOIN fournisseur f ON p.id_fournisseur = f.id_fournisseur 
            WHERE p.id_categorie = :id_categorie AND p.est_actif = 1 
            ORDER BY p.nom ASC";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute(['id_categorie' => $id_categorie]);
        return $stmt->fetchAll();
    }
    
    // --------------------------------------------------------
    // Récupérer les produits par fournisseur
    // --------------------------------------------------------
    public function getByFournisseur($id_fournisseur) {
        $sql = "SELECT * FROM produit WHERE id_fournisseur = :id_fournisseur AND est_actif = 1 ORDER BY nom ASC";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute(['id_fournisseur' => $id_fournisseur]);
        return $stmt->fetchAll();
    }
    
    // --------------------------------------------------------
    // Rechercher un produit par nom
    // --------------------------------------------------------
    public function rechercher($recherche) {
        $sql = "SELECT p.*, c.nom AS nom_categorie 
                FROM produit p 
                LEFT JOIN categorie c ON p.id_categorie = c.id_categorie 
                WHERE p.nom LIKE :recherche AND p.est_actif = 1 
                ORDER BY p.nom ASC";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute(['recherche' => '%' . $recherche . '%']);
        return $stmt->fetchAll();
    }
}