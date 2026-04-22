/**
 * Validate Custom iFrame Grid
 * 
 * by Jhon Chacolla
 */
let isValid = false;
let fakeSave = this.saveButtonFake_subInfra;
let saveForm = this.saveForm_subInfra;

let totalFinalAmount = $('#iframe-psTools').contents().find('#iframe-totalFinalAmount').val() ?? 0;
if(fakeSave != null && fakeSave != 'null') {
    $('[selector="fakeSaveButton"] > div > button'). attr("disabled", true);
    ProcessMaker.alert('Your request is being processed', "success");
    setTimeout(function () {
        $('[selector="saveButton"] > div > button').click();
        $('[selector="fakeSaveButton"] > div > button'). attr("disabled", false);
    }, 2500);
}

return saveForm;