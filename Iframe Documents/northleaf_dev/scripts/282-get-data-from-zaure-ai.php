<?php 
ini_set('memory_limit', '-1');
use GuzzleHttp\Client;
$server = getenv('HOST_URL');
$host = getenv('API_HOST');

$client = new Client([
  'base_uri' => $server,
  'verify' => false,
]);

$azureServer          = $data['_parent']['config']['azureServer'];
$azureModelId         = $data['_parent']['config']['azureModelId'];
$azureApiVer          = $data['_parent']['config']['azureApiVer'];
$azureKey             = $data['_parent']['config']['azureKey'];
$analysisType         = $data['_parent']['config']['analysisType'];
$filesList            = $data['_parent']['config']['filesList'];

$timeLoop             = $data['_parent']['config']['loopTime'];
$advancedMode         = $data['_parent']['config']['advancedMode'];
$confJson             = $data['_parent']['config']['confJson'];
$postProcessingScript = $data['_parent']['config']['postProcessingScript'];
$reqId                = $data['_parent']['request_id'];

$maxTimeExec  = 600000;

//return trim($confJson, "'");
//$confJson = '{   "VENDORNAME": {     "type": "string",     "key": "VendorName",     "filter": "content",     "replace": {       "Valvoline.": "Valvoline",       "Norco": "Norco Inc",       "LSCO": "ALSCO",       "ZIll Auto Zone": "Auto Zone",       "CARQUESTAUTO PARTS" : "Next level Parts Inc"     }   },   "VENDORADDRESS": {     "type": "string",     "key": "VendorAddress"   },   "INVNR": {     "type": "string",     "key": "InvoiceId"   },   "INVDATE": {     "type": "string",     "key": "InvoiceDate"   },   "PO_NR": {     "type": "string",     "key": "PurchaseOrder",     "filter": {       "type": "regex",       "value": "/\\\d[a-z]?/"     }   },   "TOTAL": {     "type": "string",     "key": "InvoiceTotal"   },   "TERMS": {     "type": "string",     "key": "PaymentTerm"   },   "DUEDATE": {     "type": "string",     "key": "DueDate"   } }';

$confJson = (json_decode(trim(($confJson), "'"), true));
//return $confJson;

$dataReturn = [];
$indexFile  = 1;

$indexScript = $config['indexScript'];
$indexLoop = 'loop'.$indexScript;
$toAnalize = $data['loopData'][$indexLoop];
$toAnalize = ($toAnalize != null) ? $toAnalize : [];
if($toAnalize != null){
  //$curl = curl_init();
  $apiInstanceFile = $api->files();
  $apiInstance     = $api->requestFiles();
  $requestFiles    = $apiInstance->getRequestFiles($reqId)->getData();
  $requestFilesArr = json_decode(json_encode($requestFiles), true);


  foreach($toAnalize as $file_id){
    $allTime = 0;
    $file_id = ($analysisType == 'all_files') ? $file_id["id"] : $file_id;
    $filter = array_search($file_id, array_column($requestFilesArr, 'id'));
    if($analysisType !== 'all_files' || $filter !== false){
      $file = $requestFiles[$filter];
      if($analysisType == 'all_files'){
        $fileTemp = $apiInstance->getRequestFilesById($reqId, $file_id);
        $fileName = $file->getFileName();
        $mimeFile = $file->getMimeType();
        $fileContents = new CURLFile($fileTemp->getPathname(), $mimeFile, $fileName);
      } 
      else{
        
        $reqIdFile = $apiInstanceFile->getFileById( $file_id);
        $reqsId = $reqIdFile['model_id'];
        $mimeFile = $reqIdFile['mime_type'];
        $fileTemp = $apiInstance->getRequestFilesById($reqsId, $file_id);
        $fileName = $fileTemp->getFileName();
        $fileContents = new CURLFile($fileTemp->getPathname(), $mimeFile, $fileName);
      }
      
      $url = $azureServer . "/documentintelligence/documentModels/" . $azureModelId . ":analyze?api-version=" . $azureApiVer; 
      $uploadFile = callCurl($azureKey,$url, 'POST',$fileContents);
      $modelID = getStringBetweenParams($uploadFile,"apim-request-id: ","\r\n");
      $status = 'error';
      $finalResponse = [];

      $breakLoop = false;
      $dataFile = [];
      $initTimeStamp = microtime(true);
      while($breakLoop === false){
        $allTime += $timeLoop;
        sleep($timeLoop / 1000);
        $url = $azureServer . "/documentintelligence/documentModels/" . $azureModelId . "/analyzeResults/". $modelID . "?api-version=" . $azureApiVer; 
        $analizeFile = callCurl($azureKey,$url, 'GET');
        $finalResponse = json_decode($analizeFile);
        $status = $finalResponse->status;
        $breakLoop = ($allTime >= $maxTimeExec || $status == 'succeeded') ? true : false;
        $status = ($allTime >= $maxTimeExec) ? "Analysis time exceeded 60 seconds." : $status;
      }
      $endTimeStamp = microtime(true) - $initTimeStamp;
      if($status == 'succeeded'){
        $allFields = $finalResponse->analyzeResult->documents[0]->fields;
        if(is_array($confJson)){
          $dataFile = validateResponse($allFields,$confJson);
        }
        else{
          $dataFile = withoutValidation($allFields);
        }
        //return $postProcessingScript;
        if($postProcessingScript != null){
          $fileData = [ 
            "field"  => $dataFile,
            "pages"  => $finalResponse->analyzeResult->pages,
            "tables" => $finalResponse->analyzeResult->tables
          ];
          
          

          $dataFile = executeFilter($fileData,'script',$postProcessingScript);
        }

        //$items = $finalResponse->analyzeResult->documents[0]->fields->Items->valueArray;
        /*$allFields = $finalResponse->analyzeResult->documents[0]->fields;
        $dataFile['VENDORNAME'] = isset($allFields->VendorName) ? $allFields->VendorName->content : '';
        $dataFile['VENDORADDRESS'] = isset($allFields->VendorAddress) ? $allFields->VendorAddress->content : '';
        $dataFile['INVNR']      = isset($allFields->InvoiceId) ? $allFields->InvoiceId->content : '';
        $dataFile['INVDATE']    = isset($allFields->InvoiceDate) ? $allFields->InvoiceDate->content : '';
        $dataFile['PO_NR']      = isset($allFields->PurchaseOrder) ? $allFields->PurchaseOrder->content : '';
        $dataFile['TOTAL']      = isset($allFields->InvoiceTotal) ? $allFields->InvoiceTotal->content : '';
        $dataFile['TERMS']      = isset($allFields->PaymentTerm) ? $allFields->PaymentTerm->content : '';
        $dataFile['DUEDATE']    = isset($allFields->DueDate) ? $allFields->DueDate->content : '';
        */
        //$dataFile['loop']    = 'loop'.$indexScript . " " . date('H:i:s');        
      }  
      $dataReturnFile['fileID']    = $file_id;
      $dataReturnFile['timeStamp'] = $endTimeStamp;
      $dataReturnFile['response']  = $dataFile;
      $dataReturnFile['row_id']     = $indexFile;
      $dataReturnFile['status']     = $status;
      $dataReturn['responseFile'.$file_id] = $dataReturnFile;
      $indexFile++;
    }
  }
}
//$dataReturn['review-loop'.$indexScript] = $toAnalize;
return $dataReturn;


function postProcessing($lineData,$key,$label){
  $keys  = array_column($lineData, $key);
  if(is_array($keys)){
    $index = array_search($label, $keys);
    return $index;
  }
  return null;
}

  

function callCurl($azureKey,$url,$method, $postfields = ''){
    try{
    $header = 0;
    $files  = '';
    if($postfields != ''){
      $header = 1;
      $files = array('urlSource'=> $postfields);
    }
    $curl = curl_init();
    curl_setopt_array($curl, array(
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HEADER => $header,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => $method,
        CURLOPT_POSTFIELDS => $files,
        CURLOPT_HTTPHEADER => array(
            'Content-Type: application/octet-stream',
            'Ocp-Apim-Subscription-Key: ' . $azureKey
        ),
    ));
    $response = curl_exec($curl);
    curl_close($curl);
    return $response;
    }
    catch (Exception $e) {
    return  'Caught exception: '.  $e->getMessage(). "\n";
}
}

function getStringBetweenParams($string, $start, $end){
  $string = ' ' . $string;
  $ini = strpos($string, $start);
  if ($ini == 0) return '';
  $ini += strlen($start);
  $len = strpos($string, $end, $ini) - $ini;
  return substr($string, $ini, $len);
}


function withoutValidation($allFields){
  $dataToReturn = [];
  foreach($allFields as $key => $obj) {
    if($obj->type == "array" or $obj->type == "object"){
      if($obj->type == "array"){
        $dataToReturn[$key] = withoutValidation($obj->valueArray);
      }
      if($obj->type == "object"){
        $dataToReturn[$key] = withoutValidation($obj->valueObject);
      }
    }
    else{
      $dataToReturn[$key] = str_replace("\n",'', $obj->content);
    }
  }
  return $dataToReturn;
}

function validateDate($date, $format)
{
    $d = DateTime::createFromFormat($format, $date);
    return $d && $d->format($format) == $date;
}

function validateField($type,$value,$format=''){
  switch ($type) {
    case 'alphanumeric':
      return ctype_alnum($value) ? $value : ''; 
      break;
    case 'numeric':
      return is_numeric($value) ? $value : ''; 
      break;
    case 'date':
      $format = ($format != "" && $format != null) ? $format : 'Y-m-d H:i:s';
      return validateDate($value, $format) ? $value : '';
      break;
    case 'float':
      return is_float($value) ? $value : '';
      break;
    default:
      return $value;
  }
}

function replaceValues($value,$objValues){
  $value = isset($objValues->{$value}) ?  $objValues->{$value} : $value;
  $value = isset($objValues[$value]) ?  $objValues[$value] : $value;
  return $value;
}



function executeFilter($value,$type,$filter){
  global $client,$host;

  switch ($type) {
    case 'script':
      //$host = getenv('API_HOST');
      $executorID = $filter;
      try {
        $responseScript = $client->get(
          $host . "/execution/script/" . $executorID,
          [
            'headers' => [],
            'body' => json_encode([
              'data' => $value
            ])
          ]
        );
        $value = json_decode($responseScript->getBody()->getContents(), true);
      }
      //catch exception
      catch(Exception $e) {
        $value = "Script Error - " . $e->getMessage();
      }
      break;
    case 'regex':
      $value = preg_match($filter, $value) ? $value : "";
      break;
  }
  return $value;
}

function validateResponse($allFields,$confJson,$maintype = ''){
  $dataToReturn = [];

  if($maintype == 'array'){
    foreach($allFields as $index => $rowData){
      $type = $rowData->type;
      if($type == "array" or $type == "object"){
        $keyField = $type == "array" ? "valueArray" : "valueObject";
        $dataArrObj = isset($rowData->{$keyField}) ? $rowData->{$keyField} : '';
      }
      if($type == 'array')
        $dataToReturn[$index] = validateResponse($dataArrObj,$confJson,'array');
      else
        $dataToReturn[$index] = validateResponse($dataArrObj,$confJson);
    }
  }
  else{
    foreach($confJson as $fieldId => $fieldData){
      $filter = (isset($fieldData['filter']) && !is_object($fieldData['filter'])) ? $fieldData['filter'] : 'content'; 
      $format = isset($fieldData['format']) ? $fieldData['format'] : '';
      $type   = isset($fieldData['type']) ? $fieldData['type'] : '';
      if($type == "array" or $type == "object"){
        $keyField = $type == "array" ? "valueArray" : "valueObject";
        $dataArrObj = isset($allFields->{$fieldData['key']}->{$keyField}) ? $allFields->{$fieldData['key']}->{$keyField} : '';
        if($type == "array")
          $dataToReturn[$fieldId] = validateResponse($dataArrObj,$fieldData['fields'],'array');
        else
          $dataToReturn[$fieldId] = validateResponse($dataArrObj,$fieldData['fields']);
      }
      else{
        $docVal = isset($allFields->{$fieldData['key']}->{$filter}) ? $allFields->{$fieldData['key']}->{$filter} : '';
        $docVal = str_replace("\n",'', $docVal);
        $value  = ($type == 'string') ? $docVal : validateField($type,$docVal,$format);
        $value  = (isset($fieldData['replace'])) ? replaceValues($value,$fieldData['replace']) : $value;

        $dataToReturn[$fieldId] = $value;
      }
      $externalFilter = $fieldData['filter'];
      if(is_array($externalFilter)){
        $filter = 'content';
        //return [$filter,$fieldData['key']];
        $docVal = isset($allFields->{$fieldData['key']}->{$filter}) ? $allFields->{$fieldData['key']}->{$filter} : '';
        $docVal = str_replace("\n",'', $docVal);
        $filterType = $externalFilter['type'];
        $filterVal  = $externalFilter['value'];
        $dataToReturn[$fieldId] = executeFilter($docVal,$filterType,$filterVal);
      }
    }
  }
  return $dataToReturn;










  /*
  if(isset($confJson['fields'])){
    foreach($confJson['fields'] as $key => $fieldName){
      $dataToReturn[$fieldName] = $allFields[$key]->content;
    }
  }
  if(isset($confJson['grids'])){
    foreach($confJson['grids'] as $gridName => $arrFields){
      if($allFields[$gridName]->type == 'array')
        $gridType = 'valueArray';
      if($allFields[$gridName]->type == 'object')
        $gridType = 'valueObject';
      foreach($allFields[$gridName][$gridType] as $row){
        $dataRow = [];
        foreach($arrFields as $column){
          if($row->type == 'array')
            $rowType = 'valueArray';
          if($row->type == 'object')
            $rowType = 'valueObject';
          $dataRow[$column] = $row[$rowType][$column]->content;
        }
        $dataToReturn[$gridName][] = $dataRow;
      }
    }
  }*/
}