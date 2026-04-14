var loop = this.CQP_CITIES;
var actionButton = this.ACTION;
var required =  false
if(actionButton == "SAVE"){
    this.CQP_CITIES.forEach((row, index) => {
        this.CQP_CITIES[index].REQUIRED_IF = false
    })
    
}
else{
    required = true;
    this.CQP_CITIES.forEach((row, index) => {
        this.CQP_CITIES[index].REQUIRED_IF = true
    })
}
return required;