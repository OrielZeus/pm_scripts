/*this.CQP_CITIES.forEach((row, index) => {
    var control = this.CQP_CITIES[index].CQP_ADDRESS;
    if (control.length == 1) {
        $("[selector='address-item'] button[data-cy='loop-CQP_CITIES-remove']").eq(index).hide();
    } else {
        $("[selector='address-item'] button[data-cy='loop-CQP_CITIES-remove']").eq(index).show();
    }
    return true;
});*/

this.CQP_CITIES.forEach((row, index) => {
    var control = row.CQP_ADDRESS;

    var $row = $("[selector='address-item']").eq(index);
    var $btn = $row.find("button[data-cy='loop-CQP_ADDRESS-remove']");

    if (control.length == 1) {
        $btn.hide();
    } else {
        $btn.show();
    }
});