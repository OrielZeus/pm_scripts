<?php 
/*
* This script cancel the subprocess in case of an declination of the contract
*
* by Diego Tapia
*/

//Define API
$apiInstanceRequest = $api->processRequests();

// Close Sub Process
$processRequest = $apiInstanceRequest->getProcessRequestById($data["CQP_SUBPROCESS_ID"], "data");
$dataNew = $processRequest->getData();
$dataNew['CQP_SUBPROCESS_STATUS'] = 'CANCEL';
$processRequestEditable = new \ProcessMaker\Client\Model\ProcessRequestEditable();
$processRequestEditable->setData($dataNew);
$apiInstanceRequest->updateProcessRequest($data["CQP_SUBPROCESS_ID"], $processRequestEditable);
$processRequestEditable->setStatus("CANCELED");
$apiInstanceRequest->updateProcessRequest($data["CQP_SUBPROCESS_ID"], $processRequestEditable);

 return [];