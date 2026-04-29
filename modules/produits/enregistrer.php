<?php
// modules/produits/enregistrer.php — Enregistrement d'un produit par scan de code-barres

require_once __DIR__ . '/../../auth/session.php';
exiger_role(ROLE_MANAGER);

require_once __DIR__ . '/../../includes/fonctions-produits.php';

$erreur   = '';
$succes   = '';
$produit  = null;
$code     = '';

// Recherche d'un produit existant (via scan ou formulaire GET)
if (!empty($_GET['code'])) {
    $code    = trim($_GET['code']);
    $produit = trouver_produit($code);
}

// Traitement du formulaire d'enregistrement
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $donnees = [
        'code_barre'       => trim($_POST['code_barre']      ?? ''),
        'nom'              => trim($_POST['nom']             ?? ''),
        'prix_unitaire_ht' => $_POST['prix_unitaire_ht']     ?? '',
        'date_expiration'  => $_POST['date_expiration']       ?? '',
        'quantite_stock'   => $_POST['quantite_stock']        ?? '',
    ];
    $resultat = enregistrer_produit($donnees);
    if ($resultat === true) {
        $_SESSION['succes'] = "Produit « {$donnees['nom']} » enregistré avec succès.";
        header('Location: /facturation/modules/produits/liste.php');
        exit;
    } else {
        $erreur = $resultat;
        $code   = $donnees['code_barre'];
    }
}

$titre = "Enregistrer un Produit";
require_once __DIR__ . '/../../includes/header.php';
?>

<div class="page-header">
  <h2>⊕ Enregistrer un Produit</h2>
  <p>Scannez le code-barres ou saisissez-le manuellement pour enregistrer un produit.</p>
</div>

<div style="display:grid;grid-template-columns:1fr 1fr;gap:1.5rem;align-items:start;">

  <!-- Scanner -->
  <div class="card">
    <div class="card-title">📷 Lecteur de Code-Barres</div>
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

  <!-- Formulaire -->
  <div class="card">
    <div class="card-title">✎ Informations Produit</div>

    <?php if ($erreur): ?>
      <div class="alert alert-erreur"><?= htmlspecialchars($erreur) ?></div>
    <?php endif; ?>

    <?php if ($produit): ?>
      <div class="alert alert-info">ℹ Ce code-barres est déjà enregistré. Vous pouvez modifier les informations ci-dessous.</div>
    <?php endif; ?>

    <form method="POST" action="">
      <div class="form-group">
        <label for="code_barre">Code-Barres</label>
        <input type="text" id="code_barre" name="code_barre"
               value="<?= htmlspecialchars($produit['code_barre'] ?? $code ?? $_POST['code_barre'] ?? '') ?>"
               placeholder="Scanner ou saisir le code" required>
      </div>
      <div class="form-group">
        <label for="nom">Nom du produit</label>
        <input type="text" id="nom" name="nom"
               value="<?= htmlspecialchars($produit['nom'] ?? $_POST['nom'] ?? '') ?>"
               placeholder="ex: Huile de palme 1L" required>
      </div>
      <div class="form-row">
        <div class="form-group">
          <label for="prix_unitaire_ht">Prix unitaire HT (CDF)</label>
          <input type="number" id="prix_unitaire_ht" name="prix_unitaire_ht"
                 value="<?= htmlspecialchars((string)($produit['prix_unitaire_ht'] ?? $_POST['prix_unitaire_ht'] ?? '')) ?>"
                 placeholder="1200" min="0.01" step="0.01" required>
        </div>
        <div class="form-group">
          <label for="quantite_stock">Quantité en stock</label>
          <input type="number" id="quantite_stock" name="quantite_stock"
                 value="<?= htmlspecialchars((string)($produit['quantite_stock'] ?? $_POST['quantite_stock'] ?? '')) ?>"
                 placeholder="50" min="0" step="1" required>
        </div>
      </div>
      <div class="form-group">
        <label for="date_expiration">Date d'expiration (AAAA-MM-JJ)</label>
        <input type="date" id="date_expiration" name="date_expiration"
               value="<?= htmlspecialchars($produit['date_expiration'] ?? $_POST['date_expiration'] ?? '') ?>"
               required>
      </div>
      <div style="display:flex;gap:.75rem;margin-top:.5rem;">
        <button type="submit" class="btn btn-primary">
          <?= $produit ? '✔ Mettre à jour' : '⊕ Enregistrer' ?>
        </button>
        <a href="/facturation/modules/produits/liste.php" class="btn btn-secondary">Voir le catalogue</a>
      </div>
    </form>
  </div>
</div>

<!-- QuaggaJS CDN -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/quagga/0.12.1/quagga.min.js"></script>
<script src="/facturation/assets/js/scanner.js"></script>
<script>
  initScanner('scanner-video', 'scanner-result', 'code_barre', function(code) {
    // Auto-submit après scan pour rechercher le produit existant
    const form = document.querySelector('form');
    const input = document.getElementById('code_barre');
    if (input) input.value = code;
    // Lookup AJAX
    fetch('/facturation/modules/produits/lire.php?code=' + encodeURIComponent(code))
      .then(r => r.json())
      .then(data => {
        if (data.found) {
          document.getElementById('nom').value              = data.produit.nom || '';
          document.getElementById('prix_unitaire_ht').value = data.produit.prix_unitaire_ht || '';
          document.getElementById('quantite_stock').value   = data.produit.quantite_stock || '';
          document.getElementById('date_expiration').value  = data.produit.date_expiration || '';
          document.getElementById('scanner-result').innerHTML = '<span class="dot"></span> ✔ Produit existant chargé : <strong>' + data.produit.nom + '</strong>';
        }
      });
  });
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
