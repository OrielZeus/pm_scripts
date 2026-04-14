let actions = this.CQP_ACTION
let CQP_UPDATE_NAME = this.CQP_UPDATE_NAME
let CQP_INSURED_NAME_NEW = this.CQP_INSURED_NAME_NEW

try {
    if (actions == "NEW") {
        if (this.CQP_CREATE_NEW_INSURED_NAME == "NO") {
            if (CQP_UPDATE_NAME == "YES") {
                return CQP_INSURED_NAME_NEW
            } else {
                return this.CQP_INSURED_NEW_CREATED.CQP_INSURED_NAME
            }
        } else {
            return CQP_INSURED_NAME_NEW
        }
    } else if (actions == "RENEWAL") {
        if (CQP_UPDATE_NAME == "YES") {
            return CQP_INSURED_NAME_NEW
        } else {
            return this.CQP_INSURED_RENEWAL.CQP_INSURED_NAME
        }
    } else if (actions == "CLONE") {
        return this.CQP_INSURED_CLONE.CQP_INSURED_NAME
    }
} catch (error) {
    return ""
}

return ""