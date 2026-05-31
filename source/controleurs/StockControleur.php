<?php
// ============================================================
// CONTRÔLEUR : StockControleur
// Fonctionnalités : Importer inventaire (magasin et rayon)
// ============================================================

// Inclure les modèles nécessaires
require_once __DIR__ . '/../modeles/BaseDeDonnees.php';
require_once __DIR__ . '/../modeles/StockMagasin.php';
require_once __DIR__ . '/../modeles/StockRayon.php';
require_once __DIR__ . '/../modeles/MouvementStock.php';
require_once __DIR__ . '/../modeles/Alerte.php';
require_once __DIR__ . '/../modeles/Notification.php';
require_once __DIR__ . '/../../configuration/constantes.php';

class StockControleur {
    
    private $stockMagasin;
    private $stockRayon;
    private $mouvementStock;
    private $alerte;
    private $notification;
    
    // Constructeur
    public function __construct() {
        $db = new Database();
        $conn = $db->connect();
        
        $this->stockMagasin = new StockMagasin($conn);
        $this->stockRayon = new StockRayon($conn);
        $this->mouvementStock = new MouvementStock($conn);
        $this->alerte = new Alerte($conn);
        $this->notification = new Notification($conn);
    }
    
    // --------------------------------------------------------
    // IMPORTER INVENTAIRE MAGASIN
    // Appelé par : Magasinier
    // --------------------------------------------------------
    public function importerInventaireMagasin($id_produit, $quantite, $date_peremption, $id_utilisateur) {
        
        // 1. Récupérer le stock actuel avant modification
        $stock_actuel = $this->stockMagasin->getByProduit($id_produit);
        $stock_avant = $stock_actuel ? $stock_actuel['quantite'] : 0;
        
        // 2. Ajouter la quantité au stock magasin
        $this->stockMagasin->ajouter($id_produit, $quantite, $date_peremption);
        
        // 3. Calculer le nouveau stock
        $stock_apres = $stock_avant + $quantite;
        
        // 4. Enregistrer le mouvement
        $this->mouvementStock->enregistrer(
            MVT_ENTREE,          // type_mouvement
            'MAGASIN',           // localisation
            $quantite,           // quantite
            $stock_avant,        // stock_avant
            $stock_apres,        // stock_apres
            $id_produit,         // id_produit
            $id_utilisateur,     // id_utilisateur
            null,                // id_rayon (null pour magasin)
            'Import inventaire magasin' // raison
        );
        
        return true;
    }
    
    // --------------------------------------------------------
    // IMPORTER INVENTAIRE RAYON
    // Appelé par : Caissière
    // --------------------------------------------------------
    public function importerInventaireRayon($id_produit, $id_rayon, $quantite, $date_peremption, $id_utilisateur) {
        
        // 1. Récupérer le stock actuel avant modification
        $stock_actuel = $this->stockRayon->getByProduitEtRayon($id_produit, $id_rayon);
        $stock_avant = $stock_actuel ? $stock_actuel['quantite'] : 0;
        
        // 2. Ajouter la quantité au stock rayon
        $this->stockRayon->ajouter($id_produit, $id_rayon, $quantite, $date_peremption);
        
        // 3. Calculer le nouveau stock
        $stock_apres = $stock_avant + $quantite;
        
        // 4. Enregistrer le mouvement
        $this->mouvementStock->enregistrer(
            MVT_ENTREE,          // type_mouvement
            'RAYON',             // localisation
            $quantite,           // quantite
            $stock_avant,        // stock_avant
            $stock_apres,        // stock_apres
            $id_produit,         // id_produit
            $id_utilisateur,     // id_utilisateur
            $id_rayon,           // id_rayon
            'Import inventaire rayon' // raison
        );
        
        return true;
    }
}