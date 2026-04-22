var string = "";
var type = this.transactionTypeSelect;

if(type != undefined){
    if (type.value == "PRP"){
        string = "-";
    } else {
        if(this.expenseTypeSelect != undefined){
            string = this.expenseTypeSelect.value;
        }
    }
}

return string;