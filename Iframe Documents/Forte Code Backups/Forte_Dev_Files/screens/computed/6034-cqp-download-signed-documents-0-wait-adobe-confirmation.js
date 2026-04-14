let CQP_STATUS = this.CQP_STATUS

if (CQP_STATUS != "BOUND" && CQP_STATUS != "DECLINED") {
    return true
}

return false