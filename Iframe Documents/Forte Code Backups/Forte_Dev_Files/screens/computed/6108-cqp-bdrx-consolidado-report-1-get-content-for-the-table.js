/*
* By Diego Tapia
*/

const currentUser = ProcessMaker.user.id;
let PMQL = this.PMQL
let code = ""
let CQP_CURRENCY = this.CQP_CURRENCY
let CQP_SEARCH = this.CQP_SEARCH 
let START_DATE = this.START_DATE
let END_DATE = this.END_DATE

try {
    $("#recordScreenContainer").css("max-width", "97%");
} catch (error) {}

if (CQP_CURRENCY != undefined && CQP_CURRENCY != null && CQP_CURRENCY != "") {
    window.CQP_CURRENCY = CQP_CURRENCY
}

if (CQP_SEARCH !== undefined) {
    var data = {
        CQP_SEARCH: CQP_SEARCH,
        START_DATE: START_DATE,
        END_DATE: END_DATE,
        currentUserId: currentUser,
        currency: window.CQP_CURRENCY
    };

    var jsonData = JSON.stringify(data);
    var encodedData = encodeURIComponent(jsonData);
    
    var screenHeight = 768;
    if (typeof top.innerHeight != 'undefined') {
        screenHeight = top.innerHeight - 310;
    }
    code = `<body style="margin:0px;padding:0px;overflow:hidden;width:` + screenHeight + `px">
        <iframe id='bulkIframe' src="/api/1.0/pstools/script/cqp_BDRX_report?data=` + encodedData + `" frameborder="0" 
                style="overflow:hidden;overflow-x:hidden;overflow-y:hidden;height:` + screenHeight + `px;width:100%;top:110px;left:0px;right:0px;bottom:0px" 
        ></iframe>
    </body>`;
}

return code;