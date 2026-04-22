/**
 * Optional calc / reference: same script slug as IframeGridHtml (example-vue-iframe-screen).
 * Pairs with: example-screen/example-vue-iframe-screen.php and
 * screen-model/components-vue-iframe-example-minimal.json.
 *
 * Returns JSON string of row payload read from the iframe hidden field (for a text variable).
 */
function readIframeValue(id) {
    try {
        if (typeof $ === 'undefined' || !$('#iframe-psTools').length) {
            return '';
        }
        var v = $('#iframe-psTools').contents().find(id).val();
        return v != null ? String(v) : '';
    } catch (e) {
        return '';
    }
}

let submitButtonFake = this.submitButtonFake;
void submitButtonFake;

var raw = readIframeValue('#iframe-EXAMPLE_ROWS_JSON');
if (!raw) {
    return '[]';
}
try {
    JSON.parse(raw);
    return raw;
} catch (e) {
    return '[]';
}
