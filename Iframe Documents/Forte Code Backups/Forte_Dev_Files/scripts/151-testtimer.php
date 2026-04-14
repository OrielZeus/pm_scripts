<?php 
$dataReturn = [];
$dataReturn['NEW_VARIABLE'] = 'TEST';
if ($data['variable1'] == '123') {
    $dataReturn['abc'] = "";
} else {
    $dataReturn['abc'] = 'NO';
}

return $dataReturn;