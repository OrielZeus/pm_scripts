let submitButtonFake = this.submitButtonFake;

let dataIframe = $('#iframe-psTools').contents().find('#iframe-items').val() ?? '';
if (dataIframe !='') {
    dataIframe = JSON.parse(dataIframe);
}
return dataIframe;