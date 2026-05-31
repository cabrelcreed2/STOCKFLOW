<?php
// ============================================================
// CONTRÔLEUR : CassePerimeControleur
// Fonctionnalité : Signaler une casse ou un produit périmé
// ============================================================

// Inclure les modèles nécessaires
require_once __DIR__ . '/../modeles/BaseDeDonnees.php';
require_once __DIR__ . '/../modeles/StockMagasin.php';
require_once __DIR__ . '/../modeles/StockRayon.php';
require_once __DIR__ . '/../modeles/MouvementStock.php';
require_once __DIR__ . '/../modeles/Alerte.php';
require_once __DIR__ . '/../modeles/Notification.php';
require_once __DIR__ . '/../modeles/Utilisateur.php';
require_once __DIR__ . '/../../configuration/constantes.php';

class CassePerimeControleur {
    
    private $conn; // AJOUT : Stockage de la connexion PDO globale
    private $stockMagasin;
    private $stockRayon;
    private $mouvementStock;
    private $alerte;
    private $notification;
    private $utilisateur;
    
    // Constructeur modifié pour accepter la connexion provenant de la vue
    public function __construct($db_conn) {
        $this->conn = $db_conn; // Initialisation de la connexion PDO
        
        $this->stockMagasin = new StockMagasin($db_conn);
        $this->stockRayon = new StockRayon($db_conn);
        $this->mouvementStock = new MouvementStock($db_conn);
        $this->alerte = new Alerte($db_conn);
        $this->notification = new Notification($db_conn);
        $this->utilisateur = new Utilisateur($db_conn);
    }
    
    // --------------------------------------------------------
    // SIGNALER CASSE/PÉRIMÉ DANS LE MAGASIN
    // Appelé par : Magasinier
    // --------------------------------------------------------
    public function signalerMagasin($id_produit, $type, $quantite, $commentaire, $id_utilisateur) {
        
        // 1. Vérifier que le type est valide
        if ($type !== 'CASSE' && $type !== 'PERIME') {
            return "Type invalide. Utilisez 'CASSE' ou 'PERIME'.";
        }
        
        // 2. Récupérer le stock actuel avant modification
        $stock_actuel = $this->stockMagasin->getByProduit($id_produit);
        
        if (!$stock_actuel) {
            return "Ce produit n'existe pas dans le stock magasin.";
        }
        
        $stock_avant = $stock_actuel['quantite'];
        
        // 3. Vérifier que le stock est suffisant
        if ($stock_avant < $quantite) {
            return "Stock insuffisant. Disponible : " . $stock_avant;
        }
        
        // 4. Retirer la quantité du stock magasin
        $this->stockMagasin->retirer($id_produit, $quantite);
        
        // 5. Calculer le nouveau stock
        $stock_apres = $stock_avant - $quantite;
        
        // 6. Déterminer la constante de mouvement à utiliser
        $type_mouvement = ($type === 'CASSE') ? MVT_CASSE : MVT_PERIME;
        
        // 7. Enregistrer le mouvement
        $this->mouvementStock->enregistrer(
            $type_mouvement,
            'MAGASIN',
            $quantite,
            $stock_avant,
            $stock_apres,
            $id_produit,
            $id_utilisateur,
            null,
            $commentaire
        );
        
        // 8. Créer une alerte pour informer le gestionnaire
        $type_alerte = ($type === 'CASSE') ? ALERTE_STOCK_BAS_MAGASIN : ALERTE_PERIME;
        $message = "Signalement " . strtolower($type) . " : " . $quantite . " unité(s) - " . $commentaire;
        
        $this->alerte->creer(
            $type_alerte,
            ALERTE_ATTENTION,
            $message,
            $id_produit,
            null
        );
        
        // 9. Notifier le(s) gestionnaire(s)
        $gestionnaires = $this->utilisateur->getByRole('GESTIONNAIRE');
        foreach ($gestionnaires as $gestionnaire) {
            $this->notification->creer(
                $this->conn->lastInsertId(), // FONCTIONNE MAINTENANT : $this->conn est défini
                $gestionnaire['id_utilisateur'],
                $message
            );
        }
        
        // 10. Vérifier si le stock magasin est passé sous le seuil
        $this->verifierSeuilMagasin($id_produit, $stock_apres);
        
        return true;
    }
    
    // --------------------------------------------------------
    // SIGNALER CASSE/PÉRIMÉ DANS LE RAYON
    // Appelé par : Caissière
    // --------------------------------------------------------
    public function signalerRayon($id_produit, $id_rayon, $type, $quantite, $commentaire, $id_utilisateur) {
        
        // 1. Vérifier que le type est valide
        if ($type !== 'CASSE' && $type !== 'PERIME') {
            return "Type invalide. Utilisez 'CASSE' ou 'PERIME'.";
        }
        
        // 2. Récupérer le stock actuel avant modification
        $stock_actuel = $this->stockRayon->getByProduitEtRayon($id_produit, $id_rayon);
        
        if (!$stock_actuel) {
            return "Ce produit n'existe pas dans ce rayon.";
        }
        
        $stock_avant = $stock_actuel['quantite'];
        
        // 3. Vérifier que le stock est suffisant
        if ($stock_avant < $quantite) {
            return "Stock insuffisant. Disponible : " . $stock_avant;
        }
        
        // 4. Retirer la quantité du stock rayon
        $this->stockRayon->retirer($id_produit, $id_rayon, $quantite);
        
        // 5. Calculer le nouveau stock
        $stock_apres = $stock_avant - $quantite;
        
        // 6. Déterminer la constante de mouvement
        $type_mouvement = ($type === 'CASSE') ? MVT_CASSE : MVT_PERIME;
        
        // 7. Enregistrer le mouvement
        $this->mouvementStock->enregistrer(
            $type_mouvement,
            'RAYON',
            $quantite,
            $stock_avant,
            $stock_apres,
            $id_produit,
            $id_utilisateur,
            $id_rayon,
            $commentaire
        );
        
        // 8. Créer une alerte
        $type_alerte = ($type === 'CASSE') ? ALERTE_STOCK_BAS_RAYON : ALERTE_PERIME;
        $message = "Signalement " . strtolower($type) . " rayon : " . $quantite . " unité(s) - " . $commentaire;
        
        $this->alerte->creer(
            $type_alerte,
            ALERTE_ATTENTION,
            $message,
            $id_produit,
            $id_rayon
        );
        
        // 9. Notifier le(s) gestionnaire(s)
        $gestionnaires = $this->utilisateur->getByRole('GESTIONNAIRE');
        $lastId = $this->conn->lastInsertId(); // Nettoyé : utilise la connexion existante
        
        foreach ($gestionnaires as $gestionnaire) {
            $this->notification->creer(
                $lastId,
                $gestionnaire['id_utilisateur'],
                $message
            );
        }
        
        // 10. Vérifier si le stock rayon est passé sous le seuil
        $this->verifierSeuilRayon($id_produit, $id_rayon, $stock_apres);
        
        return true;
    }
    
    // --------------------------------------------------------
    // Vérifier si le stock magasin est passé sous le seuil
    // --------------------------------------------------------
    private function verifierSeuilMagasin($id_produit, $stock_actuel) {
        // Nettoyé : plus besoin de recréer une instance Database()
        $sql = "SELECT nom, seuil_alerte_magasin FROM produit WHERE id_produit = :id";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute(['id' => $id_produit]);
        $produit = $stmt->fetch();
        
        if ($produit && $stock_actuel <= $produit['seuil_alerte_magasin']) {
            // Créer une alerte stock bas magasin
            $this->alerte->creer(
                ALERTE_STOCK_BAS_MAGASIN,
                ALERTE_ATTENTION,
                "Stock magasin bas : " . $produit['nom'] . " (" . $stock_actuel . " unités restantes)",
                $id_produit,
                null
            );
        }
    }
    
    // --------------------------------------------------------
    // Vérifier si le stock rayon est passé sous le seuil
    // --------------------------------------------------------
    private function verifierSeuilRayon($id_produit, $id_rayon, $stock_actuel) {
        // Nettoyé : plus besoin de recréer une instance Database()
        $sql = "SELECT nom, seuil_alerte_rayon FROM produit WHERE id_produit = :id";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute(['id' => $id_produit]);
        $produit = $stmt->fetch();
        
        if ($produit && $stock_actuel <= $produit['seuil_alerte_rayon']) {
            // Créer une alerte stock bas rayon
            $this->alerte->creer(
                ALERTE_STOCK_BAS_RAYON,
                ALERTE_ATTENTION,
                "Stock bas rayon : " . $produit['nom'] . " (" . $stock_actuel . " unités restantes)",
                $id_produit,
                $id_rayon
            );
        }
    }
}