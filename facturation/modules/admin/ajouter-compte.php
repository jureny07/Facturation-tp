<?php
// modules/admin/ajouter-compte.php — Création d'un nouveau compte utilisateur

require_once __DIR__ . '/../../auth/session.php';
exiger_role(ROLE_SUPERADMIN);
require_once __DIR__ . '/../../includes/fonctions-auth.php';

$erreur = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $identifiant  = trim($_POST['identifiant']  ?? '');
    $mot_de_passe = $_POST['mot_de_passe']       ?? '';
    $confirmation = $_POST['confirmation']        ?? '';
    $role         = $_POST['role']                ?? '';
    $nom_complet  = trim($_POST['nom_complet']   ?? '');

    // Validations
    if (empty($identifiant) || empty($mot_de_passe) || empty($role) || empty($nom_complet)) {
        $erreur = "Tous les champs sont obligatoires.";
    } elseif (!preg_match('/^[a-z0-9._-]{3,30}$/', $identifiant)) {
        $erreur = "Identifiant invalide (3-30 caractères, lettres minuscules, chiffres, point, trait)";
    } elseif ($mot_de_passe !== $confirmation) {
        $erreur = "Les mots de passe ne correspondent pas.";
    } elseif (strlen($mot_de_passe) < 6) {
        $erreur = "Le mot de passe doit contenir au moins 6 caractères.";
    } elseif (!in_array($role, [ROLE_CAISSIER, ROLE_MANAGER, ROLE_SUPERADMIN])) {
        $erreur = "Rôle invalide.";
    } else {
        $resultat = ajouter_utilisateur($identifiant, $mot_de_passe, $role, $nom_complet);
        if ($resultat === true) {
            $_SESSION['succes'] = "Compte « {$identifiant} » créé avec succès.";
            header('Location: /facturation/modules/admin/gestion-comptes.php');
            exit;
        } else {
            $erreur = $resultat;
        }
    }
}

$titre = "Ajouter un Compte";
require_once __DIR__ . '/../../includes/header.php';
?>

<div class="page-header">
  <h2>⊕ Ajouter un Compte Utilisateur</h2>
</div>

<div style="max-width:520px;">
<div class="card">
  <?php if ($erreur): ?>
    <div class="alert alert-erreur"><?= htmlspecialchars($erreur) ?></div>
  <?php endif; ?>

  <form method="POST" action="">
    <div class="form-group">
      <label for="nom_complet">Nom complet</label>
      <input type="text" id="nom_complet" name="nom_complet"
             value="<?= htmlspecialchars($_POST['nom_complet'] ?? '') ?>"
             placeholder="Prénom Nom" required>
    </div>
    <div class="form-group">
      <label for="identifiant">Identifiant</label>
      <input type="text" id="identifiant" name="identifiant"
             value="<?= htmlspecialchars($_POST['identifiant'] ?? '') ?>"
             placeholder="prenom.nom" pattern="[a-z0-9._-]{3,30}" required>
      <small style="color:var(--text-muted)">Minuscules, chiffres, point et trait d'union uniquement.</small>
    </div>
    <div class="form-group">
      <label for="role">Rôle</label>
      <select id="role" name="role" required>
        <option value="">-- Choisir un rôle --</option>
        <option value="<?= ROLE_CAISSIER ?>"   <?= ($_POST['role']??'')===ROLE_CAISSIER   ?'selected':'' ?>>Caissier</option>
        <option value="<?= ROLE_MANAGER ?>"    <?= ($_POST['role']??'')===ROLE_MANAGER    ?'selected':'' ?>>Manager</option>
        <option value="<?= ROLE_SUPERADMIN ?>" <?= ($_POST['role']??'')===ROLE_SUPERADMIN ?'selected':'' ?>>Super Administrateur</option>
      </select>
    </div>
    <div class="form-row">
      <div class="form-group">
        <label for="mot_de_passe">Mot de passe</label>
        <input type="password" id="mot_de_passe" name="mot_de_passe"
               placeholder="Min. 6 caractères" required minlength="6">
      </div>
      <div class="form-group">
        <label for="confirmation">Confirmation</label>
        <input type="password" id="confirmation" name="confirmation"
               placeholder="Répéter le mot de passe" required>
      </div>
    </div>
    <div style="display:flex;gap:.75rem;margin-top:.5rem;">
      <button type="submit" class="btn btn-primary">⊕ Créer le compte</button>
      <a href="/facturation/modules/admin/gestion-comptes.php" class="btn btn-secondary">Annuler</a>
    </div>
  </form>
</div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
