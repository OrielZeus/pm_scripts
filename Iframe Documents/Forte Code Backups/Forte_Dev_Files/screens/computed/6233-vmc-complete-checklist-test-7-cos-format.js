$("head").append('<script src="https://kit.fontawesome.com/2f3a9352b0.js" crossorigin="anonymous"></script>');

$(".card-body").css("padding","0");
$(".card-body").css("border","none");

$(document).ready(function() {
    const thisYear = new Date().getFullYear();
    $("#copyRightYear").text("© " + thisYear + " City of Stonnington");
});