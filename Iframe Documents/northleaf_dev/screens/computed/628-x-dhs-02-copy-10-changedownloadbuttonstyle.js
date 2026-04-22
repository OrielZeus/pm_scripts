/**
 * 
 * By Favio Mollinedo
 */
$(document).ready(function() {
    setTimeout(function() {
        $("button[aria-label='IS.01 PDF Uploaded']").removeClass("btn-primary");
        $("button[aria-label='IS.01 PDF Uploaded']").attr("class", "");
        $("button[aria-label='IS.01 PDF Uploaded']").addClass("button-as-links");
    }, 800);
});