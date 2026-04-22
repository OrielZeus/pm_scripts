var multiple_departments = this.multiple_departments;
if(multiple_departments){
    return this.approving_department_list.department_code;
}
else{
    return this.requestor_department_code;
}