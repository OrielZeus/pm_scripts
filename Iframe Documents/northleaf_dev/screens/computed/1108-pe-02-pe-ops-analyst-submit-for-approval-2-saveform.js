let btnSubmit  = this.SUBMIT_PE;
let btnAction  = this.SUBMIT_02_PE;
let copySubmit = this.copySubmitForm_PE;
let btnSubmitH  = this.SUBMIT_PE_H;
let copySubmitH = this.copySubmit_pe_h;
//btnSubmitH = (btnSubmitH != copySubmitH) ? 'SUBMITH' : null;
/*if(btnSubmitH == 'SUBMIT'){
    let action = this.IN_PEH_ACTION;
    let comments = this.IN_PEH_Comments;
    if(action != null){
        if(action == "Rejected" && (comments == "" || comments == null)){
            return btnSubmitH;
        }
        setTimeout(() => {
            document.querySelectorAll('button[name="SUBMIT_02_PE"]')[3].click();
        }, "200");
        return btnSubmitH;
    }
}*/


btnSubmit = (btnSubmit != copySubmit) ? 'SUBMIT' : null;
if(btnSubmit == 'SUBMIT'){
    let aux = this.APPROVER_LIST_PE.DEFAULT;
    if(aux != null){
        setTimeout(() => {
            document.querySelectorAll('button[name="SUBMIT_02_PE"]')[3].click();
        }, "200");
        return btnSubmit;
    }    
}

/*if(btnAction == 'SAVE'){
    setTimeout(() => {
        let reqsID = this.REQUEST_ID; 
        let dataToUpdate = {
            "data":{
                "APPROVER_LIST_PE" : this.APPROVER_LIST_PE,
                "IN_COMMENT_PE" : this.IN_COMMENT_PE
            }
        };
        ProcessMaker.apiClient.put('requests/'+reqsID, dataToUpdate)
        location.reload();
    }, "2500");
}
if(btnAction == 'SAVE_CLOSE'){
    setTimeout(() => {
        let reqsID = this.REQUEST_ID; 
        let dataToUpdate = {
            "data":{
                "APPROVER_LIST_PE" : this.APPROVER_LIST_PE,
                "IN_COMMENT_PE" : this.IN_COMMENT_PE
            }
        };
        ProcessMaker.apiClient.put('requests/'+reqsID, dataToUpdate)
        location.href = "/";
    }, "2500");
}*/

return btnAction;