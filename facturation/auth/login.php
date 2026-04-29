<?php
// auth/login.php — Page de connexion

require_once __DIR__ . '/../auth/session.php';

// Si déjà connecté, rediriger
if (isset($_SESSION['identifiant'])) {
    header('Location: ../index.php');
    exit;
}

$erreur = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $identifiant  = trim($_POST['identifiant'] ?? '');
    $mot_de_passe = $_POST['mot_de_passe'] ?? '';

    if (empty($identifiant) || empty($mot_de_passe)) {
        $erreur = "Veuillez remplir tous les champs.";
    } elseif (!authentifier($identifiant, $mot_de_passe)) {
        $erreur = "Identifiant ou mot de passe incorrect.";
    } else {
        header('Location: ../index.php');
        exit;
    }
}

$titre = "Connexion";
require_once __DIR__ . '/../includes/header.php';
?>

<div class="auth-container">
  <div class="auth-card">
    <div class="auth-logo">
      <span class="logo-icon">🛒</span>
      <h1>SuperCaisse</h1>
      <p>Système de Facturation</p>
    </div>

    <?php if ($erreur): ?>
      <div class="alert alert-danger"><?= htmlspecialchars($erreur) ?></div>
    <?php endif; ?>

    <form method="POST" action="">
      <div class="form-group">
        <label for="identifiant">Identifiant</label>
        <input type="text" id="identifiant" name="identifiant"
               value="<?= htmlspecialchars($_POST['identifiant'] ?? '') ?>"
               placeholder="ex: dan.mbo" required autofocus>
      </div>
      <div class="form-group">
        <label for="mot_de_passe">Mot de passe</label>
        <input type="password" id="mot_de_passe" name="mot_de_passe"
               placeholder="••••••••" required>
      </div>
      <button type="submit" class="btn btn-primary btn-full">Se connecter</button>
    </form>

    <p class="auth-hint">Compte par défaut : <code>admin</code> / <code>password</code></p>
  </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
