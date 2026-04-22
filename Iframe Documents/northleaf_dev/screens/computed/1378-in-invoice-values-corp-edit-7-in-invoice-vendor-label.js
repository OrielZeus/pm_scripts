/*
* Calc to get the IN_INVOICE_VENDOR selected label
* By Adriana Centellas
*/

const vendor = this.PM_VENDOR_SOURCE.find(item => item.ID === this.IN_INVOICE_VENDOR);

const label = vendor ? vendor.LABEL : "";

return label;