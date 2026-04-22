/**
 * Calculated property — texto desde #iframe-USER_NAME (campos directos).
 */
let submitButtonFake = this.submitButtonFake;
let saveButtonFake = this.saveButtonFake;
let fakeSaveCloseButton = this.fakeSaveCloseButton;

var raw = '';
try {
  if (typeof $ !== 'undefined' && $('#iframe-psTools').length) {
    var el = $('#iframe-psTools').contents().find('#iframe-USER_NAME');
    var v = el.length ? el.val() : '';
    raw = v != null ? String(v) : '';
  }
} catch (e) {
  raw = '';
}
return raw;
