<?php
// rapports/rapport-mensuel.php — Rapport des ventes du mois

require_once __DIR__ . '/../auth/session.php';
exiger_role(ROLE_MANAGER);
require_once __DIR__ . '/../includes/fonctions-factures.php';

$mois_choisi = $_GET['mois'] ?? date('Y-m');
if (!preg_match('/^\d{4}-\d{2}$/', $mois_choisi)) $mois_choisi = date('Y-m');

$factures = array_values(factures_du_mois($mois_choisi));

// Agrégation par jour
$par_jour = [];
foreach ($factures as $f) {
    $j = $f['date'];
    if (!isset($par_jour[$j])) $par_jour[$j] = ['nb'=>0,'ht'=>0,'ttc'=>0];
    $par_jour[$j]['nb']++;
    $par_jour[$j]['ht']  += $f['total_ht'];
    $par_jour[$j]['ttc'] += $f['total_ttc'];
}
ksort($par_jour);

$total_ttc = array_sum(array_column($factures,'total_ttc'));
$total_ht  = array_sum(array_column($factures,'total_ht'));
$total_tva = array_sum(array_column($factures,'tva'));

$titre = "Rapport Mensuel";
require_once __DIR__ . '/../includes/header.php';
?>

<div class="page-header">
  <h2>📅 Rapport Mensuel</h2>
</div>

<div style="display:flex;align-items:center;gap:1rem;margin-bottom:1.5rem;">
  <form method="GET" action="" style="display:flex;gap:.5rem;align-items:center;">
    <label style="color:var(--text-muted);font-size:.85rem;font-weight:600;">Mois :</label>
    <input type="month" name="mois" value="<?= htmlspecialchars($mois_choisi) ?>"
           style="background:var(--bg-input);border:1px solid var(--border);color:var(--text);padding:.4rem .7rem;border-radius:var(--radius);font-family:var(--font-mono);">
    <button type="submit" class="btn btn-secondary">Afficher</button>
  </form>
</div>

<!-- KPIs -->
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
    <div style="color:var(--text-muted);font-size:.78rem;text-transform:uppercase;letter-spacing:.05em">TVA (CDF)</div>
  </div>
  <div class="card" style="text-align:center;">
    <div style="font-size:1.8rem;font-weight:800;color:var(--green);font-family:var(--font-mono)"><?= number_format($total_ttc,0,'.',' ') ?></div>
    <div style="color:var(--text-muted);font-size:.78rem;text-transform:uppercase;letter-spacing:.05em">CA TTC (CDF)</div>
  </div>
</div>

<!-- Par jour -->
<?php if (empty($par_jour)): ?>
  <div class="alert alert-info">Aucune vente pour <?= htmlspecialchars($mois_choisi) ?>.</div>
<?php else: ?>
<div class="card">
  <div class="card-title">Récapitulatif par jour</div>
  <div class="table-wrapper">
    <table>
      <thead>
        <tr>
          <th>Date</th>
          <th style="text-align:center">Nb transactions</th>
          <th style="text-align:right">CA HT (CDF)</th>
          <th style="text-align:right">CA TTC (CDF)</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($par_jour as $date => $data): ?>
        <tr>
          <td class="td-mono"><?= htmlspecialchars($date) ?></td>
          <td style="text-align:center"><?= $data['nb'] ?></td>
          <td class="td-amount"><?= number_format($data['ht'],2,'.',' ') ?></td>
          <td class="td-amount" style="color:var(--green)"><?= number_format($data['ttc'],2,'.',' ') ?></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
      <tfoot>
        <tr style="border-top:2px solid var(--border)">
          <td style="font-weight:700;padding:.7rem 1rem;">TOTAUX</td>
          <td style="text-align:center;font-weight:700;"><?= count($factures) ?></td>
          <td class="td-amount" style="font-weight:700;"><?= number_format($total_ht,2,'.',' ') ?></td>
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
