var college_selected = this.college_selected;
// var college_selected = this.user_validation_result.college.name;
var departments_list = this.departments_list;
var filtered_departments_list = [];
if(college_selected != "NONE"){
    for(var x = 0; x <departments_list.length; x++){
        if(departments_list[x].college == college_selected){
            filtered_departments_list.push(departments_list[x]);
        }
    }
}
else{
    filtered_departments_list = departments_list;
}
return filtered_departments_list;