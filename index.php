<?php
// index.php — Page d'accueil / tableau de bord

require_once __DIR__ . '../auth/session.php';
exiger_role(ROLE_CAISSIER);

require_once __DIR__ . '../includes/fonctions-factures.php';
require_once __DIR__ . '../includes/fonctions-produits.php';

// Statistiques rapides
$factures_jour = array_values(factures_du_jour());
$nb_factures   = count($factures_jour);
$ca_jour       = array_sum(array_column($factures_jour, 'total_ttc'));
$nb_produits   = count(charger_produits());

$titre = "Tableau de bord";
require_once __DIR__ . '/./includes/header.php';
?>

<div class="page-header">
  <h2>Tableau de bord</h2>
  <p>Bienvenue, <?= htmlspecialchars($_SESSION['nom_complet']) ?> — <?= date('l d F Y') ?></p>
</div>

<!-- KPIs -->
<div style="display:grid;grid-template-columns:repeat(3,1fr);gap:1rem;margin-bottom:2rem;">
  <div class="card" style="text-align:center;">
    <div style="font-size:2rem;font-weight:800;color:var(--green);font-family:var(--font-mono)"><?= $nb_factures ?></div>
    <div style="color:var(--text-muted);font-size:.82rem;text-transform:uppercase;letter-spacing:.05em">Factures aujourd'hui</div>
  </div>
  <div class="card" style="text-align:center;">
    <div style="font-size:2rem;font-weight:800;color:var(--amber);font-family:var(--font-mono)"><?= number_format($ca_jour,0,'.',' ') ?></div>
    <div style="color:var(--text-muted);font-size:.82rem;text-transform:uppercase;letter-spacing:.05em">CA TTC (CDF)</div>
  </div>
  <div class="card" style="text-align:center;">
    <div style="font-size:2rem;font-weight:800;color:var(--blue);font-family:var(--font-mono)"><?= $nb_produits ?></div>
    <div style="color:var(--text-muted);font-size:.82rem;text-transform:uppercase;letter-spacing:.05em">Produits en catalogue</div>
  </div>
</div>

<!-- Actions rapides -->
<div class="dashboard-grid">

  <?php if (a_role(ROLE_CAISSIER)): ?>
  <a href="/facturation/modules/facturation/nouvelle-facture.php" class="dash-card">
    <span class="dash-icon">🧾</span>
    <span class="dash-title">Nouvelle Facture</span>
    <span class="dash-desc">Scanner des articles et créer une facture</span>
  </a>
  <?php endif; ?>

  <?php if (a_role(ROLE_MANAGER)): ?>
  <a href="/facturation/modules/produits/enregistrer.php" class="dash-card">
    <span class="dash-icon">📦</span>
    <span class="dash-title">Enregistrer un Produit</span>
    <span class="dash-desc">Scanner un code-barres et enregistrer un nouveau produit</span>
  </a>
  <a href="/facturation/modules/produits/liste.php" class="dash-card">
    <span class="dash-icon">📋</span>
    <span class="dash-title">Catalogue Produits</span>
    <span class="dash-desc">Consulter et gérer tous les produits</span>
  </a>
  <a href="/facturation/rapports/rapport-journalier.php" class="dash-card">
    <span class="dash-icon">📊</span>
    <span class="dash-title">Rapport Journalier</span>
    <span class="dash-desc">Résumé des ventes du jour</span>
  </a>
  <a href="/facturation/rapports/rapport-mensuel.php" class="dash-card">
    <span class="dash-icon">📅</span>
    <span class="dash-title">Rapport Mensuel</span>
    <span class="dash-desc">Analyse des ventes du mois</span>
  </a>
  <?php endif; ?>

  <?php if (a_role(ROLE_SUPERADMIN)): ?>
  <a href="/facturation/modules/admin/gestion-comptes.php" class="dash-card">
    <span class="dash-icon">👥</span>
    <span class="dash-title">Gestion des Comptes</span>
    <span class="dash-desc">Créer et supprimer des utilisateurs</span>
  </a>
  <?php endif; ?>

</div>

<?php require_once __DIR__ . '../includes/footer.php'; ?>
