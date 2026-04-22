var bool = false;

if(this.aagaApproval == "true"){
    bool = true;
} else if(this.aagaApproval == "false" && this.aagaRemarks != undefined){
    bool = true;
}

return bool;