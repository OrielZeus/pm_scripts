<?php 
/*  
 *  Welcome to ProcessMaker 4 Script Editor 
 *  To access Environment Variables use getenv("ENV_VAR_NAME") 
 *  To access Request Data use $data 
 *  To access Configuration Data use $config 
 *  To preview your script, click the Run button using the provided input and config data 
 *  Return an array and it will be merged with the processes data 
 *  Example API to retrieve user email by their ID $api->users()->getUserById(1)['email'] 
 *  API Documentation https://github.com/ProcessMaker/docker-executor-php/tree/master/docs/sdk 
 */

$toAnalize      = $data['toAnalize'];
$filesToAnalize = $data['filesToAnalize'];
$analysisType   = $data['_parent']['config']['analysisType'];
$azureResult    = ($data['azureResult'] != null) ? $data['azureResult'] : [];

if(is_array($filesToAnalize)){
    foreach($filesToAnalize as $file){
        $file = ($analysisType == 'all_files') ? $file['id'] : $file;
        if($data['responseFile'.$file] != null){
            $filter = array_search($file, array_column($azureResult, 'fileID'));
            if($filter === false){
                $azureResult[] = $data['responseFile'.$file];
            }
        }
    }
}
$timeStampGral = '';
$breakLoop = (is_array($toAnalize) AND sizeof($toAnalize) > 0) ? false : true;
if($breakLoop){
    $timeStampGral = microtime(true) - $data['intTimeGral'];    
}

$documents = $azureResult;
foreach ($documents as $document) {
    $requestData = $document['response'];
}



return [
    "breakLoop" => $breakLoop,
    "azureResult" => $azureResult,
    "timeStampGral" => $timeStampGral,
    //'IN_INVOICE_VENDOR' => $requestData['IN_INVOICE_VENDOR'], -- Deleted by Ana Castillo requested by the client
    'IN_INVOICE_DATE' => $requestData['IN_INVOICE_DATE'],
    'IN_INVOICE_NUMBER' => $requestData['IN_INVOICE_NUMBER'],
    'IN_INVOICE_TAX_TOTAL' => standardizeCurrency($requestData['IN_INVOICE_TAX_TOTAL']),
    'IN_INVOICE_TOTAL' => standardizeCurrency($requestData['IN_INVOICE_TOTAL']),
];


function standardizeCurrency($value) {
    $value = preg_replace('/[^\d,.-]/', '', $value);
    if (strpos($value, ',') !== false) {
        $value = preg_replace('/,/', '.', substr($value, 0, strrpos($value, ',')) . substr($value, strrpos($value, ',')));
    }
    $value = preg_replace('/[.,](?=\d{3})/', '', $value);
    return (float)$value;
}