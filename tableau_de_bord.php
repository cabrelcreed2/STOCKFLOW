<?php
session_start();

if (!isset($_SESSION['utilisateur'])) {
    header('Location: /superstock/source/vues/authentification/connexion.php');
    exit;
}

$role = $_SESSION['utilisateur']['role'];

switch ($role) {
    case 'ADMIN':
        header('Location: /superstock/source/vues/tableaux_de_bord/administrateur.php');
        break;
    case 'GESTIONNAIRE':
        header('Location: /superstock/source/vues/tableaux_de_bord/gestionnaire.php');
        break;
    case 'MAGASINIER':
        header('Location: /superstock/source/vues/tableaux_de_bord/magasinier.php');
        break;
    case 'CAISSIERE':
        header('Location: /superstock/source/vues/tableaux_de_bord/caissiere.php');
        break;
    default:
        header('Location: /superstock/source/vues/authentification/connexion.php');
        break;
}
exit;