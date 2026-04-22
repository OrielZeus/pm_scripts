if(this.transactionTypeCode != undefined){
    var amount= "-";
    var type = this.transactionTypeCode;
    
    if(type == "HON"){
        if(this.INVOICE_AMOUNT != undefined){
            amount = this.INVOICE_AMOUNT;
        }
    } 
} else {
    amount = 0;
}

var total = amount.toString();

return total;