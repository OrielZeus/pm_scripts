let btnSubmit = this.SUBMIT_CO_FLAG;
let btnAction  = this.SUBMIT_CO;
let copySubmit = this.copySubmitForm_CO;
let btnSubmitR = this.SUBMIT_COH;
let copySubmitR = this.copySubmitForm_COH;
btnSubmitR = (btnSubmitR != copySubmitR) ? 'SUBMIT' : null;
btnSubmit = (btnSubmit != copySubmit) ? 'SUBMIT' : null;
if(btnSubmit == 'SUBMIT'){
    //let appList  = this.APPROVER_LIST_CO.DEFAULT;
    let fxAction = this.vendorInformation.IN_FX_REQUIRED;
    let venLoc   = this.vendorInformation.vendorLocation;
    if(venLoc != null && (fxAction == "Yes" || fxAction == "No")){
        setTimeout(() => {
            document.querySelectorAll('button[name="SUBMIT_CO"]')[2].click();
        }, "2500");
        btnAction = 'SUBMITR';
    }    
}

/*if(btnAction == 'SAVE'){
        let reqsID = this.REQUEST_ID; 
        let dataToUpdate = {
            "data":{
                "IN_INVOICE_VENDOR" : this.IN_INVOICE_VENDOR,
                "vendorInformation" :this.vendorInformation,
                "IN_INVOICE_TRANS_COMMENTS" :this.IN_INVOICE_TRANS_COMMENTS,
                //"APPROVER_LIST_CO" : this.APPROVER_LIST_CO,
                "IN_COMMENT_CO" : this.IN_COMMENT_CO
            }
        };
        ProcessMaker.apiClient.put('requests/'+reqsID, dataToUpdate)
        setTimeout(() => {
            location.reload();
        }, "5500");
}
if(btnAction == 'SAVE_CLOSE'){
    setTimeout(() => {
        let reqsID = this.REQUEST_ID; 
        let dataToUpdate = {
            "data":{
                "IN_INVOICE_VENDOR" : this.IN_INVOICE_VENDOR,
                "vendorInformation" :this.vendorInformation,
                "IN_INVOICE_TRANS_COMMENTS" :this.IN_INVOICE_TRANS_COMMENTS,
                //"APPROVER_LIST_CO" : this.APPROVER_LIST_CO,
                "IN_COMMENT_CO" : this.IN_COMMENT_CO
            }
        };
        ProcessMaker.apiClient.put('requests/'+reqsID, dataToUpdate)
        location.href = "/";
    }, "2500");
}*/
if(btnSubmitR == 'SUBMIT'){
    let fxAction = this.vendorInformation.IN_FX_REQUIRED;
    let action   = this.IN_SUBMITTER_COH;
    let venLoc   = this.vendorInformation.vendorLocation;
    if(venLoc != null && action != null && (fxAction == "Yes" || fxAction == "No")){
        if(action == 'Rejected' && (this.IN_COMMENT_COH == null || this.IN_COMMENT_COH == '')){
            return btnSubmitR;    
        }
        setTimeout(() => {
            document.querySelectorAll('button[name="SUBMIT_CO"]')[2].click();
        }, "2500");
        btnAction = 'SUBMITR';
    }
}
return btnAction;