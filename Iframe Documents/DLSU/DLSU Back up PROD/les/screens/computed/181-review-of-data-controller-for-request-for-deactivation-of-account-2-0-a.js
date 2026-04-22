$(document).ready(function(){
    if($('input[name="isZeroBalance"]:checked').val() === "false"){
        $('.text-muted').show();
        console.log('show');
    } else {
        $('.text-muted').hide();
        console.log('hide');
    }
});