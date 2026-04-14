/******
 * return CQP_MLI_USD
 * By Adriana Centellas
 * Edited By Natalia Mendez
 */

function safeNumber(v) {
  const n = parseFloat(v);
  return Number.isNaN(n) ? 0 : n;
}

return safeNumber(this._parent.CQP_TOTAL_FORTE_SHARE);