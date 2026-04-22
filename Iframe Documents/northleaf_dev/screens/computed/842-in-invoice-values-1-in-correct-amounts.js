function isValidValue(value) {
  // Check for null or undefined
  if (value === null || typeof value === 'undefined') {
    return false;
  }

  // Check for empty string (after trimming whitespace)
  if (typeof value === 'string' && (value.trim() === '' || value.trim() === '0')) {
    return false;
  }

  // Check for the number 0
  if (typeof value === 'number' && value === 0) {
    return false;
  }

  // If none of the above conditions are met, the value is considered valid
  return true;
}
let forceCalcTobeExecuted = this.vendorInformation;
//$("input[name='IN_CORRECT_AMOUNTS_HELPER_FIELD']").parent().hide();
//return (isValidValue(this.IN_INVOICE_TAX_TOTAL) && isValidValue(this.IN_INVOICE_TOTAL)) ? true : false;
return isValidValue(this.IN_INVOICE_TAX_TOTAL) ? true : false;