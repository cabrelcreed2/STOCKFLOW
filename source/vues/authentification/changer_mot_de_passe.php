<?php
session_start();

// Vérifier que l'utilisateur est connecté
if (!isset($_SESSION['utilisateur'])) {
    header('Location: connexion.php');
    exit;
}

require_once __DIR__ . '/../../../source/modeles/BaseDeDonnees.php';
require_once __DIR__ . '/../../../source/modeles/Utilisateur.php';

$user = $_SESSION['utilisateur'];
$message = '';
$erreur = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nouveau = $_POST['nouveau_mdp'] ?? '';
    $confirmer = $_POST['confirmer_mdp'] ?? '';
    
    // Validations
    if (empty($nouveau) || empty($confirmer)) {
        $erreur = 'Veuillez remplir tous les champs.';
    } elseif (strlen($nouveau) < 8) {
        $erreur = 'Le mot de passe doit contenir au moins 8 caractères.';
    } elseif (!preg_match('/[A-Z]/', $nouveau)) {
        $erreur = 'Le mot de passe doit contenir au moins une majuscule.';
    } elseif (!preg_match('/[0-9]/', $nouveau)) {
        $erreur = 'Le mot de passe doit contenir au moins un chiffre.';
    } elseif ($nouveau !== $confirmer) {
        $erreur = 'Les mots de passe ne correspondent pas.';
    } else {
        // Mettre à jour le mot de passe
        $db = new Database();
        $conn = $db->connect();
        $utilisateurModel = new Utilisateur($conn);
        
        $resultat = $utilisateurModel->modifierMotDePasse($user['id_utilisateur'], $nouveau);
        
        if ($resultat) {
            // Marquer comme "plus première connexion"
            $sql = "UPDATE utilisateur SET premier_connexion = 0 WHERE id_utilisateur = :id";
            $stmt = $conn->prepare($sql);
            $stmt->execute(['id' => $user['id_utilisateur']]);
            
            // Mettre à jour la session
            $user['premier_connexion'] = 0;
            $_SESSION['utilisateur'] = $user;
            
            $message = 'Mot de passe changé avec succès ! Redirection en cours...';
            
            // Rediriger après 2 secondes
            switch ($user['role']) {
                case 'ADMIN':
                    $url = '../administrateur/tableau_de_bord_admin.php';
                    break;
                case 'GESTIONNAIRE':
                    $url = '../gerant/tableau_de_bord_gerant.php';
                    break;
                case 'MAGASINIER':
                    $url = '../magasinier/tableau_de_bord_magasinier.php';
                    break;
                case 'CAISSIERE':
                    $url = '../caissier/tableau_de_bord_caissier.php';
                    break;
                default:
                    $url = 'connexion.php';
                }
header('Refresh: 2; URL=' . $url);
        } else {
            $erreur = 'Erreur lors du changement de mot de passe.';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>STOCKFLOW - Changer mot de passe</title>
    <link href="../bootstrap-5.3.8-dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../bootstrap-5.3.8-dist/fontawesome-free-6.7.2-web/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="../../public/css/responsive.css" rel="stylesheet">
    <style>
        * { font-family: 'Poppins', sans-serif; }
        body { background-color: #f8fafc; height: 100vh; display: flex; align-items: center; }
        .card { max-width: 500px; margin: 0 auto; border-radius: 20px; border: none; }
        .btn-primary { background: linear-gradient(135deg, #f97316, #ea580c); border: none; padding: 12px; font-weight: 600; }
        .btn-primary:hover { background: linear-gradient(135deg, #ea580c, #c2410c); }
        .form-control:focus { border-color: #f97316; box-shadow: 0 0 0 3px rgba(249,115,22,0.1); }
        .requirement { font-size: 13px; margin-left: 10px; }
        .requirement.valid { color: #22c55e; }
        .requirement.invalid { color: #ef4444; }
    </style>
</head>
<body>
    <div class="container">
        <div class="card shadow-lg p-4">
            <div class="text-center mb-4">
                <i class="fas fa-lock fa-3x" style="color: #f97316;"></i>
                <h3 class="mt-3 fw-bold">Changer votre mot de passe</h3>
                <p class="text-muted">
                    Première connexion détectée. Pour des raisons de sécurité, vous devez changer votre mot de passe temporaire.
                </p>
                <p class="small text-muted">
                    Connecté en tant que : <strong><?php echo htmlspecialchars($user['prenom'] . ' ' . $user['nom']); ?></strong>
                </p>
            </div>
            
            <?php if (!empty($message)): ?>
                <div class="alert alert-success text-center">
                    <i class="fas fa-check-circle me-2"></i><?php echo $message; ?>
                </div>
            <?php endif; ?>
            
            <?php if (!empty($erreur)): ?>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-triangle me-2"></i><?php echo $erreur; ?>
                </div>
            <?php endif; ?>
            
            <form method="POST">
                <div class="mb-3">
                    <label class="form-label fw-semibold">Nouveau mot de passe</label>
                    <input type="password" name="nouveau_mdp" id="nouveau_mdp" class="form-control" 
                           placeholder="Min. 8 caractères, 1 majuscule, 1 chiffre" required minlength="8">
                    <div class="mt-2">
                        <div class="requirement" id="req-length">🔴 Au moins 8 caractères</div>
                        <div class="requirement" id="req-uppercase">🔴 Au moins une majuscule</div>
                        <div class="requirement" id="req-number">🔴 Au moins un chiffre</div>
                    </div>
                </div>
                
                <div class="mb-4">
                    <label class="form-label fw-semibold">Confirmer le mot de passe</label>
                    <input type="password" name="confirmer_mdp" id="confirmer_mdp" class="form-control" 
                           placeholder="Répétez le mot de passe" required>
                    <div class="requirement mt-2" id="req-match" style="display:none;">🔴 Les mots de passe ne correspondent pas</div>
                </div>
                
                <button type="submit" class="btn btn-primary w-100">
                    <i class="fas fa-save me-2"></i>Enregistrer le mot de passe
                </button>
            </form>
        </div>
    </div>
    
    <script src="../bootstrap-5.3.8-dist/js/bootstrap.bundle.min.js"></script>
    <script src="../../public/js/responsive.js"></script>
    <script>
        const mdpInput = document.getElementById('nouveau_mdp');
        const confirmInput = document.getElementById('confirmer_mdp');
        
        mdpInput.addEventListener('input', function() {
            const val = this.value;
            document.getElementById('req-length').className = val.length >= 8 ? 'requirement valid' : 'requirement invalid';
            document.getElementById('req-length').innerHTML = val.length >= 8 ? '🟢 Au moins 8 caractères' : '🔴 Au moins 8 caractères';
            
            document.getElementById('req-uppercase').className = /[A-Z]/.test(val) ? 'requirement valid' : 'requirement invalid';
            document.getElementById('req-uppercase').innerHTML = /[A-Z]/.test(val) ? '🟢 Au moins une majuscule' : '🔴 Au moins une majuscule';
            
            document.getElementById('req-number').className = /[0-9]/.test(val) ? 'requirement valid' : 'requirement invalid';
            document.getElementById('req-number').innerHTML = /[0-9]/.test(val) ? '🟢 Au moins un chiffre' : '🔴 Au moins un chiffre';
        });
        
        confirmInput.addEventListener('input', function() {
            const match = this.value === mdpInput.value;
            const reqMatch = document.getElementById('req-match');
            reqMatch.style.display = this.value.length > 0 ? 'block' : 'none';
            reqMatch.className = match ? 'requirement valid' : 'requirement invalid';
            reqMatch.innerHTML = match ? '🟢 Les mots de passe correspondent' : '🔴 Les mots de passe ne correspondent pas';
        });
    </script>
</body>
</html>