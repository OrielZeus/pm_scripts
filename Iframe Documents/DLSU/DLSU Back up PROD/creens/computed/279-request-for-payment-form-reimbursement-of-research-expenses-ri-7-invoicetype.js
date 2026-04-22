var string = "";

if(this.transactionTypeSelect != undefined){
    var code = this.transactionTypeCode;
    if(code == "CA" || code == "PCO" || code == "PCC" || code == "UNL" || code == "CONTRACTOR"){
        string = "PREPAYMENT";
    } else {
        string = "STANDARD";
    }
}

return string;