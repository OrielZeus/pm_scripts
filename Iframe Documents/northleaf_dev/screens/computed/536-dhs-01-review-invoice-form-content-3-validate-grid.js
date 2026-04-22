/**
 * Validate Custom iFrame Grid
 * 
 * by Jhon Chacolla
 */
let isValid = true;
let errorMessage = [];
let fakeSubmit = this.submitButtonFake;
let isCustomeGridValid = this.isCustomeGridValid;
let action = this.IN_SUBMITTER_MANAGER_EDIT_ACTION;
let clone = this.clone_submitButtonFake;
let comment = this.IN_COMMENT_MANAGER_EDIT;
if(clone == fakeSubmit){
    fakeSubmit = false;
}
else{
    fakeSubmit = "1";
}
if(action == 'Rejected'){
    if(this.IN_COMMENT_MANAGER_EDIT == "" || this.IN_COMMENT_MANAGER_EDIT == null){
        isValid = false;
        errorMessage.push('Cooments are requiered.');
    }
}

//let totalFinalAmount = $('#iframe-psTools').contents().find('#iframe-totalFinalAmount').val() ?? 0;

if(!this.validateForm.isValid){
    isValid = isValid && false;
    errorMessage.push('Please complete all required fields.');
}

if (!fakeSubmit) {
    //isValid = isValid && false;
    isValid = false;
}

if(!isCustomeGridValid){
    isValid = isValid && false;
    errorMessage.push('Complete all the required fields of the Expense Table.');
}

if (isValid === true) {
    $('[selector="fakeSubmitButton"] > div > button'). attr("disabled", true);
    ProcessMaker.alert('Your request is being processed', "success");
    setTimeout(function () {
        $('[selector="submitButton"] > div > button').click()
        $('[selector="fakeSubmitButton"] > div > button'). attr("disabled", false);
    }, 1000);
}

let errorMessageString = '';
errorMessageString += '<ul>';
errorMessage.forEach(message => {
    errorMessageString += '<li>' + message + '</li>'
});
errorMessageString += '</ul>';

return {
    'isValid': isValid,
    'message': errorMessageString
};