/**
 * Validate Custom iFrame Grid
 * 
 * by Jhon Chacolla
 */
let isValid = false;
let fakeSubmit = this.submitButtonFake;
console.log('fakeSubmit',fakeSubmit);
let totalFinalAmount = $('#iframe-psTools').contents().find('#iframe-totalFinalAmount').val() ?? 0;
console.log('PM totalFinalAmount-> ', totalFinalAmount);
if (this.totalAmount == totalFinalAmount && totalFinalAmount != 0 && typeof(totalFinalAmount) != 'undefined' && fakeSubmit != null) {
    isValid = true;
}

if (isValid === true) {
    $('[selector="fakeSubmitButton"] > div > button'). attr("disabled", true);
    ProcessMaker.alert('Your request is being processed', "success");
    setTimeout(function () {
        $('[selector="submitButton"] > div > button').click()
        $('[selector="fakeSubmitButton"] > div > button'). attr("disabled", false);
    }, 2500);
}

return isValid;