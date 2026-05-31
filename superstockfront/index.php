<?php
session_start();

// Si l'utilisateur est déjà connecté, rediriger vers son tableau de bord
if (isset($_SESSION['utilisateur'])) {
    $role = $_SESSION['utilisateur']['role'];
    switch ($role) {
        case 'ADMIN':
            header('Location: source/vues/administrateur/tableau_de_bord_admin.php');
            break;
        case 'GESTIONNAIRE':
            header('Location: source/vues/gerant/tableau_de_bord_gerant.php');
            break;
        case 'MAGASINIER':
            header('Location: source/vues/magasinier/tableau_de_bord_magasinier.php');
            break;
        case 'CAISSIERE':
            header('Location: source/vues/caissier/tableau_de_bord_caissier.php');
            break;
    }
    exit;
}
// Sinon, afficher la page d'accueil
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>STOCKFLOW - Solution de gestion de stock</title>
    <link href="bootstrap-5.3.8-dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="bootstrap-5.3.8-dist/fontawesome-free-6.7.2-web/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Poppins', sans-serif; }
        body { background-color: #f8fafc; }
        :root { --primary: #f97316; --primary-dark: #ea580c; --primary-light: #fdba74; }
        .navbar { background: white; padding: 15px 0; box-shadow: 0 2px 15px rgba(0,0,0,0.05); }
        .navbar-brand { font-size: 28px; font-weight: 800; color: var(--primary) !important; }
        .navbar-brand span { color: var(--primary-dark); }
        .nav-link { font-weight: 500; color: #1e293b !important; margin: 0 10px; transition: all 0.3s; }
        .nav-link:hover { color: var(--primary) !important; }
        .btn-login {
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            color: white !important; padding: 8px 25px; border-radius: 50px;
            margin-left: 15px; transition: all 0.3s; text-decoration: none;
        }
        .btn-login:hover { transform: translateY(-2px); box-shadow: 0 5px 15px rgba(249,115,22,0.4); }
        .hero {
            position: relative; min-height: 85vh; display: flex; align-items: center;
            margin-top: 76px; overflow: hidden;
        }
        .hero::before {
            content: ''; position: absolute; top: 0; left: 0; right: 0; bottom: 0;
            background-image: url('supermarché.jpg'); background-size: cover;
            background-position: center; background-repeat: no-repeat; z-index: 0;
        }
        .hero::after {
            content: ''; position: absolute; top: 0; left: 0; right: 0; bottom: 0;
            background: rgba(0,0,0,0.55); z-index: 0;
        }
        .hero .container { position: relative; z-index: 1; }
        .hero h1 { font-size: 48px; font-weight: 800; color: white; margin-bottom: 20px; }
        .hero h1 span { color: var(--primary); }
        .hero p { font-size: 18px; color: white; margin-bottom: 30px; line-height: 1.8; }
        .btn-hero {
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            color: white; padding: 12px 35px; border-radius: 50px; font-weight: 600;
            text-decoration: none; display: inline-block; margin-right: 15px; transition: all 0.3s;
        }
        .btn-hero:hover { transform: translateY(-3px); box-shadow: 0 10px 25px rgba(249,115,22,0.3); color: white; }
        .actor-card {
            background: white; padding: 30px; border-radius: 20px; text-align: center;
            transition: all 0.3s; box-shadow: 0 5px 20px rgba(0,0,0,0.05); height: 100%;
            cursor: pointer; border-bottom: 4px solid transparent;
        }
        .actor-card:hover { transform: translateY(-10px); border-bottom-color: var(--primary); }
        .actor-icon {
            width: 80px; height: 80px; background: linear-gradient(135deg, var(--primary-light), var(--primary));
            border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 20px;
        }
        .actor-icon i { font-size: 40px; color: white; }
        .feature-card {
            background: white; padding: 25px; border-radius: 15px; text-align: center;
            transition: all 0.3s; height: 100%;
        }
        .feature-card:hover { transform: translateY(-5px); box-shadow: 0 10px 25px rgba(0,0,0,0.1); }
        .feature-icon { font-size: 40px; color: var(--primary); margin-bottom: 15px; }
        .footer { background: #000; color: white; padding: 50px 0 20px; }
        .footer h5 { color: var(--primary); margin-bottom: 20px; }
        .footer a { color: rgba(255,255,255,0.7); transition: all 0.3s; }
        .footer a:hover { color: var(--primary); }
        .social-links a {
            display: inline-block; width: 35px; height: 35px; background: rgba(255,255,255,0.1);
            border-radius: 50%; text-align: center; line-height: 35px; margin-right: 10px;
            color: white; transition: all 0.3s;
        }
        .social-links a:hover { background: var(--primary); transform: translateY(-3px); color: white; }
        @media (max-width: 768px) { .hero h1 { font-size: 32px; } .hero { text-align: center; } }
    </style>
</head>
<body>

    <nav class="navbar navbar-expand-lg fixed-top">
        <div class="container">
            <a class="navbar-brand" href="index.php">STOCK<span>FLOW</span></a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item"><a class="nav-link active" href="#">Accueil</a></li>
                    <li class="nav-item"><a class="nav-link" href="#acteurs">Acteurs</a></li>
                    <li class="nav-item"><a class="nav-link" href="#fonctionnalites">Fonctionnalités</a></li>
                </ul>
                <a href="source/vues/authentification/connexion.php" class="btn-login">
                    <i class="fas fa-sign-in-alt"></i> Connexion
                </a>
            </div>
        </div>
    </nav>

    <section class="hero">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-7">
                    <h1>Solution complète de <span>gestion de stock</span> pour supermarchés</h1>
                    <p>Optimisez la gestion de votre supermarché avec STOCKFLOW. Suivez vos stocks en temps réel, anticipez les ruptures et prenez des décisions éclairées.</p>
                    <a href="source/vues/authentification/connexion.php" class="btn-hero"><i class="fas fa-rocket"></i> Commencer</a>
                </div>
            </div>
        </div>
    </section>

    <section class="container py-5" id="acteurs">
        <div class="text-center mb-5">
            <h2 class="fw-bold">Les acteurs du système</h2>
            <div class="mx-auto" style="width:80px;height:4px;background:var(--primary);border-radius:2px;"></div>
        </div>
        <div class="row g-4">
            <div class="col-md-3"><div class="actor-card"><div class="actor-icon"><i class="fas fa-user-shield"></i></div><h3>Administrateur</h3><p class="text-muted">Supervision, utilisateurs</p></div></div>
            <div class="col-md-3"><div class="actor-card"><div class="actor-icon"><i class="fas fa-chart-line"></i></div><h3>Gérant</h3><p class="text-muted">Produits, commandes</p></div></div>
            <div class="col-md-3"><div class="actor-card"><div class="actor-icon"><i class="fas fa-warehouse"></i></div><h3>Magasinier</h3><p class="text-muted">Stocks, transferts</p></div></div>
            <div class="col-md-3"><div class="actor-card"><div class="actor-icon"><i class="fas fa-cash-register"></i></div><h3>Caissier</h3><p class="text-muted">Ventes</p></div></div>
        </div>
    </section>

    <section class="bg-light py-5" id="fonctionnalites">
        <div class="container">
            <div class="text-center mb-5"><h2 class="fw-bold">Fonctionnalités principales</h2></div>
            <div class="row g-4">
                <div class="col-md-3"><div class="feature-card"><i class="fas fa-boxes feature-icon"></i><h5>Produits</h5></div></div>
                <div class="col-md-3"><div class="feature-card"><i class="fas fa-exchange-alt feature-icon"></i><h5>Entrées/Sorties</h5></div></div>
                <div class="col-md-3"><div class="feature-card"><i class="fas fa-bell feature-icon"></i><h5>Alertes</h5></div></div>
                <div class="col-md-3"><div class="feature-card"><i class="fas fa-chart-line feature-icon"></i><h5>Rapports</h5></div></div>
            </div>
        </div>
    </section>

    <footer class="footer">
        <div class="container">
            <div class="row">
                <div class="col-md-4 mb-4"><h5>STOCKFLOW</h5><p>Gestion de stock supermarchés</p></div>
                <div class="col-md-4 mb-4"><h5>Liens</h5><a href="source/vues/authentification/connexion.php" class="text-white-50">Connexion</a></div>
                <div class="col-md-4 mb-4"><h5>Contact</h5><p class="text-white-50">Porto-Novo, Bénin</p></div>
            </div>
            <hr style="background:rgba(255,255,255,0.2);">
            <div class="text-center small text-white-50">&copy; 2025 STOCKFLOW</div>
        </div>
    </footer>

    <script src=""></script>
</body>
</html>