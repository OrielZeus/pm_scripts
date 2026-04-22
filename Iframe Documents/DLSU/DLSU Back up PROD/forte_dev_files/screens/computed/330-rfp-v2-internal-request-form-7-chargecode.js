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