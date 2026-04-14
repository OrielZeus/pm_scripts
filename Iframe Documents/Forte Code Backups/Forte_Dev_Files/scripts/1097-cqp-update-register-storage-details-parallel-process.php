<?php
    /*
    * This process is in charge of updating the request data in the parallel process in case the data is edited after its triggered, uses
    * https://github.com/ProcessMaker/docker-executor-php/blob/develop/docs/sdk/ProcessRequests.md#update-process-request as reference
    *
    * by Mateo Rada Arias
    * MOdify By Diego Tapia
    */
    $apiInstance = $api->processRequests();

    $processRequestId = $data["CQP_SUBPROCESS_ID"];
    $include = 'data';
    $processRequest = $apiInstance->getProcessRequestById($processRequestId, $include);
    $reqData = $processRequest->getData();

    $reqData["CQP_MARKETS"] = $data["CQP_MARKETS"];
    $reqData["CQP_INSURED_NAME"] = $data["CQP_INSURED_NAME"];
    $reqData["CQP_INCEPTION_DATE"] = $data["CQP_INCEPTION_DATE"];
    $reqData["CQP_EXPIRATION_DATE"] = $data["CQP_EXPIRATION_DATE"];

    $processRequestEditable = new \ProcessMaker\Client\Model\ProcessRequestEditable();
    $processRequestEditable->setData($data);

    $apiInstance->updateProcessRequest($processRequestId, $processRequestEditable);

    // If no errors are thrown, then the process request was successfully updated
    return ['success' => true];