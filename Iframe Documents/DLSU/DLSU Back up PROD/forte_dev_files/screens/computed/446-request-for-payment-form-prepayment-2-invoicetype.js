var string = "";

if(this.transactionTypeSelect != undefined){
    var code = this.transactionTypeCode;
    if(code == "CA" || code == "CTRA" || code == "CRCA" || code == "AED" || code == "CONTRACTOR"){
        string = "PREPAYMENT";
    } else {
        string = "STANDARD";
    }
}

return string;