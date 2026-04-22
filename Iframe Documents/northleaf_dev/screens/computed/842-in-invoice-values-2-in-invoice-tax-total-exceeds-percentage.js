let amount = this.IN_INVOICE_TOTAL;
let preTaxAmount = this.IN_INVOICE_PRE_TAX;
let percentage = 0.20; // 20%
let result1 = Math.abs(amount) * percentage;
let result2 = Math.abs(preTaxAmount) * percentage;
let msg = "";
let show = false;

if(Math.abs(this.IN_INVOICE_TAX_TOTAL) > result1) {
    show = true;
    msg += "<strong>Invoice Tax (only HST/GST/VAT)</strong> exceeds 20% of the Invoice Total Amount. Please review to ensure input is correct before submitting.<br>";
}
/*
if(Math.abs(this.IN_INVOICE_TAX_TOTAL) > result2) {
    show = true;
    msg += "<strong>Invoice Tax (only HST/GST/VAT)</strong> exceeds 25% of the Invoice Pre Tax Amount. Please review to ensure input is correct before submitting.";
}
*/
return {
    "show": show,
    "msg": msg
};