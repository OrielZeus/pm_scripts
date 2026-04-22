if(this.transactionTypeCode != undefined){
    var amount= 0;
    var type = this.transactionTypeCode;
    if(type == "HON"){
        if(this.HON_recordList != undefined){
            for(var i = 0; i < this.HON_recordList.length; i++){
                amount += parseFloat(this.HON_recordList[i].amount);
            }
        }
    } 
} else {
    amount = 0;
}

var total = amount.toFixed(2);

return total;