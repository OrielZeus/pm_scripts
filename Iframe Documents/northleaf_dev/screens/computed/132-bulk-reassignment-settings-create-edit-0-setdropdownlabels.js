let ddLabels = [];
if (this.actionHelper || 1) {
    $("div[name='PERMISSIONS_SETTINGS']").children().each(function(){
        if ($(this).find("div[class=page]").length){
            debugger;
            let rowLabels = {
                "PROCESS_label": $(this).find("div[id*=PROCESS-]").find(".multiselect__single").text(),
                "GROUP_LIST_label": $(this).find("div[id*=GROUP_LIST-]").find(".multiselect__tag span").map(function() {
                    return $(this).text();
                }).get().join(', '),
                "USER_LIST_label": $(this).find("div[id*=USER_LIST-]").find(".multiselect__tag span").map(function() {
                    return $(this).text();
                }).get().join(', ')                
            }
            ddLabels.push(rowLabels);
        }        
    });

    this.PERMISSIONS_SETTINGS.forEach((row, index)=>{
        row.PROCESS_label = ddLabels[index].PROCESS_label;
        row.GROUP_LIST_label = ddLabels[index].GROUP_LIST_label;
        row.USER_LIST_label = ddLabels[index].USER_LIST_label;
    });
}
return ddLabels;