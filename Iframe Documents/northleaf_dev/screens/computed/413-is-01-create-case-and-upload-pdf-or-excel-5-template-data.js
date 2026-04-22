let result = [];
if(this.IN_TEMPLATE_ID != null && this.IN_TEMPLATE_ID != undefined && this.IN_TEMPLATE_ID != ""){
    let idToFind = this.IN_TEMPLATE_ID;
    result = this.EXCEL_TEMPLATE_DATA.find(item => item.ID == idToFind);
    return result;
}
return result;