/*
* Show request error
*
* by Cinthia Romero
*/
//validates if the FORTE_ERRORS.FORTE_ERROR_LOG variable has no errors
if (this.FORTE_ERRORS.FORTE_ERROR_LOG == "" || 
    this.FORTE_ERRORS.FORTE_ERROR_LOG == "no-error" || 
    this.FORTE_ERRORS.FORTE_ERROR_LOG == null || 
    this.FORTE_ERRORS.FORTE_ERROR_LOG == undefined) {
    return false;
} else {
    return true;
}