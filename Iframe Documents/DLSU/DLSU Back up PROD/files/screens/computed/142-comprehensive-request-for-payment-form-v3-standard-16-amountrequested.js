if(this.transactionTypeCode != undefined){
    var amount= "-";
    var type = this.transactionTypeCode;
    
    if(type == "LI"){
        if(this.INVOICE_AMOUNT != undefined){
            amount = this.INVOICE_AMOUNT;
        }
    } else if(type == "PCR"){
        if(this.INVOICE_AMOUNT != undefined){
            amount = this.INVOICE_AMOUNT;
        }
    } else if(type == "OTHERS"){
        if(this.INVOICE_AMOUNT != undefined){
            amount = this.INVOICE_AMOUNT;
        }
    } else if(type == "STI"){
        if(this.INVOICE_AMOUNT != undefined){
            amount = this.INVOICE_AMOUNT;
        }
    } else if(type == "CON"){
        if(this.INVOICE_AMOUNT != undefined){
            amount = this.INVOICE_AMOUNT;
        }
    } else if(type == "DON"){
        if(this.INVOICE_AMOUNT != undefined){
            amount = this.INVOICE_AMOUNT;
        }
    } else if(type == "MES"){
        if(this.INVOICE_AMOUNT != undefined){
            amount = this.INVOICE_AMOUNT;
        }
    } else if(type == "RRI"){
        if(this.INVOICE_AMOUNT != undefined){
            amount = this.INVOICE_AMOUNT;
        }
    }
} else {
    amount = 0;
}

var total = amount.toString();

return total;