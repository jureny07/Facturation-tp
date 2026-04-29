<?php
// modules/facturation/afficher-facture.php — Affichage et impression d'une facture

require_once __DIR__ . '/../../auth/session.php';
exiger_role(ROLE_CAISSIER);

$facture = $_SESSION['derniere_facture'] ?? null;

if (!$facture) {
    $_SESSION['erreur'] = "Aucune facture à afficher.";
    header('Location: /facturation/modules/facturation/nouvelle-facture.php');
    exit;
}

$titre = "Facture " . $facture['id_facture'];
require_once __DIR__ . '/../../includes/header.php';
?>

<div class="page-header no-print">
  <h2>✔ Facture Validée</h2>
  <p>La facture a été enregistrée et le stock mis à jour.</p>
</div>

<div class="card" style="max-width:680px;margin:0 auto;" id="facture-print">

  <div class="facture-header">
    <div style="font-size:.8rem;color:var(--text-muted);text-transform:uppercase;letter-spacing:.1em;">SuperMarché — Caisse</div>
    <div class="facture-id"><?= htmlspecialchars($facture['id_facture']) ?></div>
    <div style="font-size:.85rem;color:var(--text-muted);margin-top:.4rem;">
      <?= htmlspecialchars($facture['date']) ?> à <?= htmlspecialchars($facture['heure']) ?>
      &nbsp;·&nbsp; Caissier : <?= htmlspecialchars($facture['caissier']) ?>
    </div>
  </div>

  <!-- Articles -->
  <div class="table-wrapper">
    <table>
      <thead>
        <tr>
          <th>Désignation</th>
          <th style="text-align:right">Prix unit. HT</th>
          <th style="text-align:center">Qté</th>
          <th style="text-align:right">Sous-total HT</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($facture['articles'] as $art): ?>
        <tr>
          <td><?= htmlspecialchars($art['nom']) ?></td>
          <td class="td-amount"><?= number_format($art['prix_unitaire_ht'],2,'.',' ') ?> CDF</td>
          <td style="text-align:center"><?= $art['quantite'] ?></td>
          <td class="td-amount"><?= number_format($art['sous_total_ht'],2,'.',' ') ?> CDF</td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>

  <!-- Totaux -->
  <div class="facture-totaux">
    <div class="total-row">
      <span class="label">Total HT</span>
      <span class="value"><?= number_format($facture['total_ht'],2,'.',' ') ?> CDF</span>
    </div>
    <div class="total-row">
      <span class="label">TVA (<?= TVA_TAUX*100 ?>%)</span>
      <span class="value"><?= number_format($facture['tva'],2,'.',' ') ?> CDF</span>
    </div>
    <div class="total-row total-ttc">
      <span class="label">Net à payer</span>
      <span class="value"><?= number_format($facture['total_ttc'],2,'.',' ') ?> CDF</span>
    </div>
  </div>

  <div style="margin-top:1.5rem;text-align:center;font-size:.78rem;color:var(--text-muted);">
    Merci pour votre achat — Conservez ce reçu
  </div>
</div>

<!-- Actions -->
<div style="max-width:680px;margin:1rem auto;display:flex;gap:.75rem;" class="no-print">
  <button onclick="window.print()" class="btn btn-primary">🖨 Imprimer</button>
  <a href="/facturation/modules/facturation/nouvelle-facture.php" class="btn btn-secondary">⊕ Nouvelle Facture</a>
  <a href="/facturation/index.php" class="btn btn-secondary">⌂ Accueil</a>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
