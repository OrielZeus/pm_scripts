let totalPreTax = this.totalPreTaxAmount ?? 0;
let totalHst = this.totalHst ?? 0;
return parseFloat(totalPreTax) + parseFloat(totalHst);