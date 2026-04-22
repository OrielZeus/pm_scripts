var total = "-";

if(parseFloat(this.PCR_fund) >= parseFloat(this.INVOICE_AMOUNT)){
    var overage = parseFloat("0");
    overage = parseFloat(this.PCR_fund - this.INVOICE_AMOUNT);
    var total = overage.toFixed(2);
} 

return total;