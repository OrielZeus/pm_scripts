/**
 * Validate Custom iFrame Grid
 * 
 * by Jhon Chacolla
 */
let isValid = false;
let fakeSave = this.saveButtonFake;
let saveForm = this.saveForm;

let totalFinalAmount = $('#iframe-psTools').contents().find('#iframe-totalFinalAmount').val() ?? 0;
if(fakeSave != null && fakeSave != 'null') {
    $('[selector="fakeSaveButton"] > div > button'). attr("disabled", true);
    ProcessMaker.alert('Your request is being processed', "success");
    setTimeout(function () {
        $('[selector="saveButton"] > div > button').click();
        $('[selector="fakeSaveButton"] > div > button'). attr("disabled", false);
    }, 500);
}

return saveForm;