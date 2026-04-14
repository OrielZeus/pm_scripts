/*
* Show or hide Submit field with Period dates
*
* by Ana Castillo
*/
//Defines an object to store values
var submitVariables = new Object();
submitVariables['YQP_VALIDATE_PERIOD'] = "YES";
//Add 18 months
var periodMaximum = moment(this.YQP_PERIOD_FROM, "YYYY-MM-DD");
var periodMaximum = moment(periodMaximum).add('months', 18).format('YYYY-MM-DD');
submitVariables['YQP_PERIOD_MAXIMUM'] = periodMaximum;
//Validate Period Maximum
if (this.YQP_PERIOD_FROM != "" && this.YQP_PERIOD_FROM != null && this.YQP_PERIOD_TO != "" && this.YQP_PERIOD_TO != null) {
    var periodTo = moment(this.YQP_PERIOD_TO, "YYYY-MM-DD");
    if (moment(periodTo).isSameOrBefore(periodMaximum) != true) {
        submitVariables['YQP_VALIDATE_PERIOD'] = "NO";        
    }
}

//Define show or hide submit button
if (submitVariables['YQP_VALIDATE_PERIOD'] == "YES") {
    submitVariables['YQP_SUBMIT'] = "YES";
} else {
    submitVariables['YQP_SUBMIT'] = "NO";
}
return submitVariables;