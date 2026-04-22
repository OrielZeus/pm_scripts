let btnSubmit = this.SUBMIT_CO_FLAG;
let btnAction  = this.SUBMIT_CO;
let copySubmit = this.copySubmitForm_CO;

let btnSubmitR = this.SUBMIT_COH;
let copySubmitR = this.copySubmitForm_COH;
btnSubmitR = (btnSubmitR != copySubmitR) ? 'SUBMIT' : null;

btnSubmit = (btnSubmit != copySubmit) ? 'SUBMIT' : null;
if(btnSubmit == 'SUBMIT'){
    let aux = this.APPROVER_LIST_CO.DEFAULT;
    let fxAction = this.IN_FX_REQUIRED;
    if(aux != null && (fxAction == "Yes" || fxAction == "No")){
        setTimeout(() => {
            document.querySelectorAll('button[name="SUBMIT_CO"]')[2].click();
        }, "2500");
        return btnSubmit;
    }    
}

if(btnAction == 'SAVE'){
        let reqsID = this.REQUEST_ID; 
        let dataToUpdate = {
            "data":{
                "APPROVER_LIST_CO" : this.APPROVER_LIST_CO,
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
                "APPROVER_LIST_CO" : this.APPROVER_LIST_CO,
                "IN_COMMENT_CO" : this.IN_COMMENT_CO
            }
        };
        ProcessMaker.apiClient.put('requests/'+reqsID, dataToUpdate)
        location.href = "/";
    }, "2500");
}
if(btnSubmitR == 'SUBMIT'){
    let fxAction = this.IN_FX_REQUIRED;
    if(this.IN_SUBMITTER_COH != null && (fxAction == "Yes" || fxAction == "No")){
        setTimeout(() => {
            document.querySelectorAll('button[name="SUBMIT_CO"]')[2].click();
        }, "2500");
        return btnSubmit;
    }
}
return btnAction;