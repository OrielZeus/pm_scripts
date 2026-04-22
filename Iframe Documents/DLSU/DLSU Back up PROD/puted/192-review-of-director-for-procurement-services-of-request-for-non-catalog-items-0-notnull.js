var bool = false;

if(this.dpsApproval == "true"){
    bool = true;
}else if(this.dpsApproval == "false" && this.dpsRemarks != undefined){
    bool = true;   
}

return bool;