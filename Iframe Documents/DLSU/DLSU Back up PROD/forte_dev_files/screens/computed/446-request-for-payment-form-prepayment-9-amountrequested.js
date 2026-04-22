if(this.transactionTypeCode != undefined){
    var amount= "-";
    var type = this.transactionTypeCode;
    
    if(type == "CA"){
        amount = this.ECA_amount;
    }
    if(type == "AED"){
        amount = this.INVOICE_AMOUNT;
    }
    if(type == "CTRA"){
        amount = this.ECA_amount;
    }
    if(type == "CRCA"){
        amount = this.ECA_amount;
    }
} else {
    amount = 0;
}

var total = amount.toString();

return total;