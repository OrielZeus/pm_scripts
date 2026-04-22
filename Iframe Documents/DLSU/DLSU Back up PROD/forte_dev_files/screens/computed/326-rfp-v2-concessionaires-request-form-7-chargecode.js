// var chargecode = "";

// if(this.campusChoiceCode == "MC"){
//     chargecode = "11";
// } else if(this.campusChoiceCode == "LC"){
//     chargecode = "21";
// } else if(this.campusChoiceCode == "BC"){
//     chargecode = "31";
// } else if(this.campusChoiceCode == "MKC"){
//     chargecode = "12";
// }

var chargecode = this.campusChoiceSelect.code;

if (this.transactionTypeCode == "CA"){
    chargecode = chargecode + "-0-00-00-000-00000-11000101-1-11-00-111004001-00-0000-0000";
} else if (this.transactionTypeCode == "PCO"){
    chargecode = chargecode + "-0-00-00-000-00000-11000101-1-11-00-111004001-00-0000-0000";
} else if (this.transactionTypeCode == "PCC"){
    chargecode = chargecode + "-0-00-00-000-00000-11000101-1-11-00-111004001-00-0000-0000";
} else if (this.transactionTypeCode == "UNL"){
    chargecode = chargecode + "-0-00-00-000-00000-11000101-1-11-00-111004001-00-0000-0000";
} else if (this.transactionTypeCode == "CONTRACTOR"){
    chargecode = chargecode + "-0-00-00-000-00000-11000101-1-11-00-111003001-00-0000-0000";
}

return chargecode;