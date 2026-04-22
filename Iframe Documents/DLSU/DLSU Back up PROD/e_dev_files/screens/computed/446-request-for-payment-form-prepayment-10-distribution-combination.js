var distribution_combination = "";

if(this.campusChoiceCode == "MC"){
    distribution_combination = "11";
} else if(this.campusChoiceCode == "LC"){
    distribution_combination = "21";
} else if(this.campusChoiceCode == "BC"){
    distribution_combination = "31";
} else if(this.campusChoiceCode == "MKC"){
    distribution_combination = "12";
}

 if (this.transactionTypeCode == "CTRA"){
    distribution_combination = distribution_combination + "-0-00-00-000-00000-11000101-1-11-00-111004001-00-0000-0000";
} else if (this.transactionTypeCode == "CRCA"){
    distribution_combination = distribution_combination + "-0-00-00-000-00000-11000101-1-11-00-111004001-00-0000-0000";
}

return distribution_combination;