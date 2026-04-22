var string = "";
var jsonItemsArray = [];
var jsonPayload= null;
var liaDistribution = this.LIABILITY_DISTRIBUTION;

if(this.transactionTypeCode != undefined){
    var type = this.transactionTypeCode;

    if(type == "LI"){
        var LCA = this.LCA_recordList;
        var car = this.LCA_cashAdvanceRequestNo;
        var ld = this.LCA_liquidationDate;
        var al = this.INVOICE_AMOUNT;
        var ar = this.LCA_amountForReimbursement;
    
        if(LCA != undefined && car != undefined && ld != undefined && ar !=undefined){
            for(var i = 0; i < LCA.length; i++){
				var jsonItem = {
					ITEM: (i+1), 
					DATE: LCA[i].date, 
					EXPENSES: LCA[i].expenses, 
					INVOICE: LCA[i].invoice,
					AMOUNT: LCA[i].amount,
					DISTRIBUTION_COMBINATION: LCA[i].glAccount
					};
				jsonItemsArray.push(jsonItem);
            }
			var jsonSummary = {
                CASH_ADVANCE_REQUEST_NO : car,
				DATE_OF_LIQUIDATION : ld,
				TOTAL_AMOUNT_LIQUIDATED: al,
                AMOUNT_FOR_REIMBURSEMENT : ar
			};
			jsonPayload = {
				ITEMS : jsonItemsArray,
				SUMMARY : jsonSummary
			};
        }
    } else if(type == "PCR"){
        var PCR = this.PCR_recordList;
        var fund = this.PCR_fund;
        //var exp = this.PCR_expenses;
    
        if(PCR != undefined && fund != undefined){
            for(var i = 0; i < PCR.length; i++){
				var jsonItem = {
					ITEM: (i+1), 
					DATE: PCR[i].date, 
					EXPENSES: PCR[i].expenses, 
					INVOICE: PCR[i].invoice,
					AMOUNT: PCR[i].amount,
					DISTRIBUTION_COMBINATION: PCR[i].glAccount
					};
				jsonItemsArray.push(jsonItem);
            }
			var jsonSummary = {
                PETTY_CASH_FUND : fund,
				//SUMMARY_OF_EXPENSES : exp,
				PETTY_CASH_ON_HAND: this.INVOICE_AMOUNT,
                CASH_OVERAGE : this.PCR_overage
			};
			jsonPayload = {
				ITEMS : jsonItemsArray,
				SUMMARY : jsonSummary
			}; 
        }
    } else if(type == "CON"){ //TODO
        var CON = this.CON_recordList;
        var amount = this.INVOICE_AMOUNT;
        var remarks = this.CON_remarks;
    
        if(CON != undefined && remarks != undefined){
            for(var i = 0; i < CON.length; i++){
				var jsonItem = {
					ITEM: (i+1), 
					DATE: CON[i].date, 
					EXPENSES: CON[i].expenses, 
					INVOICE: CON[i].invoice,
					AMOUNT: CON[i].amount,
					DISTRIBUTION_COMBINATION: CON[i].glAccount
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
    } else if(type == "DON"){ //TODO
        var DON = this.DON_recordList;
        var amount = this.INVOICE_AMOUNT;
        var remarks = this.DON_remarks;
    
        if(DON != undefined && remarks != undefined){
            for(var i = 0; i < DON.length; i++){
				var jsonItem = {
					ITEM: (i+1), 
					DATE: DON[i].date, 
					EXPENSES: DON[i].expenses, 
					INVOICE: DON[i].invoice,
					AMOUNT: DON[i].amount,
					DISTRIBUTION_COMBINATION: DON[i].glAccount
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
    } else if(type == "MES"){ //TODO
        var MES = this.MES_recordList;
        var amount = this.INVOICE_AMOUNT;
        var remarks = this.MES_remarks;
    
        if(MES != undefined && remarks != undefined){
            for(var i = 0; i < MES.length; i++){
				var jsonItem = {
					ITEM: (i+1), 
					DATE: MES[i].date, 
					EXPENSES: MES[i].expenses, 
					INVOICE: MES[i].invoice,
					AMOUNT: MES[i].amount,
					DISTRIBUTION_COMBINATION: MES[i].glAccount
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
    } else if(type == "OTHERS"){
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
    } else if(type == "STI"){ //TODO
        var STI = this.STI_recordList;
        var amount = this.INVOICE_AMOUNT;
        var remarks = this.STI_remarks;
    
        if(STI != undefined && remarks != undefined){
            for(var i = 0; i < STI.length; i++){
				var jsonItem = {
					ITEM: (i+1), 
					DATE: STI[i].date, 
					EXPENSES: STI[i].expenses, 
					INVOICE: STI[i].invoice,
					AMOUNT: STI[i].amount,
					DISTRIBUTION_COMBINATION: STI[i].glAccount
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
    } else if(type == "RRI"){
        var RRI = this.RRI_recordList;
        var amount = this.INVOICE_AMOUNT;
        var remarks = this.RRI_remarks;
    
        if(RRI != undefined && remarks != undefined){
            for(var i = 0; i < RRI.length; i++){
				var jsonItem = {
					ITEM: (i+1), 
					DATE: RRI[i].date, 
					EXPENSES: RRI[i].expenses, 
					INVOICE: RRI[i].invoice,
					AMOUNT: RRI[i].amount,
					DISTRIBUTION_COMBINATION: RRI[i].glAccount
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