function initScanner(videoId, resultId, inputId, onDetected) {
  const resultEl = document.getElementById(resultId);
  const inputEl = document.getElementById(inputId);
  const startBtn = document.getElementById('btn-scanner-start');
  const stopBtn = document.getElementById('btn-scanner-stop');

  let scannerActive = false;
  let lastCode = null;

  function updateResult(message, type = 'info') {
    if (!resultEl) return;
    resultEl.innerHTML = message;
    resultEl.style.color = type === 'error' ? '#f44336' : type === 'success' ? '#4caf50' : '';
  }

  function handleCode(code) {
    if (!code || code === lastCode) return;
    lastCode = code;

    if (inputEl) inputEl.value = code;
    if (typeof onDetected === 'function') onDetected(code);
    updateResult('✔ Code détecté : ' + code, 'success');
  }

  function startScanner() {
    if (scannerActive || typeof Quagga === 'undefined') {
      return;
    }

    updateResult('🔄 Initialisation du scanner...');

    Quagga.init({
      inputStream: {
        type: 'LiveStream',
        target: document.getElementById(videoId),
        constraints: {
          facingMode: 'environment',
        },
      },
      decoder: {
        readers: [
          'code_128_reader',
          'ean_reader',
          'ean_8_reader',
          'code_39_reader',
          'code_39_vin_reader',
          'codabar_reader',
          'upc_reader',
          'upc_e_reader',
          'i2of5_reader',
          '2of5_reader',
          'code_93_reader',
        ],
      },
      locate: true,
    }, function(err) {
      if (err) {
        console.error('Quagga init error', err);
        updateResult('❌ Impossible d\'initialiser le scanner.', 'error');
        return;
      }
      Quagga.start();
      scannerActive = true;
      updateResult('▶ Scanner démarré. Présentez un code-barres ou QR devant la caméra.');
      if (startBtn) startBtn.disabled = true;
      if (stopBtn) stopBtn.disabled = false;
    });

    Quagga.onDetected(function(result) {
      if (!result || !result.codeResult || !result.codeResult.code) return;
      handleCode(result.codeResult.code);
    });
  }

  function stopScanner() {
    if (!scannerActive || typeof Quagga === 'undefined') {
      return;
    }
    Quagga.stop();
    scannerActive = false;
    updateResult('⏹ Scanner arrêté.');
    if (startBtn) startBtn.disabled = false;
    if (stopBtn) stopBtn.disabled = true;
  }

  if (startBtn) startBtn.addEventListener('click', startScanner);
  if (stopBtn) stopBtn.addEventListener('click', stopScanner);
}

function startListeningScanner(inputId, formId, apiPath = '/api/scan.php', clearButtonId = null) {
  const inputEl = document.getElementById(inputId);
  const formEl = document.getElementById(formId);
  const resultEl = document.getElementById('scanner-result');
  const remoteStatusEl = document.getElementById('scanner-mobile-status');
  const clearBtn = clearButtonId ? document.getElementById(clearButtonId) : null;

  if (!inputEl) return;

  const storageKey = 'last-remote-scan-timestamp';
  let lastTimestamp = Number(sessionStorage.getItem(storageKey) || 0);

  function updateRemoteStatus(message, type = 'info') {
    if (!remoteStatusEl) return;
    remoteStatusEl.innerHTML = message;
    remoteStatusEl.style.color = type === 'error' ? '#f44336' : type === 'success' ? '#4caf50' : '#999';
  }

  async function pollScan() {
    try {
      const res = await fetch(apiPath + '?t=' + Date.now());
      if (!res.ok) return;
      const data = await res.json();
      if (data.status !== 'ok' || !data.code || !data.timestamp) return;
      if (data.timestamp <= lastTimestamp) return;

      lastTimestamp = data.timestamp;
      sessionStorage.setItem(storageKey, String(lastTimestamp));

      inputEl.value = data.code;
      updateRemoteStatus('📱 Scan mobile reçu : ' + data.code, 'success');
      if (resultEl) {
        resultEl.innerHTML = '📱 Scan mobile reçu : ' + data.code;
        resultEl.style.color = '#4caf50';
      }

      const qtyEl = formEl ? formEl.querySelector('[name="quantite"]') : null;
      if (qtyEl) qtyEl.value = '1';

      if (formEl) {
        formEl.submit();
      }
    } catch (e) {
      console.log('Remote scan poll error', e);
    }
  }

  async function clearBuffer() {
    try {
      const res = await fetch(`${apiPath}?clear=1`, { method: 'GET' });
      if (!res.ok) throw new Error('Erreur réseau');
      const data = await res.json();
      if (data.status === 'ok') {
        sessionStorage.removeItem(storageKey);
        lastTimestamp = 0;
        updateRemoteStatus('🧹 Buffer mobile vidé.');
        if (resultEl) {
          resultEl.innerHTML = '🧹 Buffer mobile vidé.';
          resultEl.style.color = '#4caf50';
        }
      } else {
        updateRemoteStatus('❌ Impossible de vider le buffer.', 'error');
      }
    } catch (e) {
      console.error('Clear buffer error', e);
      updateRemoteStatus('❌ Erreur lors du vidage du buffer.', 'error');
    }
  }

  if (clearBtn) {
    clearBtn.addEventListener('click', clearBuffer);
  }

  setInterval(pollScan, 1000);
}
