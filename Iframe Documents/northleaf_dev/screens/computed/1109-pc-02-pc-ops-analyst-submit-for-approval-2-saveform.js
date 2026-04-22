let btnSubmit  = this.SUBMIT_PC;
let btnAction  = this.SUBMIT_02_PC;
let copySubmit = this.copySubmitForm_PC;

let btnSubmitH  = this.SUBMIT_PC_H;
let copySubmitH = this.copySubmit_pc_h;
//btnSubmitH = (btnSubmitH != copySubmitH) ? 'SUBMITH' : null;
/*if(btnSubmitH == 'SUBMIT'){
    let action = this.IN_PCH_ACTION;
    let comments = this.IN_PCH_Comments;
    if(action != null){
        if(action == "Rejected" && (comments == "" || comments == null)){
            return btnSubmitH;
        }
        setTimeout(() => {
            debugger;
            document.querySelectorAll('button[name="SUBMIT_02_PC"]')[3].click();
        }, "500");
        return btnSubmitH;
    }
}*/

btnSubmit = (btnSubmit != copySubmit) ? 'SUBMIT' : null;
if(btnSubmit == 'SUBMIT'){
    let aux = this.APPROVER_LIST_PC.DEFAULT;
    if(aux != null){
        setTimeout(() => {
            document.querySelectorAll('button[name="SUBMIT_02_PC"]')[3].click();
        }, "500");
        return btnSubmit;
    }    
}

/*if(btnAction == 'SAVE'){
    setTimeout(() => {
        let reqsID = this.REQUEST_ID; 
        let dataToUpdate = {
            "data":{
                "APPROVER_LIST_PC" : this.APPROVER_LIST_PC,
                "IN_COMMENT_PC" : this.IN_COMMENT_PC
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
                "APPROVER_LIST_PC" : this.APPROVER_LIST_PC,
                "IN_COMMENT_PC" : this.IN_COMMENT_PC
            }
        };
        ProcessMaker.apiClient.put('requests/'+reqsID, dataToUpdate)
        location.href = "/";
    }, "2500");
}*/
return btnAction;