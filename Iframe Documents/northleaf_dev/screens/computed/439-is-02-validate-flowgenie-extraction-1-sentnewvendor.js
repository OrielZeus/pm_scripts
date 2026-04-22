console.log("INICIA LIMPIADOR");
let mivar = this.IN_RESPONSE_NEW_VENDOR || "";

if (localStorage.getItem("cleanVars") != null) {
    if (localStorage.getItem("cleanVars") == "FULL") {
        console.log("CORTADOOOOOO" + localStorage.getItem("cleanVars"));
        return "";
    }
}

if (mivar != "") {
    window.ProcessMaker.alert("Managers have been notified of your request for a new vendor", "success");
    $("#buttonCancelPopUp").trigger("click");
    localStorage.setItem("cleanVars", "FULL");
    console.log(this.IN_RESPONSE_NEW_VENDOR, '<< NEW DATA');
}
return "";