var chargecode = "";

if(this.campusChoice == "Manila"){
    chargecode = "11";
} else if(this.campusChoice == "Laguna"){
    chargecode = "21";
} else if(this.campusChoice == "BGC"){
    chargecode = "31";
} else if(this.campusChoice == "Makati"){
    chargecode = "41";
}


if (this.transactionTypeCode == "CA"){
    chargecode = chargecode + "-0-00-00-000-00000-11000101-1-11-00-111004001-00-0000-0000";
} else if (this.transactionTypeCode == "PCO"){
    chargecode = chargecode + "-0-00-00-000-00000-11000101-1-11-00-111004001-00-0000-0000";
} else if (this.transactionTypeCode == "PCC"){
    chargecode = chargecode + "-0-00-00-000-00000-11000101-1-11-00-111004001-00-0000-0000";
} else if (this.transactionTypeCode == "UNL"){
    chargecode = chargecode + "-0-00-00-000-00000-11000101-1-11-00-111004001-00-0000-0000";
}

return chargecode;