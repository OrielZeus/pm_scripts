var username_used = this.user_validation_result.username_used;
var transaction_type = this.transaction_type;
var username_checked = this.user_validation_result.username;
var username_create = this.username_create;
var username_valid = "Check Validity";

if(username_create != username_checked || username_checked == "" || username_checked == null){
    username_valid = "Check Validity";
}
else{
    if(transaction_type == "Create"){
        username_valid = "Username Existing";
    }
    else{
        username_valid = "Invalid Username";
    }
}
if(transaction_type == "Create" && username_used == "false" && username_create == username_checked){
    username_valid = "Valid Username";
}
if(transaction_type == "Update" && username_used == "true" && username_create == username_checked){
    username_valid = "Valid Username";
}
if(transaction_type == "Deactivate" && username_used == "true" && username_create == username_checked){
    username_valid = "Valid Username";
}
return username_valid;