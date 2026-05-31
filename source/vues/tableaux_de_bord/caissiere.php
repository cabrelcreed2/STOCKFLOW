<?php
session_start();
if (!isset($_SESSION['utilisateur'])) {
    header('Location: ../authentification/connexion.php');
    exit;
}
$user = $_SESSION['utilisateur'];
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Caissière - Tableau de bord</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <h1>✅ Connexion réussie !</h1>
        <div class="alert alert-success">
            Bienvenue <strong><?php echo $user['prenom'] . ' ' . $user['nom']; ?></strong>
            <br>Rôle : <?php echo $user['role']; ?>
            <br>Email : <?php echo $user['email']; ?>
        </div>
        <a href="/superstock/deconnexion.php" class="btn btn-danger">Se déconnecter</a>
    </div>
</body>
</html>