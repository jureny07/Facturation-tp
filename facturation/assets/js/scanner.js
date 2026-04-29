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

  // Vérifier si QuaggaJS est chargé
  if (typeof Quagga === 'undefined') {
    if (resultEl) {
      resultEl.innerHTML = '<span style="color:var(--red)">⚠ Erreur : QuaggaJS non chargé. Veuillez recharger la page.</span>';
    }
    console.error('QuaggaJS library not loaded');
    return;
  }

  function startScanner() {
    if (running) return;
    running = true;

    // Vérifier la compatibilité du navigateur
    if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
      if (resultEl) resultEl.innerHTML = '<span style="color:var(--red)">⚠ Votre navigateur ne supporte pas l\'accès caméra.</span>';
      running = false;
      return;
    }

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
        let errorMsg = 'Erreur : ';
        
        // Gestion spécifique des erreurs
        if (err.name === 'NotAllowedError' || err.message.includes('Permission denied')) {
          errorMsg = '⚠ Accès caméra refusé. Vérifiez les permissions du navigateur.';
        } else if (err.name === 'NotFoundError' || err.message.includes('not found')) {
          errorMsg = '⚠ Aucune caméra détectée sur votre appareil.';
        } else if (err.name === 'NotReadableError' || err.message.includes('could not start')) {
          errorMsg = '⚠ La caméra est déjà utilisée par une autre application.';
        } else {
          errorMsg = '⚠ ' + (err.message || 'Erreur caméra inconnue');
        }
        
        if (resultEl) resultEl.innerHTML = '<span style="color:var(--red)">' + errorMsg + '</span>';
        running = false;
        
        // Réactiver le bouton démarrer
        const btnStart = document.getElementById('btn-scanner-start');
        if (btnStart) btnStart.disabled = false;
        
        return;
      }
      
      Quagga.start();
      if (resultEl) resultEl.innerHTML = '<span class="dot"></span> Scanner actif…';
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

    Quagga.onProcessed(function(result) {
      // Mise à jour en temps réel (facultatif)
      // Peut être utilisé pour des visualisations avancées
    });
  }

  function stopScanner() {
    if (!running) return;
    try {
      Quagga.stop();
    } catch (e) {
      console.warn('Error stopping Quagga:', e);
    }
    running = false;
    if (resultEl) resultEl.innerHTML = '<span class="dot"></span> Scanner arrêté.';
  }

  // Bouton démarrer
  const btnStart = document.getElementById('btn-scanner-start');
  const btnStop  = document.getElementById('btn-scanner-stop');

  if (btnStart) btnStart.addEventListener('click', function(e) {
    e.preventDefault();
    lastCode = ''; // reset pour permettre un nouveau scan
    startScanner();
    this.disabled = true;
    if (btnStop) btnStop.disabled = false;
  });

  if (btnStop) btnStop.addEventListener('click', function(e) {
    e.preventDefault();
    stopScanner();
    if (btnStart) btnStart.disabled = false;
    this.disabled = true;
  });

  // Auto-start si présent dans la page
  const videoEl = document.getElementById(videoId);
  if (videoEl && videoEl.parentElement) {
    // Attendre un peu pour que le DOM soit prêt
    setTimeout(function() {
      startScanner();
      if (btnStart) btnStart.disabled = true;
    }, 300);
  }
}

/**
 * Saisie manuelle du code : efface l'ancien résultat affiché.
 */
function resetScannerDisplay(resultId) {
  const el = document.getElementById(resultId);
  if (el) el.innerHTML = '<span class="dot"></span> En attente de scan…';
}
