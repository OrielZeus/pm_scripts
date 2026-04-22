let mandateAmount = this.PE_MANDATE_AMOUNT;
let mandateActual = this.PE_MANDATE_ACTUAL_ALLOCATION;
let alertMessage = '';
let percent = 0.5;
let finalMandateAmount = mandateAmount + (mandateAmount * (percent/100)); 
if (mandateActual > finalMandateAmount) {
    alertMessage = 'Cannot be more than ' + percent + '% above '+ this.PE_MANDATE_AMOUNT.toLocaleString();
}
return alertMessage;