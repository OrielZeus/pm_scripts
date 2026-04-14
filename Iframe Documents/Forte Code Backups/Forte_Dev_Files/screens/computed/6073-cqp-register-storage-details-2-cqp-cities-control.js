var control = this.CQP_CITIES;
if (control.length == 1) {
    $("[selector='city-item'] button[data-cy='loop-CQP_CITIES-remove']").hide();
} else {
    $("[selector='city-item'] button[data-cy='loop-CQP_CITIES-remove']").show();
}
return true;