<?php
// ============================================================
// CONTRÔLEUR : RapportControleur
// Fonctionnalités : Générer des rapports et statistiques
// ============================================================

require_once __DIR__ . '/../modeles/BaseDeDonnees.php';
require_once __DIR__ . '/../modeles/Vente.php';
require_once __DIR__ . '/../modeles/LigneVente.php';
require_once __DIR__ . '/../modeles/MouvementStock.php';
require_once __DIR__ . '/../modeles/Alerte.php';
require_once __DIR__ . '/../modeles/StockMagasin.php';
require_once __DIR__ . '/../modeles/StockRayon.php';

class RapportControleur {
    
    private $vente;
    private $ligneVente;
    private $mouvementStock;
    private $alerte;
    private $stockMagasin;
    private $stockRayon;
    private $conn;
    
    // Constructeur
    public function __construct() {
        $db = new Database();
        $this->conn = $db->connect();
        
        $this->vente = new Vente($this->conn);
        $this->ligneVente = new LigneVente($this->conn);
        $this->mouvementStock = new MouvementStock($this->conn);
        $this->alerte = new Alerte($this->conn);
        $this->stockMagasin = new StockMagasin($this->conn);
        $this->stockRayon = new StockRayon($this->conn);
    }
    
    // --------------------------------------------------------
    // VENTES DU JOUR
    // --------------------------------------------------------
    public function ventesDuJour() {
        return $this->vente->getTotalDuJour();
    }
    
    // --------------------------------------------------------
    // VENTES PAR PÉRIODE
    // --------------------------------------------------------
    public function ventesParPeriode($date_debut, $date_fin) {
        return $this->vente->getTotalParPeriode($date_debut, $date_fin);
    }
    
    // --------------------------------------------------------
    // TOP PRODUITS LES PLUS VENDUS
    // --------------------------------------------------------
    public function produitsPlusVendus($limite = 10) {
        return $this->ligneVente->getPlusVendus($limite);
    }
    
    // --------------------------------------------------------
    // MOUVEMENTS PAR PÉRIODE
    // --------------------------------------------------------
    public function mouvementsParPeriode($date_debut, $date_fin) {
        return $this->mouvementStock->getByPeriode($date_debut, $date_fin);
    }
    
    // --------------------------------------------------------
    // ENTRÉES DU JOUR
    // --------------------------------------------------------
    public function entreesDuJour() {
        return $this->mouvementStock->getEntreesDuJour();
    }
    
    // --------------------------------------------------------
    // SORTIES DU JOUR
    // --------------------------------------------------------
    public function sortiesDuJour() {
        return $this->mouvementStock->getSortiesDuJour();
    }
    
    // --------------------------------------------------------
    // PRODUITS EN ALERTE MAGASIN
    // --------------------------------------------------------
    public function produitsEnAlerteMagasin() {
        return $this->stockMagasin->getProduitsEnAlerte();
    }
    
    // --------------------------------------------------------
    // PRODUITS PROCHES PÉREMPTION MAGASIN
    // --------------------------------------------------------
    public function produitsProchesPeremptionMagasin($jours = 7) {
        return $this->stockMagasin->getProchesPeremption($jours);
    }
    
    // --------------------------------------------------------
    // ÉTAT COMPLET DES STOCKS
    // --------------------------------------------------------
    public function etatCompletStocks() {
        
        $stock_magasin = $this->stockMagasin->getAll();
        $stock_rayons = $this->stockRayon->getAll();
        
        $valeur_magasin = 0;
        foreach ($stock_magasin as $s) {
            $valeur_magasin += $s['quantite'] * $s['prix_achat'];
        }
        
        $valeur_rayons = 0;
        foreach ($stock_rayons as $s) {
            $valeur_rayons += $s['quantite'] * $s['prix_vente'];
        }
        
        return [
            'stock_magasin' => $stock_magasin,
            'stock_rayons' => $stock_rayons,
            'valeur_magasin' => $valeur_magasin,
            'valeur_rayons' => $valeur_rayons,
            'valeur_totale' => $valeur_magasin + $valeur_rayons
        ];
    }
    
    // --------------------------------------------------------
    // RÉSUMÉ QUOTIDIEN
    // --------------------------------------------------------
    public function resumeQuotidien() {
        
        $ventes = $this->ventesDuJour();
        $entrees = $this->entreesDuJour();
        $sorties = $this->sortiesDuJour();
        $alertes = $this->alerte->compterNonTraitees();
        $produits_alerte = $this->stockMagasin->getProduitsEnAlerte();
        
        return [
            'date' => date('d/m/Y'),
            'nb_ventes' => $ventes['nb_ventes'],
            'montant_ventes' => $ventes['montant_total'],
            'nb_entrees' => $entrees['nb_entrees'],
            'qte_entrees' => $entrees['total_quantite'],
            'nb_sorties' => $sorties['nb_sorties'],
            'qte_sorties' => $sorties['total_quantite'],
            'alertes_non_traitees' => $alertes,
            'produits_en_alerte' => count($produits_alerte)
        ];
    }
    
    // --------------------------------------------------------
    // VENTES PAR RAYON
    // --------------------------------------------------------
    public function ventesParRayon($date_debut, $date_fin) {
        $sql = "SELECT r.nom AS rayon, 
                    COUNT(v.id_vente) AS nb_ventes,
                    COALESCE(SUM(lv.quantite * lv.prix_unitaire), 0) AS montant_total
                FROM vente v 
                JOIN rayon r ON v.id_rayon = r.id_rayon 
                JOIN ligne_vente lv ON v.id_vente = lv.id_vente 
                WHERE DATE(v.date_vente) BETWEEN :date_debut AND :date_fin 
                GROUP BY r.id_rayon 
                ORDER BY montant_total DESC";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([
            'date_debut' => $date_debut,
            'date_fin' => $date_fin
        ]);
        return $stmt->fetchAll();
    }
    
    // --------------------------------------------------------
    // VENTES PAR MODE DE PAIEMENT
    // --------------------------------------------------------
    public function ventesParModePaiement($date_debut, $date_fin) {
        $sql = "SELECT mode_paiement, 
                    COUNT(*) AS nb_ventes,
                    COALESCE(SUM(lv.quantite * lv.prix_unitaire), 0) AS montant_total
                FROM vente v 
                JOIN ligne_vente lv ON v.id_vente = lv.id_vente 
                WHERE DATE(v.date_vente) BETWEEN :date_debut AND :date_fin 
                GROUP BY mode_paiement";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([
            'date_debut' => $date_debut,
            'date_fin' => $date_fin
        ]);
        return $stmt->fetchAll();
    }
}