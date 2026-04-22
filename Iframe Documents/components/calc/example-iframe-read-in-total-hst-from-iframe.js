/**
 * Calculated property — reads iframe-IN_TOTAL_HST from iframe-psTools (northleaf / IS.03 pattern).
 * Keeps references to fake buttons so PM does not strip `this` dependencies.
 *
 * Si siempre ves 0: PM suele no re-ejecutar hasta que cambie otra variable de pantalla;
 * el iframe puede cargar después del primer cálculo. Ver GUIDE.md § "Why calcs...".
 */

let submitButtonFake = this.submitButtonFake;
let saveButtonFake = this.saveButtonFake;
let fakeSaveCloseButton = this.fakeSaveCloseButton;

var dataIframe = '';
try {
    if (typeof $ !== 'undefined' && $('#iframe-psTools').length) {
        var el = $('#iframe-psTools').contents().find('#iframe-IN_TOTAL_HST');
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
