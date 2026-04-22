var string = "";
var jsonItemsArray = [];
var jsonPayload= null;
var liaDistribution = this.LIABILITY_DISTRIBUTION;

if(this.transactionTypeCode != undefined){
    var type = this.transactionTypeCode;

    if(type == "CA"){ // Cash Advance
        if(this.ECA_remarks != undefined){
            var jsonSummary = {
                ACTIVITY_DATE : this.ECA_activityDate,
                LIQUIDATION_DATE : this.ECA_liquidationDate,
                CURRENCY : this.transactionCurrency,
                AMOUNT : this.ECA_amount,
                REMARKS : this.ECA_remarks
			};
			jsonPayload = {
				SUMMARY : jsonSummary
			};
        }
    } else if(type == "CONTRACTOR"){ // Advances to Contractors
        if(this.CONTRACTOR_remarks != undefined){
            var jsonSummary = {
                ACTIVITY_DATE : this.CONTRACTOR_activityDate,
                CURRENCY : this.transactionCurrency,
                AMOUNT : this.CONTRACTOR_amount,
                REMARKS : this.CONTRACTOR_remarks
			};
			jsonPayload = {
				SUMMARY : jsonSummary
			};
        }
    }	else if(type == "LI"){
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
    } else if(type == "PRP"){
        var sel = this.paymentTypeSelect;
        var RFP = this.RFP_recordList;
    
        if(sel != undefined && RFP != undefined){
            
            var paymentType = sel.content.toUpperCase() + " (";

            if(sel.value == "UT"){
                paymentType = paymentType +  this.utilitiesPayment;   
            } else if(sel.value == "GOVT"){
                paymentType = paymentType +  this.governmentPayment;   
            } else if(sel.value == "COMMS"){
                paymentType = paymentType +  this.telecomsPayment;   
            } 

            paymentType = paymentType + ")";

            for(var i = 0; i < RFP.length; i++){
				var jsonItem = {
					ITEM: (i+1), 
					CHARGE_TO: RFP[i].chargeTo, 
					AMOUNT: RFP[i].amount, 
					LIST_OF_PAYMENTS: RFP[i].listofPayments,
					REMARKS: RFP[i].remarks,
					DISTRIBUTION_COMBINATION: RFP[i].glAccount
					};
				jsonItemsArray.push(jsonItem);
            }
			var jsonSummary = {
                PAYMENT_TYPE : paymentType,
				TOTAL_AMOUNT: this.INVOICE_AMOUNT
			};
			jsonPayload = {
				ITEMS : jsonItemsArray,
				SUMMARY : jsonSummary
			};
        }
    } else if(type == "PCO"){ //opening of Petty Cash Fund
        if(this.PCFS_remarks != undefined){
            var jsonSummary = {
                REMARKS : this.PCFS_remarks
			};
			jsonPayload = {
				SUMMARY : jsonSummary
			};
        }
    } else if(type == "PCC"){ // Closing of Petty Cash Fund
        if(this.PCFS_remarks != undefined){
            var jsonSummary = {
                REMARKS : this.PCFS_remarks
			};
			jsonPayload = {
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
    } else if(type == "RE"){
        var REI = this.REI_recordList;
        var amount = this.INVOICE_AMOUNT;
        var remarks = this.REI_remarks;
    
        if(REI != undefined && remarks != undefined){
            for(var i = 0; i < REI.length; i++){
				var jsonItem = {
					ITEM: (i+1), 
					DATE: REI[i].date, 
					EXPENSES: REI[i].expenses, 
					INVOICE: REI[i].invoice,
					AMOUNT: REI[i].amount,
					DISTRIBUTION_COMBINATION: REI[i].glAccount
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
    } else if(type == "HON"){ //TODO
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
    } else if(type == "BDO1"){ //TODO
        var BDO1 = this.BDO1_recordList;
        var amount = this.INVOICE_AMOUNT;
        var remarks = this.BDO1_remarks;
    
        if(BDO1 != undefined && remarks != undefined){
            for(var i = 0; i < BDO1.length; i++){
				var jsonItem = {
					ITEM: (i+1), 
					DATE: BDO1[i].date, 
					EXPENSES: BDO1[i].expenses, 
					INVOICE: BDO1[i].invoice,
					AMOUNT: BDO1[i].amount,
					DISTRIBUTION_COMBINATION: BDO1[i].glAccount
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
    } else if(type == "BDO2"){ //TODO
        var BDO2 = this.BDO2_recordList;
        var amount = this.INVOICE_AMOUNT;
        var remarks = this.BDO2_remarks;
    
        if(BDO2 != undefined && remarks != undefined){
            for(var i = 0; i < BDO2.length; i++){
				var jsonItem = {
					ITEM: (i+1), 
					DATE: BDO2[i].date, 
					EXPENSES: BDO2[i].expenses, 
					INVOICE: BDO2[i].invoice,
					AMOUNT: BDO2[i].amount,
					DISTRIBUTION_COMBINATION: BDO2[i].glAccount
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
    } else if(type == "BDO3"){ //TODO
        var BDO3 = this.BDO3_recordList;
        var amount = this.INVOICE_AMOUNT;
        var remarks = this.BDO3_remarks;
    
        if(BDO3 != undefined && remarks != undefined){
            for(var i = 0; i < BDO3.length; i++){
				var jsonItem = {
					ITEM: (i+1), 
					DATE: BDO3[i].date, 
					EXPENSES: BDO3[i].expenses, 
					INVOICE: BDO3[i].invoice,
					AMOUNT: BDO3[i].amount,
					DISTRIBUTION_COMBINATION: BDO3[i].glAccount
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
    } else if(type == "BDODOL"){ //TODO
        var BDODOL = this.BDODOL_recordList;
        var amount = this.INVOICE_AMOUNT;
        var remarks = this.BDODOL_remarks;
    
        if(BDODOL != undefined && remarks != undefined){
            for(var i = 0; i < BDODOL.length; i++){
				var jsonItem = {
					ITEM: (i+1), 
					DATE: BDODOL[i].date, 
					EXPENSES: BDODOL[i].expenses, 
					INVOICE: BDODOL[i].invoice,
					AMOUNT: BDODOL[i].amount,
					DISTRIBUTION_COMBINATION: BDODOL[i].glAccount
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
    } else if(type == "INS"){ //TODO
        var INS = this.INS_recordList;
        var amount = this.INVOICE_AMOUNT;
        var remarks = this.INS_remarks;
    
        if(INS != undefined && remarks != undefined){
            for(var i = 0; i < INS.length; i++){
				var jsonItem = {
					ITEM: (i+1), 
					DATE: INS[i].date, 
					EXPENSES: INS[i].expenses, 
					INVOICE: INS[i].invoice,
					AMOUNT: INS[i].amount,
					DISTRIBUTION_COMBINATION: INS[i].glAccount
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
    } else if(type == "BANK"){ //TODO
        var BANK = this.BANK_recordList;
        var amount = this.INVOICE_AMOUNT;
        var remarks = this.BANK_remarks;
    
        if(BANK != undefined && remarks != undefined){
            for(var i = 0; i < BANK.length; i++){
				var jsonItem = {
					ITEM: (i+1), 
					DATE: BANK[i].date, 
					EXPENSES: BANK[i].expenses, 
					INVOICE: BANK[i].invoice,
					AMOUNT: BANK[i].amount,
					DISTRIBUTION_COMBINATION: BANK[i].glAccount
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
    } else if(type == "UNL"){
        if(this.UCA_remarks != undefined){
            var jsonSummary = {
                REMARKS : this.UCA_remarks
			};
			jsonPayload = {
				SUMMARY : jsonSummary
			};
        }
    } else if(type == "PLA"){
        var PLA = this.PLA_recordList;
        var amount = this.INVOICE_AMOUNT;
        var remarks = this.PLA_remarks;
    
        if(PLA != undefined && amount != undefined && remarks != undefined){
            for(var i = 0; i < PLA.length; i++){
				var jsonItem = {
					ITEM: (i+1), 
					DATE: PLA[i].date, 
					EXPENSES: PLA[i].expenses, 
					INVOICE: PLA[i].invoice,
					AMOUNT: PLA[i].amount,
					DISTRIBUTION_COMBINATION: PLA[i].glAccount
					};
				jsonItemsArray.push(jsonItem);
            }
			var jsonSummary = {
                AMOUNT : amount,
				REMARKS : remarks
			};
			jsonPayload = {
				ITEMS : jsonItemsArray,
				SUMMARY : jsonSummary
			}; 
        }
    } else if(type == "STUDREF"){
        var PSR = this.PSR_recordList;
        var amount = this.INVOICE_AMOUNT;
        var remarks = this.PSR_remarks;
        
        if(PSR != undefined && amount != undefined && remarks != undefined){
            for(var i = 0; i < PSR.length; i++){
				var jsonItem = {
					ITEM: (i+1), 
					DATE: PSR[i].date, 
					EXPENSES: PSR[i].expenses, 
					INVOICE: PSR[i].invoice,
					AMOUNT: PSR[i].amount,
					DISTRIBUTION_COMBINATION: PSR[i].glAccount
					};
				jsonItemsArray.push(jsonItem);
            }
			var jsonSummary = {
                AMOUNT : amount,
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
    }
    string = JSON.stringify(jsonPayload);
} else {
    string = "";
}

return string;