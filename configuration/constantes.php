<?php
// CONSTANTES GLOBALES
// Projet : Gestion de Stock Supermarché

// Rôles des utilisateurs
define('ROLE_ADMIN', 'ADMIN');
define('ROLE_GESTIONNAIRE', 'GESTIONNAIRE');
define('ROLE_MAGASINIER', 'MAGASINIER');
define('ROLE_CAISSIERE', 'CAISSIERE');

// Types de mouvement de stock
define('MVT_ENTREE', 'ENTREE');
define('MVT_SORTIE', 'SORTIE');
define('MVT_TRANSFERT', 'TRANSFERT');
define('MVT_VENTE', 'VENTE');
define('MVT_CASSE', 'CASSE');
define('MVT_PERIME', 'PERIME');

// Statuts des commandes fournisseurs
define('CMD_ATTENTE', 'EN_ATTENTE');
define('CMD_ENVOYEE', 'ENVOYEE');
define('CMD_LIVREE', 'LIVREE');
define('CMD_ANNULEE', 'ANNULEE');

// Niveaux d'alerte
define('ALERTE_INFO', 'INFO');
define('ALERTE_ATTENTION', 'ATTENTION');
define('ALERTE_CRITIQUE', 'CRITIQUE');

// Types d'alerte
define('ALERTE_STOCK_BAS_MAGASIN', 'STOCK_BAS_MAGASIN');
define('ALERTE_STOCK_BAS_RAYON', 'STOCK_BAS_RAYON');
define('ALERTE_RUPTURE', 'RUPTURE');
define('ALERTE_PEREMPTION_PROCHAINE', 'PEREMPTION_PROCHAINE');
define('ALERTE_PERIME', 'PERIME');

// Modes de paiement
define('PAIEMENT_ESPECES', 'ESPECES');
define('PAIEMENT_CARTE', 'CARTE');
define('PAIEMENT_MIXTE', 'MIXTE');