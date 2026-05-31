<?php
// ============================================================
// CONTRÔLEUR : TransfertControleur
// Fonctionnalité : Transférer des produits du magasin vers un rayon
// ============================================================

require_once __DIR__ . '/../modeles/BaseDeDonnees.php';
require_once __DIR__ . '/../modeles/StockMagasin.php';
require_once __DIR__ . '/../modeles/StockRayon.php';
require_once __DIR__ . '/../modeles/MouvementStock.php';
require_once __DIR__ . '/../modeles/Alerte.php';
require_once __DIR__ . '/../modeles/Notification.php';
require_once __DIR__ . '/../modeles/Utilisateur.php';
require_once __DIR__ . '/../../configuration/constantes.php';

class TransfertControleur {
    
    private $stockMagasin;
    private $stockRayon;
    private $mouvementStock;
    private $alerte;
    private $notification;
    private $utilisateur;
    private $conn;
    
    // Constructeur
    public function __construct() {
        $db = new Database();
        $this->conn = $db->connect();
        
        $this->stockMagasin = new StockMagasin($this->conn);
        $this->stockRayon = new StockRayon($this->conn);
        $this->mouvementStock = new MouvementStock($this->conn);
        $this->alerte = new Alerte($this->conn);
        $this->notification = new Notification($this->conn);
        $this->utilisateur = new Utilisateur($this->conn);
    }
    
    // --------------------------------------------------------
    // EFFECTUER UN TRANSFERT MAGASIN → RAYON
    // Appelé par : Magasinier
    // --------------------------------------------------------
    public function transferer($id_produit, $id_rayon, $quantite, $date_peremption, $id_magasinier) {
        
        // 1. Vérifier que le stock magasin est suffisant
        $stockMagasin = $this->stockMagasin->getByProduit($id_produit);
        
        if (!$stockMagasin) {
            return "Ce produit n'existe pas dans le stock magasin.";
        }
        
        $stock_magasin_avant = $stockMagasin['quantite'];
        
        if ($stock_magasin_avant < $quantite) {
            return "Stock magasin insuffisant. Disponible : " . $stock_magasin_avant;
        }
        
        // 2. Récupérer le stock rayon actuel avant modification
        $stockRayon = $this->stockRayon->getByProduitEtRayon($id_produit, $id_rayon);
        $stock_rayon_avant = $stockRayon ? $stockRayon['quantite'] : 0;
        
        // 3. Retirer du stock magasin
        $retrait = $this->stockMagasin->retirer($id_produit, $quantite);
        if (!$retrait) {
            return "Erreur lors du retrait du stock magasin.";
        }
        
        // 4. Ajouter au stock rayon
        $this->stockRayon->ajouter($id_produit, $id_rayon, $quantite, $date_peremption);
        
        // 5. Calculer les nouveaux stocks
        $stock_magasin_apres = $stock_magasin_avant - $quantite;
        $stock_rayon_apres = $stock_rayon_avant + $quantite;
        
        // 6. Enregistrer UN SEUL mouvement dans l'historique
        $this->mouvementStock->enregistrer(
            MVT_TRANSFERT,
            'MAGASIN',
            $quantite,
            $stock_magasin_avant,
            $stock_magasin_apres,
            $id_produit,
            $id_magasinier,
            $id_rayon,
            'Transfert magasin → ' . $this->getNomRayon($id_rayon)
        );
        
        // 7. Notifier la caissière du rayon
        $rayon = $this->getRayonById($id_rayon);
        if ($rayon && $rayon['id_caissiere']) {
            $sql = "SELECT nom FROM produit WHERE id_produit = :id";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute(['id' => $id_produit]);
            $produit = $stmt->fetch();
            
            $sql = "INSERT INTO notification (message, id_alerte, id_utilisateur) 
                    VALUES (:message, 1, :id_utilisateur)";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([
                'message' => "Réapprovisionnement reçu : " . $quantite . " " . $produit['nom'] . " transférés dans votre rayon " . $rayon['nom'],
                'id_utilisateur' => $rayon['id_caissiere']
            ]);
        }
        
        // 8. Notifier le(s) gestionnaire(s)
        $gestionnaires = $this->utilisateur->getByRole('GESTIONNAIRE');
        $sql = "SELECT nom FROM produit WHERE id_produit = :id";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute(['id' => $id_produit]);
        $produit = $stmt->fetch();
        
        foreach ($gestionnaires as $g) {
            $sql = "INSERT INTO notification (message, id_alerte, id_utilisateur) 
                    VALUES (:message, 1, :id_utilisateur)";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([
                'message' => "Transfert magasin → rayon : " . $quantite . " " . $produit['nom'] . " vers " . $rayon['nom'],
                'id_utilisateur' => $g['id_utilisateur']
            ]);
        }
        
        // 9. Vérifier si le stock magasin est passé sous le seuil après transfert
        $this->verifierSeuilMagasin($id_produit, $stock_magasin_apres);
        
        // 10. Vérifier si le stock rayon est passé sous le seuil
        $this->verifierSeuilRayon($id_produit, $id_rayon, $stock_rayon_apres);
        
        return [
            'succes' => true,
            'stock_magasin_avant' => $stock_magasin_avant,
            'stock_magasin_apres' => $stock_magasin_apres,
            'stock_rayon_avant' => $stock_rayon_avant,
            'stock_rayon_apres' => $stock_rayon_apres
        ];
    }
    
    // --------------------------------------------------------
    // Vérifier si le stock magasin est passé sous le seuil
    // --------------------------------------------------------
    private function verifierSeuilMagasin($id_produit, $stock_actuel) {
        $sql = "SELECT nom, seuil_alerte_magasin FROM produit WHERE id_produit = :id";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute(['id' => $id_produit]);
        $produit = $stmt->fetch();
        
        if ($produit && $stock_actuel <= $produit['seuil_alerte_magasin']) {
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
    // Vérifier si le stock rayon est sous le seuil
    // --------------------------------------------------------
    private function verifierSeuilRayon($id_produit, $id_rayon, $stock_actuel) {
        $sql = "SELECT nom, seuil_alerte_rayon FROM produit WHERE id_produit = :id";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute(['id' => $id_produit]);
        $produit = $stmt->fetch();
        
        if ($produit && $stock_actuel <= $produit['seuil_alerte_rayon']) {
            $this->alerte->creer(
                ALERTE_STOCK_BAS_RAYON,
                ALERTE_INFO,
                "Stock bas rayon : " . $produit['nom'] . " (" . $stock_actuel . " unités restantes)",
                $id_produit,
                $id_rayon
            );
        }
    }
    
    // --------------------------------------------------------
    // Récupérer un rayon par son ID
    // --------------------------------------------------------
    private function getRayonById($id_rayon) {
        $sql = "SELECT * FROM rayon WHERE id_rayon = :id";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute(['id' => $id_rayon]);
        return $stmt->fetch();
    }
    
    // --------------------------------------------------------
    // Récupérer le nom d'un rayon par son ID
    // --------------------------------------------------------
    private function getNomRayon($id_rayon) {
        $sql = "SELECT nom FROM rayon WHERE id_rayon = :id";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute(['id' => $id_rayon]);
        $resultat = $stmt->fetch();
        return $resultat ? $resultat['nom'] : 'Rayon inconnu';
    }
}