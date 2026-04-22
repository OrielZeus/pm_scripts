/*****************************
 * Get comments flag
 * by Cinthia Romero
 * Modified by Ana Castillo
 *****************************/
var abeUrl = window.location.href;
addCommentsFlag = abeUrl.split("=")[1];
//Add validations to open by default with comments
var auxReturn = "1";
if (addCommentsFlag == 0) {
    auxReturn = "0";
}

return auxReturn;