var bool;

if(this.PCR_fund != undefined && this.INVOICE_AMOUNT != undefined){
    if(parseFloat(this.PCR_fund) >= parseFloat(this.INVOICE_AMOUNT)){
        bool = false;
    } else {
        bool = true;
    }
}

return bool;