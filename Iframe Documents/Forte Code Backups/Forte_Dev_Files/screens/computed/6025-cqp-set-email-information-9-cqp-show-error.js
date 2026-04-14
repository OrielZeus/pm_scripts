/*
* Calc Show request error dialog
* by Helen Callisaya
* Modified by Mateo Rada
*/
//validates if the FORTE_ERRORS.FORTE_ERROR_LOG variable has no errors
if (this.FORTE_ERROR.data.FORTE_ERROR_LOG == "" || 
    this.FORTE_ERROR.data.FORTE_ERROR_LOG == "no-error" || 
    this.FORTE_ERROR.data.FORTE_ERROR_LOG == null || 
    this.FORTE_ERROR.data.FORTE_ERROR_LOG == undefined) {
    return false;
} else {
    return true;
}