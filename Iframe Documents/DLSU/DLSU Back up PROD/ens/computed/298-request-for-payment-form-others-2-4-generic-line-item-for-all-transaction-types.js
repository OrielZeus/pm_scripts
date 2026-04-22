var string = "";
var jsonItemsArray = [];
var jsonPayload= null;
var liaDistribution = this.LIABILITY_DISTRIBUTION;

if(this.transactionTypeCode != undefined){
    var type = this.transactionTypeCode;

    if(type == "OTHERS"){
        var RFO = this.RFO_recordList;
        var amount = this.INVOICE_AMOUNT;
        var remarks = this.RFO_remarks;

        if(RFO != undefined && remarks != undefined){
            for(var i = 0; i < RFO.length; i++){
				var jsonItem = {
					ITEM: (i+1), 
					DATE: RFO[i].date, 
					EXPENSES: RFO[i].expenses, 
					INVOICE: RFO[i].invoice,
					AMOUNT: RFO[i].amount,
					DISTRIBUTION_COMBINATION: RFO[i].glAccount
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