<?php
// includes/header.php — En-tête HTML commun à toutes les pages

require_once __DIR__ . '/../auth/session.php';
$titre_page = $titre ?? APP_NAME;
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= htmlspecialchars($titre_page) ?> — SuperCaisse</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;600;700;800&family=DM+Mono:wght@400;500&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="/facturation/assets/css/style.css">
</head>
<body>

<?php if (isset($_SESSION['identifiant'])): ?>
<nav class="navbar">
  <a href="/facturation/index.php" class="nav-brand">
    <span class="brand-icon">◈</span>
    <span>SuperCaisse</span>
  </a>
  <div class="nav-links">
    <?php if (a_role(ROLE_CAISSIER)): ?>
      <a href="/facturation/modules/facturation/nouvelle-facture.php" class="nav-link <?= str_contains($_SERVER['PHP_SELF'],'nouvelle-facture') ? 'active' : '' ?>">
        ⊞ Nouvelle Facture
      </a>
    <?php endif; ?>
    <?php if (a_role(ROLE_MANAGER)): ?>
      <a href="/facturation/modules/produits/enregistrer.php" class="nav-link <?= str_contains($_SERVER['PHP_SELF'],'enregistrer') ? 'active' : '' ?>">
        ⊕ Produits
      </a>
      <a href="/facturation/rapports/rapport-journalier.php" class="nav-link <?= str_contains($_SERVER['PHP_SELF'],'rapport') ? 'active' : '' ?>">
        ≡ Rapports
      </a>
    <?php endif; ?>
    <?php if (a_role(ROLE_SUPERADMIN)): ?>
      <a href="/facturation/modules/admin/gestion-comptes.php" class="nav-link <?= str_contains($_SERVER['PHP_SELF'],'admin') ? 'active' : '' ?>">
        ⊛ Admin
      </a>
    <?php endif; ?>
  </div>
  <div class="nav-user">
    <span class="user-badge user-badge--<?= $_SESSION['role'] ?>">
      <?= htmlspecialchars($_SESSION['nom_complet']) ?>
      <em><?= ucfirst($_SESSION['role']) ?></em>
    </span>
    <a href="/facturation/auth/logout.php" class="btn-logout">Déconnexion</a>
  </div>
</nav>
<?php endif; ?>

<main class="main-content">

<?php
// Affichage des messages flash
foreach (['succes','erreur','info'] as $type) {
    if (!empty($_SESSION[$type])) {
        echo "<div class='alert alert-{$type}'>" . htmlspecialchars($_SESSION[$type]) . "</div>";
        unset($_SESSION[$type]);
    }
}
?>
