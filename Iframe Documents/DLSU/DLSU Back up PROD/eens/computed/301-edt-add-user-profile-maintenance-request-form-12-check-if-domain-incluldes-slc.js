var domain  = this.user_validation_result.domain;
var is_with_slc = false;
if(domain != null){
    for(var i = 0; i < domain.length; i++){
        if(domain[i] == "SLC"){
            is_with_slc = true;
        }
    }   
}
return is_with_slc;