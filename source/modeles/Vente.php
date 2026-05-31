<?php
// ============================================================
// MODÈLE : Vente
// Table : vente
// ============================================================

class Vente {
    
    private $conn;
    
    // Constructeur
    public function __construct($conn) {
        $this->conn = $conn;
    }
    
    // --------------------------------------------------------
    // Récupérer TOUTES les ventes
    // --------------------------------------------------------
    public function getAll() {
        $sql = "SELECT v.*, u.nom AS nom_caissiere, u.prenom AS prenom_caissiere,
                    r.nom AS nom_rayon
                FROM vente v 
                JOIN utilisateur u ON v.id_caissiere = u.id_utilisateur 
                JOIN rayon r ON v.id_rayon = r.id_rayon 
                ORDER BY v.date_vente DESC";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll();
    }
    
    // --------------------------------------------------------
    // Récupérer UNE vente par son ID
    // --------------------------------------------------------
    public function getById($id) {
        $sql = "SELECT v.*, u.nom AS nom_caissiere, u.prenom AS prenom_caissiere,
                    r.nom AS nom_rayon
                FROM vente v 
                JOIN utilisateur u ON v.id_caissiere = u.id_utilisateur 
                JOIN rayon r ON v.id_rayon = r.id_rayon 
                WHERE v.id_vente = :id";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute(['id' => $id]);
        return $stmt->fetch();
    }
    
    // --------------------------------------------------------
    // Récupérer les ventes d'un rayon
    // --------------------------------------------------------
    public function getByRayon($id_rayon) {
        $sql = "SELECT v.*, u.nom AS nom_caissiere, u.prenom AS prenom_caissiere
                FROM vente v 
                JOIN utilisateur u ON v.id_caissiere = u.id_utilisateur 
                WHERE v.id_rayon = :id_rayon 
                ORDER BY v.date_vente DESC";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute(['id_rayon' => $id_rayon]);
        return $stmt->fetchAll();
    }
    
    // --------------------------------------------------------
    // Récupérer les ventes d'une caissière
    // --------------------------------------------------------
    public function getByCaissiere($id_caissiere) {
        $sql = "SELECT v.*, r.nom AS nom_rayon
                FROM vente v 
                JOIN rayon r ON v.id_rayon = r.id_rayon 
                WHERE v.id_caissiere = :id_caissiere 
                ORDER BY v.date_vente DESC";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute(['id_caissiere' => $id_caissiere]);
        return $stmt->fetchAll();
    }
    
    // --------------------------------------------------------
    // Récupérer les ventes du jour
    // --------------------------------------------------------
    public function getVentesDuJour() {
        $sql = "SELECT v.*, u.nom AS nom_caissiere, u.prenom AS prenom_caissiere,
                    r.nom AS nom_rayon
                FROM vente v 
                JOIN utilisateur u ON v.id_caissiere = u.id_utilisateur 
                JOIN rayon r ON v.id_rayon = r.id_rayon 
                WHERE DATE(v.date_vente) = CURDATE() 
                ORDER BY v.date_vente DESC";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll();
    }
    
    // --------------------------------------------------------
    // Récupérer les ventes par période
    // --------------------------------------------------------
    public function getByPeriode($date_debut, $date_fin) {
        $sql = "SELECT v.*, u.nom AS nom_caissiere, u.prenom AS prenom_caissiere,
                    r.nom AS nom_rayon
                FROM vente v 
                JOIN utilisateur u ON v.id_caissiere = u.id_utilisateur 
                JOIN rayon r ON v.id_rayon = r.id_rayon 
                WHERE DATE(v.date_vente) BETWEEN :date_debut AND :date_fin 
                ORDER BY v.date_vente DESC";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([
            'date_debut' => $date_debut,
            'date_fin' => $date_fin
        ]);
        return $stmt->fetchAll();
    }
    
    // --------------------------------------------------------
    // Créer une vente
    // Retourne l'ID de la vente créée
    // --------------------------------------------------------
    public function creer($id_caissiere, $id_rayon, $mode_paiement) {
        $sql = "INSERT INTO vente (id_caissiere, id_rayon, mode_paiement) 
                VALUES (:id_caissiere, :id_rayon, :mode_paiement)";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([
            'id_caissiere' => $id_caissiere,
            'id_rayon' => $id_rayon,
            'mode_paiement' => $mode_paiement
        ]);
        
        return $this->conn->lastInsertId();
    }
    
    // --------------------------------------------------------
    // Supprimer une vente
    // --------------------------------------------------------
    public function supprimer($id) {
        $sql = "DELETE FROM vente WHERE id_vente = :id";
        $stmt = $this->conn->prepare($sql);
        return $stmt->execute(['id' => $id]);
    }
    
    // --------------------------------------------------------
    // Total des ventes du jour
    // --------------------------------------------------------
    public function getTotalDuJour() {
        $sql = "SELECT COALESCE(COUNT(*), 0) AS nb_ventes,
                    COALESCE(SUM(lv.quantite * lv.prix_unitaire), 0) AS montant_total
                FROM vente v 
                JOIN ligne_vente lv ON v.id_vente = lv.id_vente 
                WHERE DATE(v.date_vente) = CURDATE()";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute();
        return $stmt->fetch();
    }
    
    // --------------------------------------------------------
    // Total des ventes par période
    // --------------------------------------------------------
    public function getTotalParPeriode($date_debut, $date_fin) {
        $sql = "SELECT COALESCE(COUNT(DISTINCT v.id_vente), 0) AS nb_ventes,
                    COALESCE(SUM(lv.quantite * lv.prix_unitaire), 0) AS montant_total
                FROM vente v 
                JOIN ligne_vente lv ON v.id_vente = lv.id_vente 
                WHERE DATE(v.date_vente) BETWEEN :date_debut AND :date_fin";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([
            'date_debut' => $date_debut,
            'date_fin' => $date_fin
        ]);
        return $stmt->fetch();
    }
}