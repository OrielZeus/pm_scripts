var bool = false;

if(this.hgaApproval == "true"){
    bool = true;
} else if(this.hgaApproval == "false" && this.hgaRemarks != undefined){
    bool = true;
}

return bool;