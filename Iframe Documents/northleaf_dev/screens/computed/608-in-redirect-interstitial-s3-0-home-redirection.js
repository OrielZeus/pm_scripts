/**
 * Home Redirection
 * by Manuel Monroy
 * Modified by Ana Castillo
*/

if (this.IN_SAVE_SUBMIT == "SAVE_AND_CLOSE") {
    window.location.href = "/";
}
if (this.IN_SAVE_SUBMIT == "SUBMIT") {
    if (this.IN_JUMP_APPROVER) {
        if(this.validateJump){
            //setTimeout(function() {
                window.location.href = "/";
            //}, 500);
        }
    } else {
        //setTimeout(function() {
            window.location.href = "/";
        //}, 500);
    }
}