var bool = false;

if(this.edscmApproval == "true"){
    bool = true;
} else if(this.edscmApproval == "false" && this.edscmRemarks != undefined){
    bool = true;
}

return bool;