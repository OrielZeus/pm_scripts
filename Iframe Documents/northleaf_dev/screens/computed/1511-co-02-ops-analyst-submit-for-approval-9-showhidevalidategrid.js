let actionBtn = this.auxiliarGridValidation_CO;
let isValidCustomeGrid = $('#iframe-psTools').contents().find('#iframe-IN_IS_VALID_CUSTOME_GRID').val();
if(isValidCustomeGrid == 'true'){
    return true;
}
return false;