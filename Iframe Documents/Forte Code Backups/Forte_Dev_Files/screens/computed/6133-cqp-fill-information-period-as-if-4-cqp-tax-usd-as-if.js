/****
 * CQP_TAX_USD (AS IF)
 * By Adriana Centellas
 */

function safeNumber(v) {
  const n = parseFloat(v);
  return Number.isNaN(n) ? 0 : n;
}

return safeNumber(this._parent.CQP_SUMMARY_DETAILS[0].CQP_TAX_USD);