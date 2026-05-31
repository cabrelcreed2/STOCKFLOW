<?php
// ============================================================
// MODÈLE : Notification
// Table : notification
// ============================================================

class Notification {
    
    private $conn;
    
    // Constructeur
    public function __construct($conn) {
        $this->conn = $conn;
    }
    
    // --------------------------------------------------------
    // Récupérer TOUTES les notifications
    // --------------------------------------------------------
    public function getAll() {
        $sql = "SELECT n.*, a.type_alerte, a.niveau AS niveau_alerte,
                    u.nom AS nom_destinataire, u.prenom AS prenom_destinataire
                FROM notification n 
                JOIN alerte a ON n.id_alerte = a.id_alerte 
                JOIN utilisateur u ON n.id_utilisateur = u.id_utilisateur 
                ORDER BY n.date_envoi DESC";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll();
    }
    
    // --------------------------------------------------------
    // Récupérer UNE notification par son ID
    // --------------------------------------------------------
    public function getById($id) {
        $sql = "SELECT n.*, a.type_alerte, a.niveau AS niveau_alerte, a.message AS message_alerte,
                    u.nom AS nom_destinataire, u.prenom AS prenom_destinataire
                FROM notification n 
                JOIN alerte a ON n.id_alerte = a.id_alerte 
                JOIN utilisateur u ON n.id_utilisateur = u.id_utilisateur 
                WHERE n.id_notification = :id";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute(['id' => $id]);
        return $stmt->fetch();
    }
    
    // --------------------------------------------------------
    // Récupérer les notifications d'un utilisateur
    // --------------------------------------------------------
    public function getByUtilisateur($id_utilisateur) {
        $sql = "SELECT n.*, a.type_alerte, a.niveau AS niveau_alerte
                FROM notification n 
                JOIN alerte a ON n.id_alerte = a.id_alerte 
                WHERE n.id_utilisateur = :id_utilisateur 
                ORDER BY n.date_envoi DESC";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute(['id_utilisateur' => $id_utilisateur]);
        return $stmt->fetchAll();
    }
    
    // --------------------------------------------------------
    // Récupérer les notifications non lues d'un utilisateur
    // --------------------------------------------------------
    public function getNonLues($id_utilisateur) {
        $sql = "SELECT n.*, a.type_alerte, a.niveau AS niveau_alerte
                FROM notification n 
                JOIN alerte a ON n.id_alerte = a.id_alerte 
                WHERE n.id_utilisateur = :id_utilisateur AND n.est_lue = 0 
                ORDER BY n.date_envoi DESC";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute(['id_utilisateur' => $id_utilisateur]);
        return $stmt->fetchAll();
    }
    
    // --------------------------------------------------------
    // Créer une notification
    // --------------------------------------------------------
    public function creer($id_alerte, $id_utilisateur, $message) {
        $sql = "INSERT INTO notification (message, id_alerte, id_utilisateur) 
                VALUES (:message, :id_alerte, :id_utilisateur)";
        $stmt = $this->conn->prepare($sql);
        return $stmt->execute([
            'message' => $message,
            'id_alerte' => $id_alerte,
            'id_utilisateur' => $id_utilisateur
        ]);
    }
    
    // --------------------------------------------------------
    // Marquer une notification comme lue
    // --------------------------------------------------------
    public function marquerLue($id) {
        $sql = "UPDATE notification SET est_lue = 1 WHERE id_notification = :id";
        $stmt = $this->conn->prepare($sql);
        return $stmt->execute(['id' => $id]);
    }
    
    // --------------------------------------------------------
    // Marquer toutes les notifications d'un utilisateur comme lues
    // --------------------------------------------------------
    public function marquerToutLu($id_utilisateur) {
        $sql = "UPDATE notification SET est_lue = 1 
                WHERE id_utilisateur = :id_utilisateur AND est_lue = 0";
        $stmt = $this->conn->prepare($sql);
        return $stmt->execute(['id_utilisateur' => $id_utilisateur]);
    }
    
    // --------------------------------------------------------
    // Compter les notifications non lues d'un utilisateur
    // --------------------------------------------------------
    public function compterNonLues($id_utilisateur) {
        $sql = "SELECT COUNT(*) FROM notification 
                WHERE id_utilisateur = :id_utilisateur AND est_lue = 0";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute(['id_utilisateur' => $id_utilisateur]);
        return $stmt->fetchColumn();
    }
    
    // --------------------------------------------------------
    // Supprimer une notification
    // --------------------------------------------------------
    public function supprimer($id) {
        $sql = "DELETE FROM notification WHERE id_notification = :id";
        $stmt = $this->conn->prepare($sql);
        return $stmt->execute(['id' => $id]);
    }
    
    // --------------------------------------------------------
    // Supprimer les vieilles notifications (plus de 30 jours)
    // --------------------------------------------------------
    public function supprimerAnciennes($jours = 30) {
        $sql = "DELETE FROM notification 
                WHERE date_envoi < DATE_SUB(NOW(), INTERVAL :jours DAY)";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(':jours', (int)$jours, PDO::PARAM_INT);
        return $stmt->execute();
    }
}