if (this.form_input_4 != null){
  let valor = this.form_input_4;
  if (valor >= 0 && valor <= 50) {
        $('[selector="input_4"] > div > input').css("background-color", "#ffcccc"); // rojo claro
      } else if (valor > 50 && valor <= 80) {
        $('[selector="input_4"] > div > input').addClass('inputOk'); // amarillo claro
      } else if (valor > 80) {
        $('[selector="input_4"] > div > input').css("background-color", "#ccffcc"); // verde claro
      } else {
        $('[selector="input_4"] > div > input').css("background-color", "");
      }
}