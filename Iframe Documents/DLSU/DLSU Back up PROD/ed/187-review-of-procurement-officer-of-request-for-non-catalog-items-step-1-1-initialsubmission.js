var bool;

if(this.dpsApproval != "-" || this.edscmApproval != "-"){
    bool = "false";
} else if(this.dpsApproval == "-" && this.edscmApproval == "-"){
    bool = "true";
}

return bool;