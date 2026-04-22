var string = "";
var jsonItemsArray = [];
var jsonPayload= null;
var liaDistribution = this.LIABILITY_DISTRIBUTION;

if(this.transactionTypeCode != undefined){
    var type = this.transactionTypeCode;

    if(type == "HON"){ //TODO
        var HON = this.HON_recordList;
        var amount = this.INVOICE_AMOUNT;
        var remarks = this.HON_remarks;
    
        if(HON != undefined && remarks != undefined){
            for(var i = 0; i < HON.length; i++){
				var jsonItem = {
					ITEM: (i+1), 
					DATE: HON[i].date, 
					EXPENSES: HON[i].expenses, 
					INVOICE: HON[i].invoice,
					AMOUNT: HON[i].amount,
					DISTRIBUTION_COMBINATION: HON[i].glAccount
					};
				jsonItemsArray.push(jsonItem);
            }
			var jsonSummary = {
                TOTAL_AMOUNT : amount,
				REMARKS : remarks
			};
			jsonPayload = {
				ITEMS : jsonItemsArray,
				SUMMARY : jsonSummary
			}; 
        }
    } 
    string = JSON.stringify(jsonPayload);
} else {
    string = "";
}

return string;