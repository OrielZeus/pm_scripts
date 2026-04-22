/*****************************************
 * Calculate Percentage of Mandate Amount
 * 
 * by Cinthia Romero
 ****************************************/
var initialAmount = this._parent.PE_MANDATE_INITIAL_TOTAL_AMOUNT;
var mandateAmount = this.PE_MANDATE_AMOUNT;
var percentageValue = 0;
if (initialAmount != "" && initialAmount != undefined && initialAmount != null && initialAmount != 0 && mandateAmount != "" && mandateAmount != undefined && mandateAmount != null) {
    percentageValue = (mandateAmount * 100) / initialAmount;
}
return percentageValue;