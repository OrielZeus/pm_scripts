/*************************************
 * PE_RED_FLAG_TAX_REVIEW_LABEL
 *
 * by Adriana Centellas
 ***********************************/

let fullName = "";

for (let i = 0; i < this.PE_TAX_REPRESENTATIVE_OPTIONS.length; i++) {
    if (this.PE_TAX_REPRESENTATIVE_OPTIONS[i].ID == this.PE_RED_FLAG_TAX_REVIEW) {
        fullName = this.PE_TAX_REPRESENTATIVE_OPTIONS[i].FULL_NAME;
        break;
    }
}

return fullName;