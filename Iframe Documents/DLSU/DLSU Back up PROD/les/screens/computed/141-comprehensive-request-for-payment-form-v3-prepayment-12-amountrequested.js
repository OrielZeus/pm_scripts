if(this.transactionTypeCode != undefined){
    var amount= "-";
    var type = this.transactionTypeCode;
    
    if(type == "CA"){
        amount = this.ECA_amount;
    }
    if(type == "UNL"){
        amount = this.UCA_amount;
    }
} else {
    amount = 0;
}

var total = amount.toString();

return total;