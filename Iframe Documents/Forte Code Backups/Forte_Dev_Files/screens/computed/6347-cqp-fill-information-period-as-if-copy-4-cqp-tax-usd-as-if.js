/****
 * CQP_TAX_USD (AS IF)
 * By Adriana Centellas
 */

function safeNumber(v) {
  if (typeof v !== "string" && typeof v !== "number") return 0;

  const normalized = String(v).replace(/,/g, "");
  const n = Number(normalized);

  return Number.isNaN(n) ? 0 : n;
}

return safeNumber(this._parent.CQP_SUMMARY_DETAILS[0].CQP_TAX_USD);