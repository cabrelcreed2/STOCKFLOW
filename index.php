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
    
    <script>
        const savedTheme = localStorage.getItem('theme') || 'light';
        document.documentElement.setAttribute('data-theme', savedTheme);
    </script>
    
    <link href="bootstrap-5.3.8-dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="bootstrap-5.3.8-dist/fontawesome-free-6.7.2-web/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="public/css/responsive.css" rel="stylesheet">
    
    <style>
        /* ==========================================
           1. VARIABLES ET THÈMES
           ========================================== */
        :root {
            /* Couleurs principales (restent identiques dans les deux thèmes) */
            --primary: #f97316;
            --primary-dark: #ea580c;
            --primary-light: #fdba74;
            --primary-soft: #fff7ed;
            
            /* Intégration Bootstrap */
            --bs-primary: #f97316;
            --bs-primary-rgb: 249, 115, 22;

            /* Variables de Thème Clair (par défaut) */
            --bg-color: #f8fafc;
            --surface-color: #ffffff;
            --text-main: #0f172a;
            --text-muted: #64748b;
            --border-color: rgba(0,0,0,0.05);
            --navbar-bg: rgba(255, 255, 255, 0.85);
            --card-shadow: rgba(0, 0, 0, 0.03);
            --card-shadow-hover: rgba(249, 115, 22, 0.08);
            --step-arrow-color: #cbd5e1;
        }

        /* Variables de Thème Sombre */
        [data-theme="dark"] {
            --bg-color: #0f172a;        /* Fond de page sombre */
            --surface-color: #1e293b;   /* Fond des cartes et sections blanches */
            --text-main: #f8fafc;       /* Texte principal blanc cassé */
            --text-muted: #94a3b8;      /* Texte secondaire gris clair */
            --border-color: rgba(255,255,255,0.08);
            --navbar-bg: rgba(15, 23, 42, 0.90);
            --primary-soft: rgba(249, 115, 22, 0.15); /* Orange transparent pour le sombre */
            --card-shadow: rgba(0, 0, 0, 0.2);
            --card-shadow-hover: rgba(249, 115, 22, 0.15);
            --step-arrow-color: #475569;
        }

        html { scroll-behavior: smooth; }

        * {
            margin: 0; padding: 0;
            box-sizing: border-box;
            font-family: 'Poppins', sans-serif;
        }
        
        body {
            background-color: var(--bg-color);
            color: var(--text-main);
            overflow-x: hidden;
            transition: background-color 0.4s ease, color 0.4s ease;
        }

        /* Utilitaires dynamiques */
        .bg-surface {
            background-color: var(--surface-color);
            transition: background-color 0.4s ease;
        }
        .text-muted { color: var(--text-muted) !important; }

        /* Animations */
        @keyframes fadeInUp {
            from { opacity: 0; transform: translateY(30px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .animate-fade-up { animation: fadeInUp 0.8s cubic-bezier(0.16, 1, 0.3, 1) forwards; }
        .delay-1 { animation-delay: 0.1s; }
        .delay-2 { animation-delay: 0.2s; }
        .delay-3 { animation-delay: 0.3s; }

        /* Navbar */
        .navbar {
            background: var(--navbar-bg);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            padding: 15px 0;
            box-shadow: 0 4px 30px rgba(0, 0, 0, 0.05);
            transition: all 0.4s ease;
        }
        .navbar-brand { font-size: 28px; font-weight: 800; color: var(--primary) !important; letter-spacing: -1px; }
        .navbar-brand span { color: var(--text-main); transition: color 0.4s ease; }
        
        .nav-link {
            font-weight: 500; color: var(--text-muted) !important; margin: 0 10px;
            position: relative; transition: all 0.3s ease;
        }
        .nav-link::after {
            content: ''; position: absolute; width: 0; height: 2px;
            bottom: 0; left: 50%; background-color: var(--primary);
            transition: all 0.3s ease; transform: translateX(-50%);
        }
        .nav-link:hover::after, .nav-link.active::after { width: 100%; }
        .nav-link:hover, .nav-link.active { color: var(--primary) !important; }
        
        .btn-login {
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            color: white !important; padding: 10px 28px; border-radius: 50px;
            margin-left: 15px; font-weight: 600; transition: all 0.3s ease;
            text-decoration: none; box-shadow: 0 4px 15px rgba(249, 115, 22, 0.3);
        }
        .btn-login:hover { transform: translateY(-2px); box-shadow: 0 8px 25px rgba(249, 115, 22, 0.4); }
        
        /* Bouton Thème Navbar */
        .theme-btn-nav {
            background: transparent;
            border: 1px solid var(--border-color);
            color: var(--text-main);
            width: 40px; height: 40px;
            border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            transition: all 0.3s ease;
        }
        .theme-btn-nav:hover {
            background: var(--primary-soft);
            color: var(--primary);
            border-color: var(--primary);
        }

        /* Hero Section */
        .hero { position: relative; min-height: 100vh; display: flex; align-items: center; overflow: hidden; }
        .hero::before {
            content: ''; position: absolute; inset: 0;
            background-image: url('supermarché.jpg'); background-size: cover;
            background-position: center; background-repeat: no-repeat; background-attachment: fixed; z-index: 0;
        }
        .hero::after {
            content: ''; position: absolute; inset: 0;
            background: linear-gradient(90deg, rgba(0,0,0,0.85) 0%, rgba(0,0,0,0.4) 100%); z-index: 0;
        }
        .hero .container { position: relative; z-index: 1; padding-top: 80px; }
        .hero h1 {
            font-size: 3.5rem; font-weight: 800; color: white; margin-bottom: 24px; line-height: 1.2;
            text-shadow: 0 2px 10px rgba(0,0,0,0.3);
        }
        .hero h1 span { color: var(--primary); display: inline-block; }
        .hero p { font-size: 1.1rem; color: rgba(255, 255, 255, 0.9); margin-bottom: 40px; line-height: 1.8; max-width: 600px; }
        
        .btn-hero {
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            color: white; padding: 14px 38px; border-radius: 50px; font-weight: 600; text-decoration: none;
            display: inline-flex; align-items: center; gap: 10px; margin-right: 15px;
            transition: all 0.3s ease; box-shadow: 0 4px 15px rgba(249, 115, 22, 0.3);
        }
        .btn-hero:hover { transform: translateY(-3px); box-shadow: 0 10px 25px rgba(249, 115, 22, 0.5); color: white; }
        .btn-outline-hero {
            background: rgba(255, 255, 255, 0.1); backdrop-filter: blur(5px); color: white;
            padding: 14px 38px; border-radius: 50px; font-weight: 600; text-decoration: none;
            display: inline-flex; align-items: center; gap: 10px; border: 1px solid rgba(255, 255, 255, 0.3);
            transition: all 0.3s ease;
        }
        .btn-outline-hero:hover { background: white; color: #0f172a; transform: translateY(-3px); }

        /* Social Proof (Partenaires) */
        .partners-section {
            background: var(--surface-color); padding: 30px 0;
            border-bottom: 1px solid var(--border-color); transition: background-color 0.4s ease;
        }
        .partner-logo { font-size: 1.2rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; letter-spacing: 2px; display: flex; align-items: center; justify-content: center; gap: 10px; transition: all 0.3s ease; filter: grayscale(100%); }
        .partner-logo:hover { color: var(--text-main); filter: grayscale(0%); }

        /* Section Titles */
        .section-title { text-align: center; margin-bottom: 4rem; }
        .section-title h2 { font-weight: 800; color: var(--text-main); font-size: 2.5rem; transition: color 0.4s ease; }
        .section-title .divider { width: 80px; height: 5px; background: var(--primary); border-radius: 50px; margin: 15px auto; }
        
        /* Pourquoi choisir STOCKFLOW */
        .benefit-card {
            background: var(--surface-color); padding: 45px 35px; border-radius: 24px;
            border: 1px solid var(--border-color); box-shadow: 0 12px 35px var(--card-shadow);
            transition: all 0.4s; height: 100%;
        }
        .benefit-card:hover { transform: translateY(-12px); box-shadow: 0 25px 50px var(--card-shadow-hover); border-color: var(--primary-light); }
        .stat-number { font-size: 3.2rem; font-weight: 800; background: linear-gradient(135deg, var(--primary), var(--primary-dark)); -webkit-background-clip: text; -webkit-text-fill-color: transparent; line-height: 1; margin-bottom: 15px; }
        .benefit-card h4 { font-size: 19px; font-weight: 700; color: var(--text-main); margin-bottom: 12px; }
        .benefit-card p { font-size: 14px; color: var(--text-muted); line-height: 1.6; margin: 0; }
        
        /* Comment ça marche */
        .step-card { text-align: center; position: relative; padding: 20px; }
        .step-number { width: 70px; height: 70px; border-radius: 50%; background: var(--primary-soft); color: var(--primary); display: flex; align-items: center; justify-content: center; font-size: 28px; font-weight: 800; margin: 0 auto 25px; box-shadow: 0 10px 20px rgba(249,115,22,0.15); border: 2px solid var(--primary-light); transition: all 0.3s; }
        .step-card:hover .step-number { background: var(--primary); color: white; transform: scale(1.1); }
        .step-card h4 { font-weight: 700; color: var(--text-main); margin-bottom: 15px; }
        .step-card p { color: var(--text-muted); font-size: 0.95rem; }
        .step-arrow { position: absolute; top: 55px; right: -20px; font-size: 24px; color: var(--step-arrow-color); transition: color 0.4s ease; }
        @media (max-width: 991px) { .step-arrow { display: none; } }

        /* Fonctionnalités */
        .feature-card {
            background: var(--surface-color); padding: 35px 25px; border-radius: 20px;
            transition: all 0.3s ease; height: 100%; border: 1px solid var(--border-color);
            display: flex; align-items: flex-start; gap: 20px;
        }
        .feature-card:hover { transform: translateY(-5px); box-shadow: 0 15px 30px var(--card-shadow); border-color: var(--primary-light); }
        .feature-icon-wrapper { flex-shrink: 0; width: 55px; height: 55px; border-radius: 15px; background: var(--primary-soft); display: flex; align-items: center; justify-content: center; }
        .feature-icon-wrapper i { font-size: 24px; color: var(--primary); }
        .feature-content h5 { font-weight: 700; font-size: 18px; margin-bottom: 8px; color: var(--text-main); }
        .feature-content p { font-size: 14px; margin: 0; color: var(--text-muted); line-height: 1.6; }

        /* FAQ Accordion */
        .accordion-item {
            border: 1px solid var(--border-color); background: var(--surface-color);
            border-radius: 15px !important; margin-bottom: 15px;
            box-shadow: 0 5px 15px var(--card-shadow); overflow: hidden; transition: background-color 0.4s ease;
        }
        .accordion-button { font-weight: 600; color: var(--text-main); padding: 20px 25px; background: var(--surface-color); box-shadow: none !important; transition: background-color 0.4s ease, color 0.4s ease; }
        .accordion-button:not(.collapsed) { background-color: var(--primary-soft); color: var(--primary); }
        .accordion-button::after { filter: var(--accordion-icon-filter, none); }
        [data-theme="dark"] .accordion-button::after { filter: invert(1); }
        .accordion-body { color: var(--text-muted); line-height: 1.7; padding: 0 25px 25px; background-color: var(--surface-color); }

        /* Final CTA */
        .cta-section { background: linear-gradient(135deg, #0f172a, #1e293b); border-radius: 30px; padding: 70px 40px; text-align: center; color: white; position: relative; overflow: hidden; box-shadow: 0 20px 40px rgba(0,0,0,0.1); }
        .cta-section::before { content: "\f07a"; font-family: "Font Awesome 6 Free"; font-weight: 900; position: absolute; top: -20px; right: -20px; font-size: 250px; color: rgba(255,255,255,0.03); transform: rotate(-15deg); }
        .cta-section h2 { font-weight: 800; font-size: 2.5rem; margin-bottom: 20px; position: relative; z-index: 1; }
        .cta-section p { font-size: 1.1rem; color: rgba(255,255,255,0.8); margin-bottom: 35px; position: relative; z-index: 1; }
        
        /* Footer */
        .footer { background: #000000; color: white; padding: 70px 0 20px; }
        .footer h5 { color: white; font-weight: 700; margin-bottom: 25px; font-size: 1.2rem; }
        .footer p, .footer li { color: rgba(255, 255, 255, 0.6); font-size: 14px; line-height: 1.8; }
        .footer a { color: rgba(255, 255, 255, 0.6); text-decoration: none; transition: all 0.3s ease; display: inline-block; }
        .footer a:hover { color: var(--primary); transform: translateX(5px); }
        .social-links a { display: inline-flex; align-items: center; justify-content: center; width: 40px; height: 40px; background: rgba(255, 255, 255, 0.05); border-radius: 50%; margin-right: 12px; color: white; transition: all 0.3s; }
        .social-links a:hover { background: var(--primary); transform: translateY(-3px); color: white; }
        hr.bg-white-50 { background-color: rgba(255, 255, 255, 0.1); margin: 40px 0 20px; }
        
        @media (max-width: 991px) {
            .navbar { background: var(--surface-color); }
            .hero h1 { font-size: 2.5rem; }
            .btn-hero, .btn-outline-hero { width: 100%; margin: 10px 0; justify-content: center; }
            .hero { text-align: center; }
            .hero p { margin-left: auto; margin-right: auto; }
            .navbar-toggler i { color: var(--text-main) !important; }
            .theme-btn-nav { margin: 15px auto; }
        }
    </style>
</head>
<body>

    <nav class="navbar navbar-expand-lg fixed-top">
        <div class="container">
            <a class="navbar-brand" href="index.html">
                STOCK<span>FLOW</span>
            </a>
            
            <div class="d-flex align-items-center gap-2 d-lg-none">
                <button class="navbar-toggler border-0 shadow-none" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                    <i class="fas fa-bars fs-3"></i>
                </button>
            </div>

            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto align-items-center">
                    <li class="nav-item"><a class="nav-link active" href="#">Accueil</a></li>
                    <li class="nav-item"><a class="nav-link" href="#avantages">Avantages</a></li>
                    <li class="nav-item"><a class="nav-link" href="#comment-ca-marche">Comment ça marche</a></li>
                    <li class="nav-item"><a class="nav-link" href="#faq">FAQ</a></li>
                    
                    <li class="nav-item mt-3 mt-lg-0 ms-lg-3">
                        <button id="theme-toggle" class="theme-btn-nav" title="Changer le thème">
                            <i class="fas fa-moon"></i>
                        </button>
                    </li>

                    <li class="nav-item mt-3 mt-lg-0">
                        <a href="source/vues/authentification/connexion.php" class="btn-login">
                            <i class="fas fa-sign-in-alt me-2"></i>Mon Espace
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <section class="hero">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-7 animate-fade-up">
                    <h1>Pilotez votre <span>supermarché</span> avec précision</h1>
                    <p>La solution tout-en-un pour optimiser vos rayons, suivre vos inventaires en temps réel et booster votre rentabilité sans effort.</p>
                    <div>
                        <a href="source/vues/authentification/connexion.php" class="btn-hero">
                            Démarrer maintenant <i class="fas fa-arrow-right"></i>
                        </a>
                        <a href="#comment-ca-marche" class="btn-outline-hero">
                            <i class="fas fa-play-circle"></i> Comment ça marche ?
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <div class="partners-section d-none d-md-block">
        <div class="container">
            <div class="row text-center align-items-center">
                <div class="col-md-2 offset-md-1"><div class="partner-logo"><i class="fas fa-shopping-basket fs-3"></i> Super-U</div></div>
                <div class="col-md-2"><div class="partner-logo"><i class="fas fa-store-alt fs-3"></i> Market+</div></div>
                <div class="col-md-2"><div class="partner-logo"><i class="fas fa-leaf fs-3"></i> BioFresh</div></div>
                <div class="col-md-2"><div class="partner-logo"><i class="fas fa-shopping-cart fs-3"></i> EcoMart</div></div>
                <div class="col-md-2"><div class="partner-logo"><i class="fas fa-apple-alt fs-3"></i> DailyGros</div></div>
            </div>
        </div>
    </div>

    <section class="py-5 my-5" id="avantages">
        <div class="container">
            <div class="section-title animate-fade-up">
                <h2>Pourquoi choisir STOCKFLOW ?</h2>
                <div class="divider"></div>
                <p class="text-muted mt-3">Des résultats mesurables et immédiats pour la gestion de votre point de vente.</p>
            </div>
            
            <div class="row g-4">
                <div class="col-md-4 animate-fade-up">
                    <div class="benefit-card">
                        <div class="stat-number">-30%</div>
                        <h4>De pertes et démarques</h4>
                        <p>Anticipez les dates de péremption et contrôlez le surstockage pour réduire drastiquement le gaspillage dans vos rayons frais et secs.</p>
                    </div>
                </div>
                <div class="col-md-4 animate-fade-up delay-1">
                    <div class="benefit-card">
                        <div class="stat-number">+15h</div>
                        <h4>Gagnées par semaine</h4>
                        <p>Automatisez la génération de vos commandes fournisseurs et facilitez les inventaires tournants grâce à un système intuitif et rapide.</p>
                    </div>
                </div>
                <div class="col-md-4 animate-fade-up delay-2">
                    <div class="benefit-card">
                        <div class="stat-number">99.8%</div>
                        <h4>De précision sur les stocks</h4>
                        <p>Évitez définitivement les ruptures en rayon. Nos seuils d'alerte automatiques vous préviennent dès qu'une référence nécessite un réapprovisionnement.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="bg-surface py-5" id="comment-ca-marche">
        <div class="container py-5">
            <div class="section-title animate-fade-up">
                <h2>Un déploiement en 3 étapes simples</h2>
                <div class="divider"></div>
                <p class="text-muted mt-3">Pas besoin d'être un expert en informatique. Démarrez en quelques minutes.</p>
            </div>
            
            <div class="row mt-5">
                <div class="col-md-4 animate-fade-up">
                    <div class="step-card">
                        <div class="step-number">1</div>
                        <i class="fas fa-arrow-right step-arrow"></i>
                        <h4>Importez votre liste de produits</h4>
                        <p>Importez votre liste de produits ou scannez simplement les codes-barres pour ajouter de nouveaux articles à votre base de données.</p>
                    </div>
                </div>
                <div class="col-md-4 animate-fade-up delay-1">
                    <div class="step-card">
                        <div class="step-number">2</div>
                        <i class="fas fa-arrow-right step-arrow"></i>
                        <h4>Connectez vos caisses</h4>
                        <p>Créez les accès pour vos caissières. Chaque vente enregistrée déduit automatiquement l'article du stock global.</p>
                    </div>
                </div>
                <div class="col-md-4 animate-fade-up delay-2">
                    <div class="step-card">
                        <div class="step-number">3</div>
                        <h4>Pilotez en temps réel</h4>
                        <p>Gérez vos réapprovisionnements depuis votre tableau de bord, suivez vos marges et recevez des alertes avant les ruptures.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="py-5" id="fonctionnalites">
        <div class="container py-4">
            <div class="section-title animate-fade-up">
                <h2>Fonctionnalités clés</h2>
                <div class="divider"></div>
            </div>
            
            <div class="row g-4">
                <div class="col-md-6 col-lg-4 animate-fade-up">
                    <div class="feature-card">
                        <div class="feature-icon-wrapper"><i class="fas fa-boxes"></i></div>
                        <div class="feature-content">
                            <h5>Catalogue Produits</h5>
                            <p>Ajoutez, modifiez et classez vos articles facilement grâce au code-barres.</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-6 col-lg-4 animate-fade-up delay-1">
                    <div class="feature-card">
                        <div class="feature-icon-wrapper"><i class="fas fa-sync-alt"></i></div>
                        <div class="feature-content">
                            <h5>Mouvement de stock</h5>
                            <p>Tracez chaque entrée et sortie pour un inventaire toujours juste.</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-6 col-lg-4 animate-fade-up delay-2">
                    <div class="feature-card">
                        <div class="feature-icon-wrapper"><i class="fas fa-bell"></i></div>
                        <div class="feature-content">
                            <h5>Alertes intelligentes</h5>
                            <p>Soyez notifié instantanément en cas de stock bas ou de péremption proche.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="bg-surface py-5" id="faq">
        <div class="container py-5">
            <div class="section-title animate-fade-up">
                <h2>Foire Aux Questions</h2>
                <div class="divider"></div>
                <p class="text-muted mt-3">Nous répondons aux questions les plus fréquentes.</p>
            </div>

            <div class="row justify-content-center">
                <div class="col-lg-8">
                    <div class="accordion" id="faqAccordion">
                        <div class="accordion-item animate-fade-up">
                            <h2 class="accordion-header" id="headingOne">
                                <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#collapseOne" aria-expanded="true" aria-controls="collapseOne">
                                    <i class="fas fa-lock text-primary me-3"></i> Les données de mon supermarché sont-elles sécurisées ?
                                </button>
                            </h2>
                            <div id="collapseOne" class="accordion-collapse collapse show" aria-labelledby="headingOne" data-bs-parent="#faqAccordion">
                                <div class="accordion-body">
                                    Absolument. Toutes vos données (catalogues, ventes, marges) sont cryptées et sauvegardées quotidiennement sur des serveurs sécurisés. Vous seul, en tant qu'administrateur, définissez qui a accès à quelles informations grâce à notre système de rôles (Gérant, Caissière, Magasinier).
                                </div>
                            </div>
                        </div>

                        <div class="accordion-item animate-fade-up delay-1">
                            <h2 class="accordion-header" id="headingTwo">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseTwo" aria-expanded="false" aria-controls="collapseTwo">
                                    <i class="fas fa-graduation-cap text-primary me-3"></i> Faut-il une formation longue pour les caissières ?
                                </button>
                            </h2>
                            <div id="collapseTwo" class="accordion-collapse collapse" aria-labelledby="headingTwo" data-bs-parent="#faqAccordion">
                                <div class="accordion-body">
                                    Non, pas du tout. L'interface dédiée aux caissières a été conçue pour être extrêmement intuitive. En moins de 15 minutes, votre personnel sera capable de scanner des articles, gérer des paniers et valider des encaissements sans difficulté.
                                </div>
                            </div>
                        </div>

                        <div class="accordion-item animate-fade-up delay-2">
                            <h2 class="accordion-header" id="headingThree">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseThree" aria-expanded="false" aria-controls="collapseThree">
                                    <i class="fas fa-wifi text-primary me-3"></i> Le logiciel fonctionne-t-il en cas de coupure internet ?
                                </button>
                            </h2>
                            <div id="collapseThree" class="accordion-collapse collapse" aria-labelledby="headingThree" data-bs-parent="#faqAccordion">
                                <div class="accordion-body">
                                    Oui. STOCKFLOW intègre un mode hors-ligne pour la caisse. Vos caissières peuvent continuer à encaisser les clients normalement. Dès que la connexion internet est rétablie, les ventes se synchronisent automatiquement avec le serveur central pour mettre à jour les stocks.
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="container py-5 animate-fade-up">
        <div class="cta-section">
            <h2>Prêt à révolutionner votre gestion ?</h2>
            <p>Rejoignez les dizaines de supermarchés qui ont déjà fait confiance à STOCKFLOW pour optimiser leur rentabilité.</p>
            <a href="source/vues/authentification/connexion.php" class="btn-hero" style="margin:0;">
                Rejoindre mon espace  <i class="fas fa-arrow-right"></i>
            </a>
        </div>
    </section>

    <footer class="footer" id="contact">
        <div class="container">
            <div class="row">
                <div class="col-lg-4 mb-5 mb-lg-0">
                    <h5 style="color: var(--primary); font-size: 1.5rem; font-weight: 800;">STOCKFLOW</h5>
                    <p class="pe-lg-4">Votre partenaire technologique pour une gestion de supermarché fluide, moderne et sans erreur. Gérer vos stock en toute sérénité.</p>
                    <div class="social-links mt-4">
                        <a href="#"><i class="fab fa-linkedin-in"></i></a>
                        <a href="#"><i class="fab fa-facebook-f"></i></a>
                        <a href="#"><i class="fab fa-twitter"></i></a>
                        <a href="#"><i class="fab fa-instagram"></i></a>
                    </div>
                </div>
                <div class="col-md-6 col-lg-4 mb-5 mb-md-0">
                    <h5>Navigation Rapide</h5>
                    <ul class="list-unstyled">
                        <li class="mb-2"><a href="#"><i class="fas fa-chevron-right ms-1 me-2 text-primary" style="font-size:10px;"></i> Accueil</a></li>
                        <li class="mb-2"><a href="#comment-ca-marche"><i class="fas fa-chevron-right ms-1 me-2 text-primary" style="font-size:10px;"></i> Comment ça marche</a></li>
                        <li class="mb-2"><a href="#faq"><i class="fas fa-chevron-right ms-1 me-2 text-primary" style="font-size:10px;"></i> Questions fréquentes</a></li>
                        <li class="mb-2"><a href="source/vues/authentification/connexion.php"><i class="fas fa-chevron-right ms-1 me-2 text-primary" style="font-size:10px;"></i> Se Connecter</a></li>
                    </ul>
                </div>
                <div class="col-md-6 col-lg-4">
                    <h5>Contactez-nous</h5>
                    <ul class="list-unstyled">
                        <li class="mb-3 d-flex align-items-start"><i class="fas fa-map-marker-alt text-primary mt-1 me-3"></i> <span>ESCAE BENIN , Porto-Novo<br>Bénin</span></li>
                        <li class="mb-3 d-flex align-items-center"><i class="fas fa-phone-alt text-primary me-3"></i> <span>+229 97 12 34 56</span></li>
                        <li class="mb-3 d-flex align-items-center"><i class="fas fa-envelope text-primary me-3"></i> <span>contact@stockflow.com</span></li>
                    </ul>
                </div>
            </div>
            <hr class="bg-white-50">
            <div class="row align-items-center">
                <div class="col-md-6 text-center text-md-start small">&copy; 2026 STOCKFLOW. Tous droits réservés.</div>
                <div class="col-md-6 text-center text-md-end small mt-3 mt-md-0">
                    <a href="#" class="me-3">Politique de confidentialité</a>
                    <a href="#">Mentions légales</a>
                </div>
            </div>
        </div>
    </footer>

    <script src="bootstrap-5.3.8-dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const themeToggle = document.getElementById('theme-toggle');
            const htmlEl = document.documentElement;
            const icon = themeToggle.querySelector('i');

            // État initial de l'icône
            if (htmlEl.getAttribute('data-theme') === 'dark') {
                icon.classList.remove('fa-moon');
                icon.classList.add('fa-sun');
            }

            // Événement de clic
            themeToggle.addEventListener('click', () => {
                const currentTheme = htmlEl.getAttribute('data-theme');
                const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
                
                htmlEl.setAttribute('data-theme', newTheme);
                localStorage.setItem('theme', newTheme);
                
                if (newTheme === 'dark') {
                    icon.classList.remove('fa-moon');
                    icon.classList.add('fa-sun');
                } else {
                    icon.classList.remove('fa-sun');
                    icon.classList.add('fa-moon');
                }
            });
        });
    </script>
</body>
</html>