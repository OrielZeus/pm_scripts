var required = this._parent.REQUIRED_IF;
var country = this._parent.CQP_COUNTRY;

if(required == true && country == "PANAMÁ"){
    return true;
}

return false;