var chargecode = "";

if(this.campusChoiceCode == "MC"){
    chargecode = "11";
} else if(this.campusChoiceCode == "LC"){
    chargecode = "21";
} else if(this.campusChoiceCode == "BC"){
    chargecode = "31";
} else if(this.campusChoiceCode == "MKC"){
    chargecode = "12";
}


return chargecode;