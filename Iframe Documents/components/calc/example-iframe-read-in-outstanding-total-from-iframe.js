/**
 * Calculated property — reads iframe-IN_OUTSTANDING_TOTAL from iframe-psTools.
 * Ver GUIDE.md si el valor no actualiza al cambiar solo el iframe.
 */

let submitButtonFake = this.submitButtonFake;
let saveButtonFake = this.saveButtonFake;
let fakeSaveCloseButton = this.fakeSaveCloseButton;

var dataIframe = '';
try {
    if (typeof $ !== 'undefined' && $('#iframe-psTools').length) {
        var el = $('#iframe-psTools').contents().find('#iframe-IN_OUTSTANDING_TOTAL');
        var v = el.length ? el.val() : '';
        dataIframe = v != null ? String(v) : '';
    }
} catch (e) {
    dataIframe = '';
}

if (dataIframe !== '') {
    var retriveData = parseFloat(dataIframe);
    return isNaN(retriveData) ? 0 : retriveData;
}
return 0;
