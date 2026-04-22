var bool = true;

if(this.multiplePayeeLine != undefined){
    if(this.multiplePayeeLine.length <= 1){
        bool = true;
    } else{
        bool = false;
    }
}

return bool;