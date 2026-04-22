var string = "";

if(this.transactionTypeSelect != undefined){
    var code = this.transactionTypeCode;
    if(code == "CA" || code == "PCO" || code == "PCC" || code == "UNL"){
        string = "PREPAYMENT";
    } else {
        string = "STANDARD";
    }
}

return string;