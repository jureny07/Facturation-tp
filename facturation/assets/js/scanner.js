// assets/js/scanner.js — Intégration QuaggaJS pour la lecture de codes-barres

/**
 * Initialise le scanner de codes-barres.
 * @param {string} videoId     - ID de l'élément <video> pour la caméra
 * @param {string} resultId    - ID de l'élément affichant le résultat
 * @param {string} inputId     - ID du champ <input> à remplir avec le code
 * @param {Function} [onScan]  - Callback optionnel appelé avec le code scanné
 */
function initScanner(videoId, resultId, inputId, onScan) {
  const resultEl = document.getElementById(resultId);
  const inputEl  = document.getElementById(inputId);
  let   lastCode = '';
  let   running  = false;

  function startScanner() {
    if (running) return;
    running = true;

    Quagga.init({
      inputStream: {
        type: 'LiveStream',
        target: document.getElementById(videoId),
        constraints: {
          width:  { ideal: 640 },
          height: { ideal: 320 },
          facingMode: 'environment'
        }
      },
      decoder: {
        readers: [
          'ean_reader', 'ean_8_reader', 'upc_reader',
          'code_128_reader', 'code_39_reader', 'codabar_reader'
        ]
      },
      locate: true,
      numOfWorkers: navigator.hardwareConcurrency || 2,
    }, function(err) {
      if (err) {
        console.error('QuaggaJS init error:', err);
        if (resultEl) resultEl.innerHTML = '<span style="color:var(--red)">⚠ Caméra inaccessible : ' + err.message + '</span>';
        running = false;
        return;
      }
      Quagga.start();
    });

    Quagga.onDetected(function(result) {
      const code = result.codeResult.code;
      if (!code || code === lastCode) return;
      lastCode = code;

      // Vibration retour haptique (mobile)
      if (navigator.vibrate) navigator.vibrate(120);

      // Afficher le résultat
      if (resultEl) {
        resultEl.innerHTML = '<span class="dot"></span> Code détecté : <strong>' + code + '</strong>';
      }

      // Remplir le champ
      if (inputEl) {
        inputEl.value = code;
        inputEl.dispatchEvent(new Event('input', { bubbles: true }));
      }

      // Callback
      if (typeof onScan === 'function') onScan(code);

      // Flash visuel
      const video = document.getElementById(videoId);
      if (video) {
        video.style.outline = '4px solid var(--green)';
        setTimeout(() => { video.style.outline = ''; }, 600);
      }

      // Arrêt auto après scan pour éviter les doublons
      setTimeout(stopScanner, 800);
    });
  }

  function stopScanner() {
    if (!running) return;
    Quagga.stop();
    running = false;
  }

  // Bouton démarrer
  const btnStart = document.getElementById('btn-scanner-start');
  const btnStop  = document.getElementById('btn-scanner-stop');

  if (btnStart) btnStart.addEventListener('click', function() {
    lastCode = ''; // reset pour permettre un nouveau scan
    startScanner();
    this.disabled = true;
    if (btnStop) btnStop.disabled = false;
  });

  if (btnStop) btnStop.addEventListener('click', function() {
    stopScanner();
    if (btnStart) btnStart.disabled = false;
    this.disabled = true;
  });

  // Auto-start si présent dans la page
  if (document.getElementById(videoId)) {
    startScanner();
    if (btnStart) btnStart.disabled = true;
  }
}

/**
 * Saisie manuelle du code : efface l'ancien résultat affiché.
 */
function resetScannerDisplay(resultId) {
  const el = document.getElementById(resultId);
  if (el) el.innerHTML = '<span class="dot"></span> En attente de scan…';
}
