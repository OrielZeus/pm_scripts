let submitButtonFake = this.submitButtonFake;
let saveButtonFake = this.saveButtonFake;
let fakeSaveCloseButton = this.fakeSaveCloseButton;

let dataIframe = $('#iframe-psTools').contents().find('#iframe-IN_TOTAL_TOTAL').val() ?? '';
if (dataIframe !='') {
    let retriveData = parseFloat(dataIframe);
    return isNaN(retriveData) ? 0 : retriveData;
}
return 0;