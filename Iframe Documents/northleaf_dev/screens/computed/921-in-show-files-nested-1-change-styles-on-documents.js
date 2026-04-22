/**
 * Set Upload Icons
 * by Favio Mollinedo
 * Modified by Ana Castillo
 */
$(document).ready(function() {
    $("label:contains('PDF Invoice')").each(function() {
        $(this).css("font-weight","Bold");
        if ($(this).find("i.pdf-icon").length === 0) {
            $(this).append('<i class="fas fa-file-pdf pdf-icon" style="margin-left: 5px;"></i>');
        }
    });
    $("label:contains('Excel Invoice')").each(function() {
        $(this).css("font-weight","Bold");
        if ($(this).find("i.excel-icon").length === 0) {
            $(this).append('<i class="fas fa-file-excel excel-icon" style="margin-left: 5px;"></i>');
        }
    });
    $("label:contains('Additional Documents')").each(function() {
        $(this).css("font-weight","Bold");
    });
});