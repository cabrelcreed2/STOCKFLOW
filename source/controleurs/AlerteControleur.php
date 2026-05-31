<?php
// ============================================================
// CONTRÔLEUR : AlerteControleur
// Fonctionnalités : Consulter et traiter les alertes
// ============================================================

require_once __DIR__ . '/../modeles/BaseDeDonnees.php';
require_once __DIR__ . '/../modeles/Alerte.php';
require_once __DIR__ . '/../modeles/Notification.php';
require_once __DIR__ . '/../../configuration/constantes.php';

class AlerteControleur {
    
    private $alerte;
    private $notification;
    private $conn;
    
    // Constructeur
    public function __construct() {
        $db = new Database();
        $this->conn = $db->connect();
        
        $this->alerte = new Alerte($this->conn);
        $this->notification = new Notification($this->conn);
    }
    
    // --------------------------------------------------------
    // Récupérer toutes les alertes
    // --------------------------------------------------------
    public function listeAlertes() {
        return $this->alerte->getAll();
    }
    
    // --------------------------------------------------------
    // Récupérer les alertes non traitées
    // --------------------------------------------------------
    public function listeAlertesNonTraitees() {
        return $this->alerte->getNonTraitees();
    }
    
    // --------------------------------------------------------
    // Récupérer une alerte par son ID
    // --------------------------------------------------------
    public function getAlerte($id) {
        return $this->alerte->getById($id);
    }
    
    // --------------------------------------------------------
    // Récupérer les alertes par niveau
    // --------------------------------------------------------
    public function listeAlertesParNiveau($niveau) {
        $niveaux_valides = [ALERTE_INFO, ALERTE_ATTENTION, ALERTE_CRITIQUE];
        if (!in_array($niveau, $niveaux_valides)) {
            return [];
        }
        return $this->alerte->getByNiveau($niveau);
    }
    
    // --------------------------------------------------------
    // Récupérer les alertes par type
    // --------------------------------------------------------
    public function listeAlertesParType($type) {
        return $this->alerte->getByType($type);
    }
    
    // --------------------------------------------------------
    // Récupérer les alertes d'un produit
    // --------------------------------------------------------
    public function listeAlertesProduit($id_produit) {
        return $this->alerte->getByProduit($id_produit);
    }
    
    // --------------------------------------------------------
    // Traiter une alerte
    // --------------------------------------------------------
    public function traiterAlerte($id_alerte, $id_utilisateur) {
        
        // Vérifier que l'alerte existe
        $alerte = $this->alerte->getById($id_alerte);
        if (!$alerte) {
            return "Alerte introuvable.";
        }
        
        // Vérifier qu'elle n'est pas déjà traitée
        if ($alerte['est_traitee']) {
            return "Cette alerte est déjà traitée.";
        }
        
        return $this->alerte->traiter($id_alerte, $id_utilisateur);
    }
    
    // --------------------------------------------------------
    // Compter les alertes non traitées
    // --------------------------------------------------------
    public function compterNonTraitees() {
        return $this->alerte->compterNonTraitees();
    }
    
    // --------------------------------------------------------
    // Compter par niveau
    // --------------------------------------------------------
    public function compterCritiques() {
        return $this->alerte->compterParNiveau(ALERTE_CRITIQUE);
    }
    
    public function compterAttention() {
        return $this->alerte->compterParNiveau(ALERTE_ATTENTION);
    }
    
    public function compterInfo() {
        return $this->alerte->compterParNiveau(ALERTE_INFO);
    }
    
    // --------------------------------------------------------
    // Supprimer une alerte
    // --------------------------------------------------------
    public function supprimerAlerte($id_alerte) {
        $alerte = $this->alerte->getById($id_alerte);
        if (!$alerte) {
            return "Alerte introuvable.";
        }
        return $this->alerte->supprimer($id_alerte);
    }
    
    // --------------------------------------------------------
    // Statistiques des alertes
    // --------------------------------------------------------
    public function getStatistiques() {
        return [
            'non_traitees' => $this->compterNonTraitees(),
            'critiques' => $this->compterCritiques(),
            'attention' => $this->compterAttention(),
            'info' => $this->compterInfo()
        ];
    }
    
    // --------------------------------------------------------
    // Récupérer les notifications d'un utilisateur
    // --------------------------------------------------------
    public function listeNotifications($id_utilisateur) {
        return $this->notification->getByUtilisateur($id_utilisateur);
    }
    
    // --------------------------------------------------------
    // Récupérer les notifications non lues
    // --------------------------------------------------------
    public function listeNotificationsNonLues($id_utilisateur) {
        return $this->notification->getNonLues($id_utilisateur);
    }
    
    // --------------------------------------------------------
    // Compter les notifications non lues
    // --------------------------------------------------------
    public function compterNotificationsNonLues($id_utilisateur) {
        return $this->notification->compterNonLues($id_utilisateur);
    }
    
    // --------------------------------------------------------
    // Marquer une notification comme lue
    // --------------------------------------------------------
    public function marquerNotificationLue($id_notification) {
        return $this->notification->marquerLue($id_notification);
    }
    
    // --------------------------------------------------------
    // Marquer toutes les notifications comme lues
    // --------------------------------------------------------
    public function marquerToutesLues($id_utilisateur) {
        return $this->notification->marquerToutLu($id_utilisateur);
    }
}