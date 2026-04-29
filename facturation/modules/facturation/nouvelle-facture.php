<?php
// modules/facturation/nouvelle-facture.php — Création d'une nouvelle facture

require_once __DIR__ . '/../../auth/session.php';
exiger_role(ROLE_CAISSIER);
require_once __DIR__ . '/../../includes/fonctions-produits.php';
require_once __DIR__ . '/../../includes/fonctions-factures.php';

// Panier en session
if (!isset($_SESSION['panier'])) $_SESSION['panier'] = [];

$erreur_article = '';
$info_article   = '';

// ── Action : Ajouter un article ──────────────────────────────
if (isset($_POST['action']) && $_POST['action'] === 'ajouter') {
    $code     = trim($_POST['code_barre'] ?? '');
    $quantite = (int)($_POST['quantite'] ?? 1);

    if (empty($code)) {
        $erreur_article = "Veuillez scanner ou saisir un code-barres.";
    } elseif ($quantite <= 0) {
        $erreur_article = "La quantité doit être supérieure à zéro.";
    } else {
        $produit = trouver_produit($code);
        if (!$produit) {
            $erreur_article = "Produit inconnu (code : {$code}). Veuillez demander au Manager de l'enregistrer.";
        } elseif ($quantite > $produit['quantite_stock']) {
            $erreur_article = "Stock insuffisant pour « {$produit['nom']} » — disponible : {$produit['quantite_stock']}.";
        } else {
            // Vérifier si déjà dans le panier
            $trouve = false;
            foreach ($_SESSION['panier'] as &$item) {
                if ($item['code_barre'] === $code) {
                    $nouvelle_qte = $item['quantite'] + $quantite;
                    if ($nouvelle_qte > $produit['quantite_stock']) {
                        $erreur_article = "Quantité totale ({$nouvelle_qte}) dépasse le stock ({$produit['quantite_stock']}).";
                    } else {
                        $item['quantite']      = $nouvelle_qte;
                        $item['sous_total_ht'] = round($item['prix_unitaire_ht'] * $nouvelle_qte, 2);
                        $info_article = "Quantité de « {$produit['nom']} » mise à jour.";
                    }
                    $trouve = true;
                    break;
                }
            }
            unset($item);
            if (!$trouve) {
                $_SESSION['panier'][] = [
                    'code_barre'       => $code,
                    'nom'              => $produit['nom'],
                    'prix_unitaire_ht' => $produit['prix_unitaire_ht'],
                    'quantite'         => $quantite,
                    'sous_total_ht'    => round($produit['prix_unitaire_ht'] * $quantite, 2),
                ];
                $info_article = "« {$produit['nom']} » ajouté au panier.";
            }
        }
    }
}

// ── Action : Supprimer un article ───────────────────────────
if (isset($_POST['action']) && $_POST['action'] === 'supprimer') {
    $idx = (int)($_POST['index'] ?? -1);
    if (isset($_SESSION['panier'][$idx])) {
        array_splice($_SESSION['panier'], $idx, 1);
    }
}

// ── Action : Vider le panier ────────────────────────────────
if (isset($_POST['action']) && $_POST['action'] === 'vider') {
    $_SESSION['panier'] = [];
}

// ── Action : Valider la facture ─────────────────────────────
if (isset($_POST['action']) && $_POST['action'] === 'valider') {
    $resultat = valider_facture($_SESSION['panier'], $_SESSION['identifiant']);
    if (is_array($resultat)) {
        $_SESSION['panier'] = [];
        $_SESSION['derniere_facture'] = $resultat;
        header('Location: /modules/facturation/afficher-facture.php');
        exit;
    } else {
        $erreur_article = $resultat;
    }
}

// Calcul des totaux courants
$totaux = calculer_totaux($_SESSION['panier']);

$titre = "Nouvelle Facture";
require_once __DIR__ . '/../../includes/header.php';
?>

<div class="page-header">
  <h2>🧾 Nouvelle Facture</h2>
  <p>Scannez les articles un par un pour constituer la facture.</p>
</div>

<div style="display:grid;grid-template-columns:1fr 1.6fr;gap:1.5rem;align-items:start;">

  <!-- Panneau scanner + ajout article -->
  <div>
    <div class="card">
      <div class="card-title">📷 Scanner un Article</div>
      <div class="scanner-wrapper">
        <video id="scanner-video" muted playsinline></video>
        <div class="scanner-overlay"><div class="scanner-crosshair"></div></div>
      </div>
      <div class="scanner-result" id="scanner-result">
        <span class="dot"></span> En attente de scan…
      </div>
      <div class="scanner-controls">
        <button id="btn-scanner-start" class="btn btn-primary">▶ Démarrer</button>
        <button id="btn-scanner-stop"  class="btn btn-secondary" disabled>⏹ Arrêter</button>
      </div>
    </div>

    <div class="card">
      <div class="card-title">+ Ajouter au Panier</div>

      <?php if ($erreur_article): ?>
        <div class="alert alert-erreur"><?= htmlspecialchars($erreur_article) ?></div>
      <?php elseif ($info_article): ?>
        <div class="alert alert-succes"><?= htmlspecialchars($info_article) ?></div>
      <?php endif; ?>

      <form method="POST" action="" id="form-ajouter">
        <input type="hidden" name="action" value="ajouter">
        <div class="form-group">
          <label for="code_barre">Code-Barres</label>
          <input type="text" id="code_barre" name="code_barre"
                 placeholder="Scanner ou saisir" required autofocus>
        </div>
        <div class="form-group">
          <label for="quantite">Quantité</label>
          <input type="number" id="quantite" name="quantite"
                 value="1" min="1" step="1" required>
        </div>
        <button type="submit" class="btn btn-primary">⊕ Ajouter</button>
      </form>
    </div>
  </div>

  <!-- Panier en cours -->
  <div>
    <div class="card">
      <div class="card-title" style="justify-content:space-between;">
        <span>🛒 Panier en cours</span>
        <?php if (!empty($_SESSION['panier'])): ?>
        <form method="POST" action="" style="margin:0;">
          <input type="hidden" name="action" value="vider">
          <button type="submit" class="btn btn-secondary" style="padding:.25rem .6rem;font-size:.75rem;">✕ Vider</button>
        </form>
        <?php endif; ?>
      </div>

      <?php if (empty($_SESSION['panier'])): ?>
        <p style="color:var(--text-muted);text-align:center;padding:2rem 0;">Aucun article pour l'instant. Scannez un code-barres pour commencer.</p>
      <?php else: ?>
      <div class="table-wrapper">
        <table class="cart-table">
          <thead>
            <tr>
              <th>Désignation</th>
              <th style="text-align:right">Prix HT</th>
              <th style="text-align:center">Qté</th>
              <th style="text-align:right">Sous-total HT</th>
              <th></th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($_SESSION['panier'] as $i => $item): ?>
            <tr>
              <td>
                <strong><?= htmlspecialchars($item['nom']) ?></strong>
                <div class="td-mono" style="font-size:.72rem"><?= htmlspecialchars($item['code_barre']) ?></div>
              </td>
              <td class="td-amount"><?= number_format($item['prix_unitaire_ht'],2,'.',' ') ?></td>
              <td class="qty-cell"><?= $item['quantite'] ?></td>
              <td class="td-amount"><?= number_format($item['sous_total_ht'],2,'.',' ') ?></td>
              <td>
                <form method="POST" action="">
                  <input type="hidden" name="action" value="supprimer">
                  <input type="hidden" name="index" value="<?= $i ?>">
                  <button type="submit" class="remove-btn">✕</button>
                </form>
              </td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>

      <!-- Totaux -->
      <div class="facture-totaux">
        <div class="total-row">
          <span class="label">Total HT</span>
          <span class="value"><?= number_format($totaux['total_ht'],2,'.',' ') ?> CDF</span>
        </div>
        <div class="total-row">
          <span class="label">TVA (<?= TVA_TAUX*100 ?>%)</span>
          <span class="value"><?= number_format($totaux['tva'],2,'.',' ') ?> CDF</span>
        </div>
        <div class="total-row total-ttc">
          <span class="label">Net à payer</span>
          <span class="value"><?= number_format($totaux['total_ttc'],2,'.',' ') ?> CDF</span>
        </div>
      </div>

      <!-- Validation -->
      <div style="margin-top:1.25rem;">
        <form method="POST" action="">
          <input type="hidden" name="action" value="valider">
          <button type="submit" class="btn btn-primary btn-full" style="font-size:1rem;padding:.9rem;">
            ✔ Valider et Imprimer la Facture
          </button>
        </form>
      </div>
      <?php endif; ?>
    </div>
  </div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/quagga/0.12.1/quagga.min.js"></script>
<script src="/assets/js/scanner.js"></script>
<script>
  initScanner('scanner-video', 'scanner-result', 'code_barre', function(code) {
    // Optionnel : chercher le nom du produit en temps réel
    fetch('/modules/produits/lire.php?code=' + encodeURIComponent(code))
      .then(r => r.json())
      .then(data => {
        const el = document.getElementById('scanner-result');
        if (data.found) {
          el.innerHTML = '<span class="dot"></span> ✔ ' + data.produit.nom + ' — ' + data.produit.prix_unitaire_ht + ' CDF — Stock : ' + data.produit.quantite_stock;
        } else {
          el.innerHTML = '<span style="color:var(--red)">⚠ Produit inconnu : ' + code + '</span>';
        }
      });
  });
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
