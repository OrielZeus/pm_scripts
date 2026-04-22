let file = this.IN_FX_UPLOAD_FILE;
let btn  = this.SUBMIT_CO_FX;
debugger;
if((file == undefined || file == null) && btn == "SUBMIT"){
    return true;
}
return false;