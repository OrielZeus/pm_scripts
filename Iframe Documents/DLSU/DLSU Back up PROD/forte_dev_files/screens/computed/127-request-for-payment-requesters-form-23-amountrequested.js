if(this.transactionTypeCode != undefined){
    var amount= "-";
    var type = this.transactionTypeCode;
    
    if(type == "CA"){
        if(this.ECA_amount != undefined){
            amount = this.ECA_amount;
        }
    }
    else if(type == "LI"){
        if(this.INVOICE_AMOUNT != undefined){
            amount = this.INVOICE_AMOUNT;
        }
    } else if(type == "PRP"){
        if(this.INVOICE_AMOUNT != undefined){
            amount = this.INVOICE_AMOUNT;
        }
    } else if(type == "PCR"){
        if(this.INVOICE_AMOUNT != undefined){
            amount = this.INVOICE_AMOUNT;
        }
    } else if(type == "RE"){
        if(this.INVOICE_AMOUNT != undefined){
            amount = this.INVOICE_AMOUNT;
        }
    } else if(type == "PLA"){
        if(this.INVOICE_AMOUNT != undefined){
            amount = this.INVOICE_AMOUNT;
        }
    } else if(type == "STUDREF"){
        if(this.INVOICE_AMOUNT != undefined){
            amount = this.INVOICE_AMOUNT;
        }
    } else if(type == "OTHERS"){
        if(this.INVOICE_AMOUNT != undefined){
            amount = this.INVOICE_AMOUNT;
        }
    } else if(type == "HON"){
        if(this.INVOICE_AMOUNT != undefined){
            amount = this.INVOICE_AMOUNT;
        }
    } else if(type == "BDO1"){
        if(this.INVOICE_AMOUNT != undefined){
            amount = this.INVOICE_AMOUNT;
        }
    } else if(type == "BDO2"){
        if(this.INVOICE_AMOUNT != undefined){
            amount = this.INVOICE_AMOUNT;
        }
    } else if(type == "BDO3"){
        if(this.INVOICE_AMOUNT != undefined){
            amount = this.INVOICE_AMOUNT;
        }
    } else if(type == "BDODOL"){
        if(this.INVOICE_AMOUNT != undefined){
            amount = this.INVOICE_AMOUNT;
        }
    } else if(type == "STI"){
        if(this.INVOICE_AMOUNT != undefined){
            amount = this.INVOICE_AMOUNT;
        }
    } else if(type == "INS"){
        if(this.INVOICE_AMOUNT != undefined){
            amount = this.INVOICE_AMOUNT;
        }
    } else if(type == "CON"){
        if(this.INVOICE_AMOUNT != undefined){
            amount = this.INVOICE_AMOUNT;
        }
    } else if(type == "BANK"){
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
    }
} else {
    amount = 0;
}

var total = amount.toString();

return total;