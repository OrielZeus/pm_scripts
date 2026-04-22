//return this.IN_INVOICE_VENDOR_LABEL+  ' - ' + this.IN_INVOICE_NUMBER + ' - ' + this.IN_INVOICE_DATE;
let tempVendor = this.IN_INVOICE_VENDOR_LABEL.replace("|", " |");
let caseNumber = (this._request.case_number != undefined) ? this._request.case_number : '';
return caseNumber + ' - ' + tempVendor +  ' - ' + this.IN_INVOICE_NUMBER + ' - ' + this.IN_INVOICE_DATE;