var string = "";

if(this.payee != undefined){
    string = this.payee.ID.substring(1,this.payee.ID.length);
}

return string;