/*************************************
 * PE_RED_FLAG_LEGAL_REVIEW_LABEL
 *
 * by Adriana Centellas
 ***********************************/

let fullName = "";

for (let i = 0; i < this.PE_LEGAL_COUNSEL_OPTIONS.length; i++) {
    if (this.PE_LEGAL_COUNSEL_OPTIONS[i].ID == this.PE_RED_FLAG_LEGAL_REVIEW) {
        fullName = this.PE_LEGAL_COUNSEL_OPTIONS[i].FULL_NAME;
        break;
    }
}

return fullName;