var data = {
    currentUserId: this.DASHBOARD_CURRENT_USER,
};
var jsonData = JSON.stringify(data);
//Encode data to make it secure
var encodedData = encodeURIComponent(jsonData);
//Get window height
var screenHeight = 700;
if (typeof top.innerHeight != 'undefined') {
    screenHeight = top.innerHeight - 230;
}

return {
    'encodedData': encodedData,
    'height': screenHeight
};

/*<iframe style="overflow-x: hidden; height: {{iframeOptions.height}}px; width: 100%; top: 110px; left: 0px; right: 0px; bottom: 0px;" src="/api/1.0/pstools/script/dashboard-cases-list?data={{iframeOptions.encodedData}}" frameborder="0"></iframe>*/