let dataVendor = this.vendorInformation;
if(dataVendor[0] != undefined && dataVendor[0].EXPENSE_VENDOR_COMPANYCODE != undefined){
    document.getElementById("iframe-psTools").contentWindow.document.querySelector("button[id='btnGetComId']").click();
    return dataVendor[0].EXPENSE_VENDOR_COMPANYCODE;
    
}