var original_department_list = this.departments_list;
// var college = this.user_validation_result.college.name;
var new_list = new Array;
var domain = this.domain;

if(original_department_list != undefined){
    for(var i = 0; i < original_department_list.length; i++){
        var college_in_list = original_department_list[i].college;
        if(college == college_in_list){        
            new_list.push(original_department_list[i]);
        }
    }
}
if(domain != "SLC"){
    new_list = original_department_list;
}
// new_list = original_department_list;
return new_list;