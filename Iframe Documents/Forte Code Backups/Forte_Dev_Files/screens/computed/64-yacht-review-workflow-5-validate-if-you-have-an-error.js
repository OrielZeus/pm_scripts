/*
* Validate if you have an error 
* by Helen Callisaya
*/
if (this.YQP_AGREEMENT_CREATED_ERROR == "" || this.YQP_AGREEMENT_CREATED_ERROR == null) {
    //Has no error
    return 0;
} else {
    //Show Error
    return 1;
}