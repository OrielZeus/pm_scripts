/********************
 * POC - Grid iframe
 *
 * by Telmo Chiri
 *******************/
 let data = {
    totalPreTaxAmount: this.totalPreTaxAmount,
    totalHSTAmount: this.totalHst,
    totalAmount: this.totalAmount,
};
let jsonData = JSON.stringify(data);
//Encode data to make it secure
let encodedData = encodeURIComponent(jsonData);
//Get window height
let screenHeight = 508;
if (typeof top.innerHeight != 'undefined') {
    screenHeight = top.innerHeight - 250;
}
let code = `<body style="margin:0px;padding:0px;overflow:hidden;width:` + screenHeight + `px">
    <iframe id="iframe-psTools"
        src="/api/1.0/pstools/script/poc-custom-grid-iframe?data=` + encodedData + `" frameborder="0" 
        style="overflow:hidden;overflow-x:hidden;overflow-y:hidden;height:` + screenHeight + `px;
        width:100%; top:110px;left:0px;right:0px;bottom:0px" 
    ></iframe>
</body>`;
return code;