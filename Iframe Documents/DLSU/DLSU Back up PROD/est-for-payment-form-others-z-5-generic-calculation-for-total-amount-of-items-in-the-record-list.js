if(this.transactionTypeCode != undefined){
    var amount= 0;
    var type = this.transactionTypeCode;
    if(type == "OTHERS"){
        if(this.RFO_recordList != undefined){
            for(var i = 0; i < this.RFO_recordList.length; i++){
                amount += parseFloat(this.RFO_recordList[i].amount);
            }
        }
    } 
} else {
    amount = 0;
}

var total = amount.toFixed(2);

return total;