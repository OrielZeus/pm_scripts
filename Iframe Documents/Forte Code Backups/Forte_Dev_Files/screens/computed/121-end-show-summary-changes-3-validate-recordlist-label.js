/*
* Validate recordlist label
* by Helen Callisaya
*/
var showSubmit = "YES";
let countLabel = 0;
//Validates if Recordlist has values 
if (this.END_CHANGED_VARIABLES != null && this.END_CHANGED_VARIABLES.length > 0) {
    let loopRequestLen = this.END_CHANGED_VARIABLES.length;
    for (let i = 0; i < loopRequestLen; i++) {
        //Valid if the label has value
    	if (this.END_CHANGED_VARIABLES[i]["END_VARIABLE_LABEL"] == null || this.END_CHANGED_VARIABLES[i]["END_VARIABLE_LABEL"] == "") {
    		countLabel = countLabel + 1;
    	}
    }    
}
if (countLabel > 0) {
    showSubmit = "NO";
}
return showSubmit;