var str = this.employee_email;
var at_length = str.indexOf("@");
var at_count = (str.match(/@/g) || []).length;
var dot_length = str.lastIndexOf(".");
var first_dot = str.indexOf(".");
var email_validity = "";
var email_list = this.email_list;
var email_entered = this.employee_email;
var is_email_entered = email_list.includes(email_entered);
var original_email = this.user_validation_result.email;

if(str.includes("@") && at_count == 1 && str.includes(".") && first_dot != 0 && at_length != 0 && at_length+1 < dot_length && dot_length != str.length-1 && (is_email_entered == false || 
(is_email_entered == true && original_email == email_entered))){
    email_validity = "Valid Email";
}
else if(str != ""){
    email_validity = "Invalid Email";
}
if(is_email_entered== true && email_entered != original_email){
    email_validity = "Email Used";
}
return email_validity;