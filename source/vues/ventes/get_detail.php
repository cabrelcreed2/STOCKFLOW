<?php
require_once __DIR__ . '/../../../source/modeles/BaseDeDonnees.php';
require_once __DIR__ . '/../../../source/modeles/LigneVente.php';

$id_vente = $_GET['id'] ?? 0;

$db = new Database();
$conn = $db->connect();
$ligneVenteModel = new LigneVente($conn);

$lignes = $ligneVenteModel->getByVente($id_vente);
$total = $ligneVenteModel->getTotal($id_vente);
?>

<?php if (empty($lignes)): ?>
    <p class="text-muted text-center py-3">Aucun détail trouvé pour cette vente.</p>
<?php else: ?>
    <table class="table table-striped">
        <thead>
            <tr>
                <th>Produit</th>
                <th>Prix unitaire</th>
                <th>Quantité</th>
                <th>Sous-total</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($lignes as $l): ?>
                <tr>
                    <td><?php echo htmlspecialchars($l['nom_produit']); ?></td>
                    <td><?php echo number_format($l['prix_unitaire'], 0, ',', ' '); ?> FCFA</td>
                    <td><?php echo $l['quantite']; ?></td>
                    <td><?php echo number_format($l['sous_total'], 0, ',', ' '); ?> FCFA</td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <div class="border-top pt-2 mt-2 text-end">
        <strong>Total : <?php echo number_format($total, 0, ',', ' '); ?> FCFA</strong>
    </div>
<?php endif; ?>