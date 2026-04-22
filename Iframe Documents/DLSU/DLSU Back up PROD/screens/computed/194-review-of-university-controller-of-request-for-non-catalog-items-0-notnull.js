var bool = false;

if(this.ucApproval == "true"){
    bool = true;
} else if(this.ucApproval == "false" && this.ucRemarks != undefined){
    bool = true;
}

return bool;