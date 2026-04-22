let submitButtonFake = this.submitButtonFake_subInfra;
let saveButtonFake = this.saveButtonFake_subInfra;
let fakeSaveCloseButton = this.fakeSaveCloseButton_subInfra;


let dataIframe = $('#iframe-psTools').contents().find('#iframe-deleted-rows').val() ?? '';
if (dataIframe !='') {
    dataIframe = dataIframe;
}
return dataIframe;