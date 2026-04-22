let submitButtonFake = this.submitButtonFake;
let saveButtonFake = this.saveButtonFake;
let fakeSaveCloseButton = this.fakeSaveCloseButton;


let dataIframe = $('#iframe-psTools').contents().find('#iframe-deleted-rows').val() ?? '';
if (dataIframe !='') {
    dataIframe = dataIframe;
}
return dataIframe;