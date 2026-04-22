/**
 * Render and set config for custom POC IS.03 Grid
 * 
 * by Jhon Chacolla
 */

 let data = {
    IN_INVOICE_PRE_TAX: this.IN_INVOICE_PRE_TAX + "",
    IN_INVOICE_TAX_TOTAL: this.IN_INVOICE_TAX_TOTAL + "",
    IN_INVOICE_TOTAL: this.IN_INVOICE_TOTAL + "",

    IN_INVOICE_PRE_TAX_PERCENTAGE: this.IN_INVOICE_PRE_TAX_PERCENTAGE + "",
    IN_INVOICE_TAX_TOTAL_PERCENTAGE: this.IN_INVOICE_TAX_TOTAL_PERCENTAGE + "",
    IN_INVOICE_TOTAL_PERCENTAGE: this.IN_INVOICE_TOTAL_PERCENTAGE + "",

    IN_INVOICE_CURRENCY: this.IN_INVOICE_CURRENCY+"",
    IN_CLEAR_GRID: this.IN_CLEAR_GRID,
    IN_REQUEST_ID: this.IN_REQUEST_ID,
    IN_INVOICE_DISCREPANCY: this.IN_INVOICE_DISCREPANCY,
    IN_EXPENSE_REQUEST: this.IN_EXPENSE_REQUEST,

    IN_TOTAL_PRE_TAX_AMOUNT_INIT_SUB_PE: this.IN_TOTAL_PRE_TAX_AMOUNT_INIT_SUB_PE,
    IN_TOTAL_HST_INIT_SUB_PE: this.IN_TOTAL_HST_INIT_SUB_PE,
    IN_TOTAL_TOTAL_INIT_SUB_PE: this.IN_TOTAL_TOTAL_INIT_SUB_PE,
    IN_TOTAL_PERCENTAGE_TOTAL_INIT_SUB_PE: this.IN_TOTAL_PERCENTAGE_TOTAL_INIT_SUB_PE,
    IN_INVOICE_PRE_TAX_PERCENTAGE_INIT_SUB_PE: this.IN_INVOICE_PRE_TAX_PERCENTAGE_INIT_SUB_PE,
    IN_INVOICE_TAX_TOTAL_PERCENTAGE_INIT_SUB_PE: this.IN_INVOICE_TAX_TOTAL_PERCENTAGE_INIT_SUB_PE,
    IN_INVOICE_TOTAL_PERCENTAGE_INIT_SUB_PE: this.IN_INVOICE_TOTAL_PERCENTAGE_INIT_SUB_PE,
    _user:this.currentUser.id,
    IN_TEAM_ID: 'INFRA',
    Allocated_INFRA: this.Allocated_INFRA,
    IN_SUMMARY_TOTAL_GRID: this.IN_SUMMARY_TOTAL_GRID,
    IN_IS_DISCREPANCY:this.IN_IS_DISCREPANCY
};
let jsonData = JSON.stringify(data);
//Encode data to make it secure
let encodedData = encodeURIComponent(jsonData);
//Get window height
let screenHeight = 1200;
if (typeof top.innerHeight != 'undefined') {
    screenHeight = top.innerHeight - 50;
}
//let code = `<body style="margin:0px;padding:0px;overflow:hidden;width:` + screenHeight + `px">`;
let code = `<body style="margin:0px;padding:0px;">
    <style>
    .iframe-container {
        background:url(/public-files/loadingIMG.gif) center center no-repeat;
    }
    </style>
    <div class="iframe-container">
        <iframe id="iframe-psTools"
            src="/api/1.0/pstools/script/pe-allocation-custom-grid?data=` + encodedData + `" frameborder="0" 
            style="overflow:hidden;overflow-x:hidden;overflow-y:hidden;height:` + screenHeight + `px;
            width:100%; top:110px;left:0px;right:0px;bottom:0px" 
        ></iframe>
    <div>
</body>`;
return code;