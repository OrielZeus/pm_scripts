/**
 * Lee los hidden iframe-* del booking-range y devuelve JSON string para una variable de texto en la solicitud.
 *
 * northleaf: $('#iframe-psTools').contents().find('#iframe-...')
 *
 * Screen computed name sugerido: BookingOutboundJsonFromIframe (string).
 */

function readIframeField(selector) {
    try {
        if (typeof $ === 'undefined' || !$('#iframe-psTools').length) {
            return '';
        }
        var el = $('#iframe-psTools').contents().find(selector);
        if (!el.length) {
            return '';
        }
        var val = el.val();
        return val != null ? String(val) : '';
    } catch (e) {
        return '';
    }
}

var payload = {
    BookingRangeStart: readIframeField('#iframe-BOOKING_RANGE_START'),
    BookingRangeEnd: readIframeField('#iframe-BOOKING_RANGE_END'),
    BookingBlockedStart: readIframeField('#iframe-BOOKING_BLOCKED_START'),
    BookingBlockedEnd: readIframeField('#iframe-BOOKING_BLOCKED_END'),
    BookingExtraBlocksJson: readIframeField('#iframe-BOOKING_EXTRA_BLOCKS_JSON'),
    BookingSelectionJson: readIframeField('#iframe-BOOKING_SELECTION_JSON')
};

try {
    return JSON.stringify(payload);
} catch (e) {
    return '{}';
}
