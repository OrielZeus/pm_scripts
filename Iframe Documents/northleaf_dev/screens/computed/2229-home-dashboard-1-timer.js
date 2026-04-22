$(document).ready(function() {
  // Espera 1000 milisegundos (1 segundo)
  setTimeout(function() {
    // Selecciona el elemento por su ID y cambia el padding
    $('#dashboardShow').prop('style', 'padding-left: 0px !important; padding-right: 0px !important;');
    $('#mainbody').prop('style', 'padding-top: 0px !important; padding-bottom: 0px !important;');
    $('#main').prop('style', 'padding-top: 0px !important; padding-bottom: 0px !important; background-color: #e4e4e4 !important;');
  }, 1000);
});