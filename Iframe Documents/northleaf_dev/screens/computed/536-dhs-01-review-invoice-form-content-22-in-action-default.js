let ret = "";
if (this.IN_BUTTON_FLAG == "1") {
    ret = "Approved";
} else {
    if (this.IN_BUTTON_FLAG == "3") {
        ret = "Rejected";
    } else {
        ret = "";
    }
}

return ret;