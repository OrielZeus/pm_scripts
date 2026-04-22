/********************
 * Dashboard iframe
 *
 * by Cinthia Romero
 *******************/
 var data = {
    currentUserId: this.DASHBOARD_CURRENT_USER,
};
var jsonData = JSON.stringify(data);
//Encode data to make it secure
var encodedData = encodeURIComponent(jsonData);
//Get window height
var screenHeight = 768;
if (typeof top.innerHeight != 'undefined') {
    screenHeight = top.innerHeight - 200;
}
let code = `<body style="margin:0px;padding:0px;overflow:hidden;width:` + screenHeight + `px">
    <iframe src="/api/1.0/pstools/script/dashboard-main-screen?data=` + encodedData + `" frameborder="0" 
            style="overflow:hidden;overflow-x:hidden;overflow-y:hidden;height:` + screenHeight + `px;width:100%; position:absolute;top:110px;left:0px;right:0px;bottom:0px" 
    ></iframe>
</body>`;
return code;