var college_selected = "NONE";
var college_value = this.user_validation_result.college;
if(college_value != null && college_value != ""){
    college_selected = college_value.name;
}
return college_selected;