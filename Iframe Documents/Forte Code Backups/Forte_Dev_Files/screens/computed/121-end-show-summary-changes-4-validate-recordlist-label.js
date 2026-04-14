/*
* Validate recordlist label
* by Helen Callisaya
*/
var showSubmit = "NO";
//Validates if Recordlist has values 
if (this.END_CHANGED_VARIABLES != null && this.END_CHANGED_VARIABLES.length > 0) {
    showSubmit = "YES";
}
return showSubmit;