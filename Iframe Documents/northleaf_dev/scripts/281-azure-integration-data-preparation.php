<?php 

$analysisType = $data['_parent']['config']['analysisType'];
$filesList    = $data['_parent']['config']['filesList'];
$reqId        = $data['_parent']['request_id'];
$intTimeGral  = ($data['intTimeGral'] != null) ? $data['intTimeGral'] : microtime(true);

$sizeFiles      = 0;
$paginationSize = 10;
$loopData       = [];

if($data['toAnalize'] != null){
    $toAnalize      = $data['toAnalize'];
    $filesToAnalize = $data['filesToAnalize'];
}
else{
    if($analysisType == "single_file"){
        $toAnalize = $data[$filesList];
        if(!is_array($toAnalize)){
            $toAnalize = [
            $toAnalize
            ];
        }
        $sizeFiles = 1;
    }
    if($analysisType == "specific_files"){
        $toAnalize = $data[$filesList];
        $sizeFiles = sizeof($data[$filesList]);
    }

    if($analysisType == 'all_files'){
        $apiInstance     = $api->requestFiles();
        $requestFiles    = $apiInstance->getRequestFiles($reqId)->getData();
        $requestFilesArr = json_decode(json_encode($requestFiles), true);
        $toAnalize       = $requestFilesArr;
        $sizeFiles       = sizeof($requestFilesArr);
    }
    $filesToAnalize = $toAnalize;
}




if(sizeof($toAnalize) > 0){
    for($i = 1;$i <= 3;$i++){
        $splitFiles = array_splice($toAnalize, 0, $paginationSize);
        $loopData['loop'.$i] = $splitFiles;
        if(sizeof($toAnalize) == 0){
            break;
        }
    }
}


return [
    "filesToAnalize" => $filesToAnalize,
    "loopData" => $loopData,
    "toAnalize" => $toAnalize,
    "intTimeGral" => $intTimeGral
];