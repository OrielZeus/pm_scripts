<?php 


$apiInstanceCollection = $api->collections();

$record = new \ProcessMaker\Client\Model\RecordsEditable();
$recordData = [
    "payload" => $data
];

$record->setData($recordData);

$result = $apiInstanceCollection->createRecord(100, $record);
$result = $result->getId();

 return ["request_id" => $result];