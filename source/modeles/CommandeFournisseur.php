<?php
// ============================================================
// MODÈLE : CommandeFournisseur
// Table : commande_fournisseur
// ============================================================

class CommandeFournisseur {
    
    private $conn;
    
    // Constructeur
    public function __construct($conn) {
        $this->conn = $conn;
    }
    
    // --------------------------------------------------------
    // Récupérer TOUTES les commandes
    // --------------------------------------------------------
    public function getAll() {
        $sql = "SELECT c.*, f.nom AS nom_fournisseur,
                    u.nom AS nom_gestionnaire, u.prenom AS prenom_gestionnaire
                FROM commande_fournisseur c 
                JOIN fournisseur f ON c.id_fournisseur = f.id_fournisseur 
                JOIN utilisateur u ON c.id_gestionnaire = u.id_utilisateur 
                ORDER BY c.date_commande DESC";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll();
    }
    
    // --------------------------------------------------------
    // Récupérer UNE commande par son ID
    // --------------------------------------------------------
    public function getById($id) {
        $sql = "SELECT c.*, f.nom AS nom_fournisseur, f.email AS email_fournisseur, f.telephone AS tel_fournisseur,
                    u.nom AS nom_gestionnaire, u.prenom AS prenom_gestionnaire
                FROM commande_fournisseur c 
                JOIN fournisseur f ON c.id_fournisseur = f.id_fournisseur 
                JOIN utilisateur u ON c.id_gestionnaire = u.id_utilisateur 
                WHERE c.id_commande = :id";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute(['id' => $id]);
        return $stmt->fetch();
    }
    
    // --------------------------------------------------------
    // Récupérer les commandes par fournisseur
    // --------------------------------------------------------
    public function getByFournisseur($id_fournisseur) {
        $sql = "SELECT c.*, u.nom AS nom_gestionnaire, u.prenom AS prenom_gestionnaire
                FROM commande_fournisseur c 
                JOIN utilisateur u ON c.id_gestionnaire = u.id_utilisateur 
                WHERE c.id_fournisseur = :id_fournisseur 
                ORDER BY c.date_commande DESC";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute(['id_fournisseur' => $id_fournisseur]);
        return $stmt->fetchAll();
    }
    
    // --------------------------------------------------------
    // Récupérer les commandes par statut
    // --------------------------------------------------------
    public function getByStatut($statut) {
        $sql = "SELECT c.*, f.nom AS nom_fournisseur,
                    u.nom AS nom_gestionnaire, u.prenom AS prenom_gestionnaire
                FROM commande_fournisseur c 
                JOIN fournisseur f ON c.id_fournisseur = f.id_fournisseur 
                JOIN utilisateur u ON c.id_gestionnaire = u.id_utilisateur 
                WHERE c.statut = :statut 
                ORDER BY c.date_commande DESC";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute(['statut' => $statut]);
        return $stmt->fetchAll();
    }
    
    // --------------------------------------------------------
    // Créer une commande (statut par défaut : ENVOYEE)
    // --------------------------------------------------------
    public function creer($id_fournisseur, $id_gestionnaire) {
        // Générer un numéro de commande unique
        $numero = 'CMD-' . date('Ymd') . '-' . rand(100, 999);
        
        $sql = "INSERT INTO commande_fournisseur (numero_commande, statut, id_fournisseur, id_gestionnaire) 
                VALUES (:numero_commande, 'ENVOYEE', :id_fournisseur, :id_gestionnaire)";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([
            'numero_commande' => $numero,
            'id_fournisseur' => $id_fournisseur,
            'id_gestionnaire' => $id_gestionnaire
        ]);
        
        // Retourner l'ID de la commande créée
        return $this->conn->lastInsertId();
    }
    
    // --------------------------------------------------------
    // Changer le statut d'une commande
    // --------------------------------------------------------
    public function changerStatut($id, $statut) {
        $sql = "UPDATE commande_fournisseur SET statut = :statut WHERE id_commande = :id";
        $stmt = $this->conn->prepare($sql);
        return $stmt->execute([
            'id' => $id,
            'statut' => $statut
        ]);
    }
    
    // --------------------------------------------------------
    // Annuler une commande
    // --------------------------------------------------------
    public function annuler($id) {
        return $this->changerStatut($id, 'ANNULEE');
    }
    
    // --------------------------------------------------------
    // Marquer une commande comme envoyée
    // --------------------------------------------------------
    public function marquerEnvoyee($id) {
        return $this->changerStatut($id, 'ENVOYEE');
    }
    
    // --------------------------------------------------------
    // Marquer une commande comme livrée
    // --------------------------------------------------------
    public function marquerLivree($id) {
        return $this->changerStatut($id, 'LIVREE');
    }
    
    // --------------------------------------------------------
    // Supprimer une commande
    // --------------------------------------------------------
    public function supprimer($id) {
        $sql = "DELETE FROM commande_fournisseur WHERE id_commande = :id";
        $stmt = $this->conn->prepare($sql);
        return $stmt->execute(['id' => $id]);
    }
    
    // --------------------------------------------------------
    // Récupérer les commandes en attente
    // --------------------------------------------------------
    public function getEnAttente() {
        return $this->getByStatut('EN_ATTENTE');
    }
    
    // --------------------------------------------------------
    // Compter les commandes par statut
    // --------------------------------------------------------
    public function compterParStatut($statut) {
        $sql = "SELECT COUNT(*) FROM commande_fournisseur WHERE statut = :statut";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute(['statut' => $statut]);
        return $stmt->fetchColumn();
    }
    
    // --------------------------------------------------------
    // Rechercher une commande par numéro
    // --------------------------------------------------------
    public function rechercher($recherche) {
        $sql = "SELECT c.*, f.nom AS nom_fournisseur,
                    u.nom AS nom_gestionnaire, u.prenom AS prenom_gestionnaire
                FROM commande_fournisseur c 
                JOIN fournisseur f ON c.id_fournisseur = f.id_fournisseur 
                JOIN utilisateur u ON c.id_gestionnaire = u.id_utilisateur 
                WHERE c.numero_commande LIKE :recherche 
                ORDER BY c.date_commande DESC";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute(['recherche' => '%' . $recherche . '%']);
        return $stmt->fetchAll();
    }
}