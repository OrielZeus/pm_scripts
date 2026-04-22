if(this.transactionTypeCode != undefined){
    var amount= 0;
    var type = this.transactionTypeCode;
    if(this.ITEM_recordList != undefined){
        for(var i = 0; i < this.ITEM_recordList.length; i++){
            amount += parseFloat(this.ITEM_recordList[i].amount);
        }
    }
} else {
    amount = 0;
}
var total = amount.toString();
return total;