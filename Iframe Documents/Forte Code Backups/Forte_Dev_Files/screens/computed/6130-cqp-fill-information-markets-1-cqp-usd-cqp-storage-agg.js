/*****
 * '= CQP_USD / CQP_STORAGE_AGG
 * By Adriana Centellas
 */

function safeNumber(v) {
  const n = parseFloat(v);
  return Number.isNaN(n) ? 0 : n;
}

// Get all possible amounts
const values = [
  this._parent.CQP_STORAGE_EXPOSURE?.[0]?.CQP_STORAGE_AGG,
  this._parent.CQP_STORAGE_EXPOSURE?.[0]?.CQP_STORAGE_EEL,
  this._parent.CQP_TRANSIT_EXPOSURE?.[0]?.CQP_MLI_USD,
  this._parent.CQP_TRANSIT_EXPOSURE?.[0]?.CQP_MLE_USD,
  this._parent.CQP_TRANSIT_EXPOSURE?.[0]?.CQP_MLD_USD
];

// Convert safely to numbers
const numericValues = values.map(function(v){
  return safeNumber(v);
});

// Get the largest value
const amount = Math.max.apply(null, numericValues);

// Apply distribution
return (safeNumber(this.CQP_USD) / amount) * 100;