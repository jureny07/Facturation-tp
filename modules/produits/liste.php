<?php
// modules/produits/liste.php — Catalogue de tous les produits

require_once __DIR__ . '/../../auth/session.php';
exiger_role(ROLE_MANAGER);
require_once __DIR__ . '/../../includes/fonctions-produits.php';

$produits = charger_produits();
$titre = "Catalogue Produits";
require_once __DIR__ . '/../../includes/header.php';

// Tri par nom
usort($produits, fn($a,$b) => strcmp($a['nom'], $b['nom']));
?>

<div class="page-header">
  <h2>📋 Catalogue Produits</h2>
  <p><?= count($produits) ?> produit(s) enregistré(s)</p>
</div>

<div style="margin-bottom:1rem;">
  <a href="/facturation/modules/produits/enregistrer.php" class="btn btn-primary">⊕ Nouveau Produit</a>
</div>

<?php if (empty($produits)): ?>
  <div class="alert alert-info">Aucun produit enregistré. <a href="/facturation/modules/produits/enregistrer.php" style="color:var(--blue)">Enregistrez le premier produit.</a></div>
<?php else: ?>
<div class="card">
  <div class="table-wrapper">
    <table>
      <thead>
        <tr>
          <th>Code-Barres</th>
          <th>Nom</th>
          <th style="text-align:right">Prix HT (CDF)</th>
          <th style="text-align:center">Stock</th>
          <th>Expiration</th>
          <th>Enregistré le</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($produits as $p):
          $stock_class = $p['quantite_stock'] <= 0 ? 'badge-danger' : ($p['quantite_stock'] <= 5 ? 'badge-warn' : 'badge-ok');
        ?>
        <tr>
          <td class="td-mono"><?= htmlspecialchars($p['code_barre']) ?></td>
          <td><strong><?= htmlspecialchars($p['nom']) ?></strong></td>
          <td class="td-amount"><?= number_format($p['prix_unitaire_ht'],2,'.',' ') ?></td>
          <td style="text-align:center">
            <span class="badge <?= $stock_class ?>"><?= $p['quantite_stock'] ?></span>
          </td>
          <td class="td-mono"><?= htmlspecialchars($p['date_expiration']) ?></td>
          <td class="td-mono"><?= htmlspecialchars($p['date_enregistrement'] ?? '—') ?></td>
          <td>
            <a href="/facturation/modules/produits/enregistrer.php?code=<?= urlencode($p['code_barre']) ?>"
               class="btn btn-secondary" style="padding:.3rem .7rem;font-size:.78rem;">✎ Modifier</a>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
<?php endif; ?>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
