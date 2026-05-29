<?php
// modules/produits/enregistrer.php — Enregistrement d'un produit par scan de code-barres

require_once __DIR__ . '/../../auth/session.php';
exiger_role(ROLE_MANAGER);

require_once __DIR__ . '/../../includes/fonctions-produits.php';

$erreur   = '';
$succes   = '';
$produit  = null;
$code     = '';
$noExpiration = false;

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
        'date_expiration'  => isset($_POST['sans_expiration']) ? '' : ($_POST['date_expiration'] ?? ''),
        'quantite_stock'   => $_POST['quantite_stock']        ?? '',
    ];
    $noExpiration = !empty($_POST['sans_expiration']);
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

if ($produit && $produit['date_expiration'] === '') {
    $noExpiration = true;
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
      <div id="reader" style="width:100%;max-width:420px;margin:auto;"></div>
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
               value="<?= htmlspecialchars($produit['date_expiration'] ?? ($_POST['date_expiration'] ?? '')) ?>"
               <?= $noExpiration ? 'disabled' : 'required' ?> >
      </div>
      <div class="form-group">
        <label class="checkbox-label" style="display:flex;align-items:center;gap:.5rem;">
          <input type="checkbox" id="sans_expiration" name="sans_expiration"
                 <?= $noExpiration ? 'checked' : '' ?>>
          Produit sans date d'expiration
        </label>
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

<script src="https://unpkg.com/html5-qrcode"></script>
<script>
  const scannerResult = document.getElementById('scanner-result');
  const scannerStartBtn = document.getElementById('btn-scanner-start');
  const scannerStopBtn = document.getElementById('btn-scanner-stop');
  const codeInput = document.getElementById('code_barre');
  let html5QrcodeScanner = null;
  let lastScannedCode = null;

  function updateScannerStatus(message, color = '#999') {
    if (!scannerResult) return;
    scannerResult.innerHTML = '<span class="dot"></span> ' + message;
    scannerResult.style.color = color;
  }

  async function startMobileScanner() {
    if (html5QrcodeScanner) return;
    updateScannerStatus('🔄 Initialisation du scanner...');
    html5QrcodeScanner = new Html5Qrcode('reader');
    scannerStartBtn.disabled = true;
    scannerStopBtn.disabled = false;

    try {
      await html5QrcodeScanner.start(
        { facingMode: 'environment' },
        { fps: 10, qrbox: { width: 250, height: 250 } },
        (decodedText) => {
          if (!decodedText || decodedText === lastScannedCode) return;
          lastScannedCode = decodedText;
          if (codeInput) codeInput.value = decodedText;

          fetch('/facturation/modules/produits/lire.php?code=' + encodeURIComponent(decodedText))
            .then(r => r.json())
            .then(data => {
              const dateExpirationInput = document.getElementById('date_expiration');
              const noExpirationCheckbox = document.getElementById('sans_expiration');

              if (data && data.found) {
                document.getElementById('nom').value              = data.produit.nom || '';
                document.getElementById('prix_unitaire_ht').value = data.produit.prix_unitaire_ht || '';
                document.getElementById('quantite_stock').value   = data.produit.quantite_stock || '';
                if (dateExpirationInput) dateExpirationInput.value = data.produit.date_expiration || '';
                if (noExpirationCheckbox) {
                  noExpirationCheckbox.checked = !data.produit.date_expiration;
                }
                syncExpirationFields();
                updateScannerStatus('✔ Produit existant chargé : ' + data.produit.nom, '#4caf50');
              } else {
                if (noExpirationCheckbox) {
                  noExpirationCheckbox.checked = false;
                }
                syncExpirationFields();
                updateScannerStatus('✔ Code détecté : ' + decodedText + ' — produit inconnu.', '#4caf50');
              }
            })
            .catch(err => {
              console.error('Erreur lookup produit', err);
              updateScannerStatus('❌ Erreur recherche produit', '#f44336');
            });
        },
        (errorMessage) => {
          // Ignorer les erreurs mineures de lecture.
        }
      );
      updateScannerStatus('▶ Scanner mobile actif. Présentez un code-barres ou QR.', '#4caf50');
    } catch (error) {
      console.error('Erreur démarrage scanner mobile', error);
      updateScannerStatus('❌ Impossible d\'accéder à la caméra.', '#f44336');
      scannerStartBtn.disabled = false;
      scannerStopBtn.disabled = true;
      html5QrcodeScanner = null;
    }
  }

  async function stopMobileScanner() {
    if (!html5QrcodeScanner) return;
    try {
      await html5QrcodeScanner.stop();
      await html5QrcodeScanner.clear();
    } catch (error) {
      console.error('Erreur arrêt scanner mobile', error);
    }
    html5QrcodeScanner = null;
    scannerStartBtn.disabled = false;
    scannerStopBtn.disabled = true;
    updateScannerStatus('⏹ Scanner mobile arrêté.', '#999');
  }

  function syncExpirationFields() {
    const dateInput = document.getElementById('date_expiration');
    const checkbox = document.getElementById('sans_expiration');
    if (!dateInput || !checkbox) return;
    dateInput.disabled = checkbox.checked;
    dateInput.required = !checkbox.checked;
    if (checkbox.checked) {
      dateInput.value = '';
    }
  }

  const expirationCheckbox = document.getElementById('sans_expiration');
  if (expirationCheckbox) {
    expirationCheckbox.addEventListener('change', syncExpirationFields);
  }
  window.addEventListener('load', syncExpirationFields);

  if (scannerStartBtn) {
    scannerStartBtn.addEventListener('click', startMobileScanner);
  }
  if (scannerStopBtn) {
    scannerStopBtn.addEventListener('click', stopMobileScanner);
  }
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
