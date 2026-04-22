/*
 *
 */

// let totalPreTax = this.totalPreTaxAmount ?? 0;
// let totalHst = this.totalHst ?? 0;
// return parseFloat(totalPreTax) + parseFloat(totalHst);

let IN_INVOICE_PRE_TAX = this.IN_INVOICE_PRE_TAX ?? 0;
let IN_INVOICE_TAX_TOTAL = this.IN_INVOICE_TAX_TOTAL ?? 0;

return cleanNumber(IN_INVOICE_PRE_TAX) + cleanNumber(IN_INVOICE_TAX_TOTAL);

function cleanNumber(value) {
    if (typeof value === 'string') {
        return parseFloat(value.replace(/,/g, ''));
    }
    return parseFloat(value) || 0;
}