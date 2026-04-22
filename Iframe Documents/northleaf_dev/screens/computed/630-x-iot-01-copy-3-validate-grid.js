/**
 * Validate Custom iFrame Grid
 * 
 * by Jhon Chacolla
 */
let isValid = true;
let errorMessage = [];
let fakeSubmit = this.submitButtonFake_subInfra;
let isCustomeGridValid = this.isCustomeGridValid_subInfra;
// let totalFinalAmount = $('#iframe-psTools').contents().find('#iframe-totalFinalAmount').val() ?? 0;

if(!this.validateForm_subInfra.isValid){
    isValid = isValid && false;
    errorMessage.push('Please complete all required fields.');
}

if (!fakeSubmit) {
    isValid = isValid && false;
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
    }, 2500);
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