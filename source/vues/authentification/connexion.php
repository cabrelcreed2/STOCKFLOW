<?php
session_start();
require_once __DIR__ . '/../../../source/controleurs/AuthentificationControleur.php';

$erreur = '';
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'];
    $mot_de_passe = $_POST['mot_de_passe'];
    
    $auth = new AuthentificationControleur();
    $resultat = $auth->connexion($email, $mot_de_passe);
    
    if ($resultat['succes']) {
    $utilisateur = $resultat['utilisateur'];
    
    // Vérifier si première connexion
    if ($utilisateur['premier_connexion'] == 1) {
        header('Location: changer_mot_de_passe.php');
        exit;
    }
    
    // Sinon, rediriger selon le rôle
    switch ($utilisateur['role']) {
        case 'ADMIN':
            header('Location: ../administrateur/tableau_de_bord_admin.php');
            break;
        case 'GESTIONNAIRE':
            header('Location: ../gerant/tableau_de_bord_gerant.php');
            break;
        case 'MAGASINIER':
            header('Location: ../magasinier/tableau_de_bord_magasinier.php');
            break;
        case 'CAISSIERE':
            header('Location: ../caissier/tableau_de_bord_caissier.php');
            break;
    }
        exit;
        }
    }
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>STOCKFLOW - Connexion</title>
    <link href="../bootstrap-5.3.8-dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../bootstrap-5.3.8-dist/fontawesome-free-6.7.2-web/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="../../public/css/responsive.css" rel="stylesheet">
    <style>
        * { font-family: 'Poppins', sans-serif; }
        body { background-color: #f5f5f5; height: 100vh; display: flex; align-items: center; }
        .login-card { max-width: 450px; margin: 0 auto; }
        .login-header { text-align: center; margin-bottom: 2rem; }
        .login-header i { font-size: 3rem; color: #f97316; }
        .login-header h2 { color: #f97316; font-weight: 800; }
        .login-header h2 span { color: #ea580c; }
        .card { border-radius: 20px; border: none; }
        .btn-primary {
            background: linear-gradient(135deg, #f97316 0%, #ea580c 100%);
            border: none; padding: 12px; font-weight: 600; transition: all 0.3s;
        }
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(249,115,22,0.4);
            background: linear-gradient(135deg, #ea580c 0%, #c2410c 100%);
        }
        .form-control:focus { border-color: #f97316; box-shadow: 0 0 0 3px rgba(249,115,22,0.1); }
        .input-group-text { background: white; border-right: none; color: #f97316; }
        .form-control { border-left: none; }
        a { color: #f97316; transition: all 0.3s; }
        a:hover { color: #ea580c; }
        .form-check-input:checked { background-color: #f97316; border-color: #f97316; }
    </style>
</head>
<body>
    <div class="container">
        <div class="login-card">
            <div class="card shadow-lg border-0 rounded-4">
                <div class="card-body p-5">
                    
                    <div class="login-header">
                        <i class="fas fa-chart-line"></i>
                        <h2 class="mt-3 fw-bold">STOCK<span>FLOW</span></h2>
                        <p class="text-muted">Gestion de Stock</p>
                    </div>
                    
                    <?php if (!empty($erreur)): ?>
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            <?php echo $erreur; ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>
                    
                    <form method="POST" action="">
                        <div class="mb-4">
                            <label for="email" class="form-label fw-semibold">
                                <i class="fas fa-envelope"></i> Adresse Email
                            </label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-user"></i></span>
                                <input type="email" class="form-control" id="email" name="email"
                                    placeholder="Entrez votre adresse email" value="<?php echo htmlspecialchars($email); ?>" required>
                            </div>
                        </div>
                        
                        <div class="mb-4">
                            <label for="mot_de_passe" class="form-label fw-semibold">
                                <i class="fas fa-lock"></i> Mot de passe
                            </label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-shield-alt"></i></span>
                                <input type="password" class="form-control" id="mot_de_passe" name="mot_de_passe"
                                    placeholder="Entrez votre mot de passe" required>
                            </div>
                        </div>
                        
                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary btn-lg">
                                <i class="fas fa-sign-in-alt me-2"></i>Se connecter
                            </button>
                        </div>
                    </form>
                    
                </div>
            </div>
            
            <p class="text-center text-muted mt-4">
                <small>© 2025 STOCKFLOW. Tous droits réservés.</small>
            </p>
        </div>
    </div>

    <script src="../bootstrap-5.3.8-dist/js/bootstrap.bundle.min.js"></script>
    <script src="../../public/js/responsive.js"></script>
</body>
</html>