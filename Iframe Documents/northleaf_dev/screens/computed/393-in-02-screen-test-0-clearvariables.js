$(document).ready(function () {
    this.IN_EDIT_SUBMIT = null;
    $("button[name='IN_SAVE_SUBMIT']").one('click', function (event) {
        window.IN_EDIT_SUBMIT = null;
    });
});
return true;