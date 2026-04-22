/***************************
 * Bulk Reassignment Iframe
 *
 * by Cinthia Romero
 **************************/
var data = {
    currentUserId: this.CURRENT_USER,
    filterProcess: this.FILTER_PROCESS,
    filterUser: this.FILTER_USER
};
var jsonData = JSON.stringify(data);
//Encode data to make it secure
var encodedData = encodeURIComponent(jsonData);
//Get window height
var screenHeight = 768;
if (typeof top.innerHeight != 'undefined') {
    screenHeight = top.innerHeight - 310;
}
let code = `<body style="margin:0px;padding:0px;overflow:hidden;width:` + screenHeight + `px">
    <iframe id='bulkIframe' src="/api/1.0/pstools/script/bulk-reassignment-main-screen?data=` + encodedData + `" frameborder="0" 
            style="overflow:hidden;overflow-x:hidden;overflow-y:hidden;height:` + screenHeight + `px;width:100%;top:110px;left:0px;right:0px;bottom:0px" 
    ></iframe>
</body>`;
return code;