<?php 
/*  
 * Set approve Sr Underwriter
 * by Helen Callisaya
 */
if ($data['_request']['process_id'] == getenv('FORTE_ID_YACHT')) {
    //User fullname Sr. Underwriter
    $dataRequest['YQP_USER_FULLNAME'] = "Sr. Underwriter";
    $dataRequest['YQP_COMMENTS_APPROVE_CONFIRMATION_SLIP'] = "";
    $dataRequest['YQP_APPROVE_CONFIRMATION_UPLOAD_SLIP'] = "";
    $dataRequest['YQP_FLOW_CONFIRMATION_SLIP'] = "";
}
if ($data['_request']['process_id'] == getenv('FORTE_ID_ENDORSEMENT')) {
    //User fullname Sr. Underwriter
    $dataRequest['YQP_USER_FULLNAME'] = "Sr. Underwriter";
    $dataRequest['END_COMMENTS_APPROVE_CONFIRMATION_ENDORSEMENT'] = "";
    $dataRequest['END_APPROVE_CONFIRMATION_UPLOAD_ENDORSEMENT'] = "";
    $dataRequest['END_FLOW_CONFIRMATION_ENDORSEMENT'] = "";
}
return $dataRequest;