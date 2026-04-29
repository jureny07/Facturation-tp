<?php
// rapports/rapport-journalier.php — Rapport des ventes du jour

require_once __DIR__ . '/../auth/session.php';
exiger_role(ROLE_MANAGER);
require_once __DIR__ . '/../includes/fonctions-factures.php';

$date_choisie = $_GET['date'] ?? date('Y-m-d');
// Validation format date
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date_choisie)) $date_choisie = date('Y-m-d');

$toutes = charger_factures();
$factures = array_values(array_filter($toutes, fn($f) => $f['date'] === $date_choisie));

$total_ht  = array_sum(array_column($factures, 'total_ht'));
$total_tva = array_sum(array_column($factures, 'tva'));
$total_ttc = array_sum(array_column($factures, 'total_ttc'));

$titre = "Rapport Journalier";
require_once __DIR__ . '/../includes/header.php';
?>

<div class="page-header">
  <h2>📊 Rapport Journalier</h2>
</div>

<div style="display:flex;align-items:center;gap:1rem;margin-bottom:1.5rem;">
  <form method="GET" action="" style="display:flex;gap:.5rem;align-items:center;">
    <label style="color:var(--text-muted);font-size:.85rem;font-weight:600;">Date :</label>
    <input type="date" name="date" value="<?= htmlspecialchars($date_choisie) ?>"
           style="background:var(--bg-input);border:1px solid var(--border);color:var(--text);padding:.4rem .7rem;border-radius:var(--radius);font-family:var(--font-mono);">
    <button type="submit" class="btn btn-secondary">Afficher</button>
  </form>
</div>

<!-- KPIs du jour -->
<div style="display:grid;grid-template-columns:repeat(4,1fr);gap:1rem;margin-bottom:2rem;">
  <div class="card" style="text-align:center;">
    <div style="font-size:1.8rem;font-weight:800;color:var(--blue);font-family:var(--font-mono)"><?= count($factures) ?></div>
    <div style="color:var(--text-muted);font-size:.78rem;text-transform:uppercase;letter-spacing:.05em">Transactions</div>
  </div>
  <div class="card" style="text-align:center;">
    <div style="font-size:1.8rem;font-weight:800;color:var(--text);font-family:var(--font-mono)"><?= number_format($total_ht,0,'.',' ') ?></div>
    <div style="color:var(--text-muted);font-size:.78rem;text-transform:uppercase;letter-spacing:.05em">Total HT (CDF)</div>
  </div>
  <div class="card" style="text-align:center;">
    <div style="font-size:1.8rem;font-weight:800;color:var(--amber);font-family:var(--font-mono)"><?= number_format($total_tva,0,'.',' ') ?></div>
    <div style="color:var(--text-muted);font-size:.78rem;text-transform:uppercase;letter-spacing:.05em">TVA collectée (CDF)</div>
  </div>
  <div class="card" style="text-align:center;">
    <div style="font-size:1.8rem;font-weight:800;color:var(--green);font-family:var(--font-mono)"><?= number_format($total_ttc,0,'.',' ') ?></div>
    <div style="color:var(--text-muted);font-size:.78rem;text-transform:uppercase;letter-spacing:.05em">CA TTC (CDF)</div>
  </div>
</div>

<!-- Liste des factures -->
<?php if (empty($factures)): ?>
  <div class="alert alert-info">Aucune vente enregistrée pour le <?= htmlspecialchars($date_choisie) ?>.</div>
<?php else: ?>
<div class="card">
  <div class="card-title">Détail des transactions</div>
  <div class="table-wrapper">
    <table>
      <thead>
        <tr>
          <th>N° Facture</th>
          <th>Heure</th>
          <th>Caissier</th>
          <th style="text-align:center">Articles</th>
          <th style="text-align:right">Total HT</th>
          <th style="text-align:right">TVA</th>
          <th style="text-align:right">Total TTC</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($factures as $f): ?>
        <tr>
          <td class="td-mono" style="color:var(--green)"><?= htmlspecialchars($f['id_facture']) ?></td>
          <td class="td-mono"><?= htmlspecialchars($f['heure']) ?></td>
          <td><?= htmlspecialchars($f['caissier']) ?></td>
          <td style="text-align:center"><?= count($f['articles']) ?></td>
          <td class="td-amount"><?= number_format($f['total_ht'],2,'.',' ') ?></td>
          <td class="td-amount"><?= number_format($f['tva'],2,'.',' ') ?></td>
          <td class="td-amount" style="color:var(--green)"><?= number_format($f['total_ttc'],2,'.',' ') ?></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
      <tfoot>
        <tr style="border-top:2px solid var(--border)">
          <td colspan="4" style="font-weight:700;padding:.7rem 1rem;">TOTAUX</td>
          <td class="td-amount" style="font-weight:700;"><?= number_format($total_ht,2,'.',' ') ?></td>
          <td class="td-amount" style="font-weight:700;"><?= number_format($total_tva,2,'.',' ') ?></td>
          <td class="td-amount" style="font-weight:700;color:var(--green)"><?= number_format($total_ttc,2,'.',' ') ?></td>
        </tr>
      </tfoot>
    </table>
  </div>
</div>
<?php endif; ?>

<div class="no-print" style="margin-top:1rem;">
  <button onclick="window.print()" class="btn btn-secondary">🖨 Imprimer</button>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
