/**
 * Pre Tax Calculation
 * 
 * by Favio Mollinedo
 */
//let invTtoal    = (this.IN_INVOICE_TOTAL > 0)? this.IN_INVOICE_TOTAL.toFixed(2): 0;
let invTtoal    = this.IN_INVOICE_TOTAL.toFixed(2);
//let invTaxTotal = (this.IN_INVOICE_TAX_TOTAL > 0)?this.IN_INVOICE_TAX_TOTAL.toFixed(2): 0;
let invTaxTotal = (this.IN_INVOICE_TAX_TOTAL == null || this.IN_INVOICE_TAX_TOTAL == '') ? '0.00' : this.IN_INVOICE_TAX_TOTAL;
invTaxTotal = parseFloat(invTaxTotal).toFixed(2)
let preTaxTotal = invTtoal - invTaxTotal;
return preTaxTotal.toLocaleString("en-US", { minimumFractionDigits: 2, maximumFractionDigits: 2 });