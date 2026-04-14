<?php
/*  
* Get Period From and Period by type endorsement
* by Helen Callisaya
*/

$typeEndorsementOld = $data['END_TYPE_ENDORSEMENT_OLD'];
switch ($typeEndorsementOld) {
    case 'Coverage Extension':
        $dataResult['END_PERIOD_TO_NEW'] = $data['END_NEW_PERIOD_TO_OLD'];
        break;
    default:
        $dataResult['END_PERIOD_TO_NEW'] = $data['YQP_PERIOD_TO'];
}
return $dataResult;