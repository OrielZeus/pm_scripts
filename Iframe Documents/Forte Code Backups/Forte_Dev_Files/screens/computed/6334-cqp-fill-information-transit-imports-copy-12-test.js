/***
 *  View and mask all numeric field values to ##,###.##
 *  By Adriana Centellas
 */

(function () {

  /**
   * CSS selector for the container that holds the numeric inputs.
   */
  const containerSelector = 'div[selector="DATAMASK_TEST"]';

  /**
   * Sanitizes and formats a numeric input value.
   *
   * Rules:
   * - Allows only digits, a single decimal point, and an optional leading minus sign
   * - Ensures only one decimal point is kept
   * - Applies thousand separators to the integer part
   * - Does NOT force decimal places while typing
   *
   * @param {string|number} value - Raw input value
   * @returns {string} Formatted numeric string
   */
  function sanitizeAndFormat(value) {
    if (!value) return '';

    let v = String(value);

    // Detect negative numbers
    const isNegative = v.startsWith('-');

    // Remove all characters except digits and decimal point
    v = v.replace(/[^0-9.]/g, '');

    // Ensure only one decimal point exists
    const firstDotIndex = v.indexOf('.');
    if (firstDotIndex !== -1) {
      v =
        v.substring(0, firstDotIndex + 1) +
        v.substring(firstDotIndex + 1).replace(/\./g, '');
    }

    // Split integer and decimal parts
    const parts = v.split('.');
    let intPart = parts[0];
    let decPart = parts[1];

    // Apply thousand separators
    intPart = intPart.replace(/\B(?=(\d{3})+(?!\d))/g, ',');

    const result = decPart !== undefined
      ? `${intPart}.${decPart}`
      : intPart;

    return isNegative && result ? `-${result}` : result;
  }

  /**
   * Adds ".00" ONLY when the value is an integer.
   * If the value already has decimals, it is left untouched.
   *
   * @param {string} value - Formatted numeric string
   * @returns {string}
   */
  function forceTwoDecimalsIfInteger(value) {
    if (!value) return '';

    // If it already has decimals, do nothing
    if (value.includes('.')) return value;

    // Remove thousand separators for numeric check
    const numericValue = value.replace(/,/g, '');

    if (isNaN(numericValue)) return value;

    return `${value}.00`;
  }

  /**
   * Prevents invalid characters from being typed into the input.
   *
   * Allowed:
   * - Digits (0–9)
   * - Decimal point (.)
   * - Minus sign (-), only at the first position and only once
   * - Navigation and control keys
   *
   * @param {HTMLInputElement} input
   */
  function blockNonNumericKeys(input) {
    input.addEventListener('keydown', (e) => {
      const allowedControlKeys = [
        'Backspace',
        'Delete',
        'Tab',
        'ArrowLeft',
        'ArrowRight',
        'Home',
        'End'
      ];

      // Allow copy/paste/select shortcuts
      if (e.ctrlKey || e.metaKey) return;

      if (/^[0-9]$/.test(e.key)) return;
      if (e.key === '.') return;

      // Allow minus sign only once and only at the beginning
      if (
        e.key === '-' &&
        input.selectionStart === 0 &&
        !input.value.includes('-')
      ) {
        return;
      }

      if (allowedControlKeys.includes(e.key)) return;

      e.preventDefault();
    });
  }

  /**
   * Attaches masking and formatting logic to an input element.
   *
   * @param {HTMLInputElement} input
   */
  function applyMask(input) {
    if (input.dataset.maskAttached) return;
    input.dataset.maskAttached = 'true';

    blockNonNumericKeys(input);

    // Sanitize and format while typing
    input.addEventListener('input', (e) => {
      e.target.value = sanitizeAndFormat(e.target.value);
    });

    // ✅ Add .00 ONLY if the value is an integer
    input.addEventListener('blur', (e) => {
      e.target.value = forceTwoDecimalsIfInteger(e.target.value);
    });
  }

  /**
   * Scans and processes all inputs inside the container.
   */
  function scanAndFormatAll() {
    const container = document.querySelector(containerSelector);
    if (!container) return;

    const inputs = container.querySelectorAll('input[type="text"]');

    inputs.forEach(input => {
      if (!input.readOnly && !input.disabled) {
        applyMask(input);
      }

      if (input.value) {
        input.value = sanitizeAndFormat(input.value);
      }
    });
  }

  /**
   * Observe DOM changes to support dynamically generated inputs.
   */
  const observer = new MutationObserver(scanAndFormatAll);

  observer.observe(document.body, {
    childList: true,
    subtree: true,
    attributes: true,
    attributeFilter: ['value']
  });

  // Initial execution
  scanAndFormatAll();

})();