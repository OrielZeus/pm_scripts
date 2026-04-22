let currentLoc = (this.vendorInformation != null && this.vendorInformation.vendorLocation) ? this.vendorInformation.vendorLocation : '';
let currentFxR = (this.vendorInformation.IN_FX_REQUIRED != null && this.vendorInformation.IN_FX_REQUIRED != undefined) ? this.vendorInformation.IN_FX_REQUIRED : '';

return this.IN_INVOICE_VENDOR_ID + '-' + currentLoc + '-' + currentFxR;