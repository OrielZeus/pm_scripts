/**
 * Validate Custom iFrame Grid
 * 
 * by Jhon Chacolla
 */
let isValid = false;
let fakeSave = this.fakeSaveCloseButton_subPc;
let saveForm = this.saveForm_subPc;
console.log('saveClose');
if(fakeSave != null) {
    $('[selector="fakeSaveCloseButton"] > div > button'). attr("disabled", true);
    ProcessMaker.alert('Your request is being processed', "success");
    setTimeout(function () {
        $('[selector="saveCloseButton"] > div > button').click();
        $('[selector="fakeSaveCloseButton"] > div > button'). attr("disabled", false);
    }, 2500);
}

return saveForm;