if(this.accountType.value == "depository" && this.accountDepType.value == "specialFunds"){
    return "specialFunds"
} else if(this.accountType.value == "depository" && this.accountDepType.value == "research" && this.researchType.value == "externalFund"){
    return "externalFund";
} else if(this.accountType.value == "depository" && this.accountDepType.value == "research" && this.researchType.value == "internalFund"){
    return "internalFund";
}