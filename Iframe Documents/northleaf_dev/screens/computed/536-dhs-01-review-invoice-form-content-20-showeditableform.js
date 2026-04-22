let ret = "";
if (this.IN_BUTTON_FLAG == "1") {
    ret = "APPROVE";
} else {
    if (this.IN_BUTTON_FLAG == "3") {
        ret = "REJECT";
    } else {
        ret = "YES";
    }
}
console.log(ret);
return ret;