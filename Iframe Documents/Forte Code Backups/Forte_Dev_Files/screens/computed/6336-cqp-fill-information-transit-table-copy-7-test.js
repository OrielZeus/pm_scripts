(function () {

  // 🔹 Selector del contenedor
  const containerSelector = 'div[selector="DATAMASK"]';

  /**
   * Limpia cualquier carácter no numérico
   * Permite solo números y un punto decimal
   * Aplica separador de miles con coma
   */
  function sanitizeAndFormat(value) {
    if (!value) return '';

    let v = String(value)
      .replace(/[^0-9.]/g, '') // ❌ elimina letras, %, símbolos
      .replace(/,/g, '');

    if (!v || isNaN(v)) return '';

    const parts = v.split('.');
    let intPart = parts[0];
    let decPart = parts.slice(1).join(''); // evita múltiples puntos

    intPart = intPart.replace(/\B(?=(\d{3})+(?!\d))/g, ',');

    return decPart ? `${intPart}.${decPart}` : intPart;
  }

  /**
   * Bloquea teclas no numéricas
   */
  function blockNonNumericKeys(input) {
    input.addEventListener('keydown', (e) => {
      const allowedKeys = [
        'Backspace',
        'Delete',
        'Tab',
        'ArrowLeft',
        'ArrowRight',
        'Home',
        'End'
      ];

      // permitir Ctrl / Cmd + C, V, X, A
      if (e.ctrlKey || e.metaKey) return;

      if (
        allowedKeys.includes(e.key) ||
        /^[0-9.]$/.test(e.key)
      ) {
        return;
      }

      e.preventDefault();
    });
  }

  /**
   * Aplica máscara solo una vez por input
   */
  function applyMask(input) {
    if (input.dataset.maskAttached) return;
    input.dataset.maskAttached = 'true';

    blockNonNumericKeys(input);

    input.addEventListener('input', (e) => {
      e.target.value = sanitizeAndFormat(e.target.value);
    });
  }

  /**
   * Escanea inputs dentro del contenedor
   * Aplica formato incluso a readonly
   */
  function scanAndFormatAll() {
    const container = document.querySelector(containerSelector);
    if (!container) return;

    const inputs = container.querySelectorAll('input[type="text"]');

    inputs.forEach(input => {
      // aplicar listeners solo si es editable
      if (!input.readOnly && !input.disabled) {
        applyMask(input);
      }

      // aplicar formato siempre
      if (input.value) {
        input.value = sanitizeAndFormat(input.value);
      }
    });
  }

  /**
   * Observa cambios dinámicos del DOM
   */
  const observer = new MutationObserver(scanAndFormatAll);

  observer.observe(document.body, {
    childList: true,
    subtree: true,
    attributes: true,
    attributeFilter: ['value']
  });

  // ejecutar al cargar
  scanAndFormatAll();

})();