<?php
/**********************************
 * Sign Configuration - Upload File
 *
 * by Elmer Orihuela
 *********************************/
require_once("/Northleaf_PHP_Library.php");

// Retrieve environment variables for API
$apiToken = getenv('API_TOKEN');
$apiHost = getenv('API_HOST');
$apiSql = getenv('API_SQL');

// Retrieve server host URL
$host = $_SERVER["HOST_URL"];
$sqlUrl = $apiHost . $apiSql;

// Set default values for IDs
$signatureGroupId = getenv('SIGN_CONF_SIGNATURE_GROUP_ID');
$publicFileRequestId = getenv('SIGN_CONF_PUBLIC_FILE_REQUEST_ID');
$publicFolderId = getenv('SIGN_CONF_PUBLIC_FOLDER_ID');

// Validate the required data structure and presence
if (!isset($data) || !is_array($data) || !isset($data['dataCase'])) {
    return ["Error" => "Data not properly defined"];
}

// Check if dataCase is provided
if (empty($data['dataCase'])) {
    return ["Error" => "Signature Information not found"];
}
// Extract signature details
$signatureIdToUpdate = $data['dataCase']['id'];
$signatureName = $data['dataCase']['USER_ID'] . "_" . $data['dataCase']['FIRST_NAME'] . "_" . $data['dataCase']['LAST_NAME'];
$signatureUserId = $data['dataCase']['USER_ID'];

$fundingApprovalRequired = $data['fundingApprovalRequired'];
$typeOfSigner = $data['typeOfSigner'];
// Define the file type and base64 format
if (!empty($data['signature'])) {    
    // Check if signature file type is provided
    if (empty($data['fileType'])) {
        return ["Error" => "Signature file type not found"];
    }
    $fileType = $data['fileType'];
    $signatureEncoded = $data['signature'];
    $signatureBaseFormat = "data:" . $fileType . ";base64," . $signatureEncoded;
} else {
    if (!empty($data['dataCase']['SIGNATURE_BASE64'])) {
        $signatureBaseFormat = $data['dataCase']['SIGNATURE_BASE64'];
    } else {
        $signatureBaseFormat = "";
    }
}

// Prepare the endpoint URL and data
$endpointUrl = $apiHost . "/files";
$endpointUrl .= '?model_id=' . $publicFileRequestId . '&model=ProcessRequest&data_name=' . urlencode($signatureName) . '&collection=default';
if (empty($fileType)) {
    $fileExtension = 'png';
    if (isset($data['dataCase']['SIGNATURE_BASE64']) && !empty($data['dataCase']['SIGNATURE_BASE64'])) {
        preg_match('/^data:image\/(\w+);base64,/', $data['dataCase']['SIGNATURE_BASE64'], $matches);
        if (!empty($matches[1])) {
            $fileExtension = $matches[1]; 
        }
    } 
} else {
    $fileExtension = explode('/', $fileType)[1];
}
$filename = $signatureName . '.' . $fileExtension;

try {
    // Call the API to create the file
    $apiInstance = $api->files();
    $fileContent = base64_decode($signatureEncoded);
    $filePath = '/tmp/' . $filename;
    file_put_contents($filePath, $fileContent);

    // Create the file using the API
    $newFile = $apiInstance->createFile($publicFileRequestId, 'ProcessMaker\Models\ProcessRequest', $signatureName, 'default', $filePath);
    $signatureFileId = $newFile['id'];
    $fileUrl = $apiHost . "/files/" . $signatureFileId;

    // Retrieve the file URL information
    $responseFileUrlInformation = callApiUrlGuzzle($fileUrl, 'GET', []);
    $fileUrlOriginalUrl = $responseFileUrlInformation['original_url'];

    if (isset($fileUrlOriginalUrl) && !empty($fileUrlOriginalUrl)) {
        // Update the `signature` table
        $fundingApprovalRequiredQuery = $fundingApprovalRequired ? 'true' : 'false';
        $updateSignatureQuery = "UPDATE SIGNATURE_CONFIGURATION 
                                 SET SIGNATURE_URL = '$fileUrlOriginalUrl', 
                                     SIGNATURE_BASE64 = '$signatureBaseFormat', 
                                     CONFIGURED = true, 
                                     SIGNATURE_STATUS = 'ACTIVE', 
                                     fundingApprovalRequired = " . $fundingApprovalRequiredQuery . ", 
                                     typeOfSigner = '$typeOfSigner'  
                                 WHERE id = $signatureIdToUpdate";
        callApiUrlGuzzle($sqlUrl, 'POST', encodeSql($updateSignatureQuery));

        // Move Signature into Signature public folder
        $updateSignatureMediaQuery = "UPDATE media SET custom_properties = JSON_SET(custom_properties, '$.parent', $publicFolderId) WHERE id = $signatureFileId";
        callApiUrlGuzzle($sqlUrl, 'POST', encodeSql($updateSignatureMediaQuery));

        // Check if the meta field is null
        $checkMetaQuery = "SELECT meta FROM users WHERE id = $signatureUserId";
        $metaResult = callApiUrlGuzzle($sqlUrl, 'POST', encodeSql($checkMetaQuery));

        if ($metaResult && $metaResult['meta'] === null) {
            // If the meta field is null, perform an initial INSERT
            $initialMetaQuery = sprintf(
                "UPDATE users SET meta = JSON_OBJECT('signature', '%s', 'fundingApprovalRequired', %s, 'typeOfSigner', '%s') WHERE id = %d",
                addslashes($signatureBaseFormat),
                $fundingApprovalRequired ? 'true' : 'false',  // Convert boolean to string for SQL
                addslashes($typeOfSigner),
                intval($signatureUserId)
            );
            callApiUrlGuzzle($sqlUrl, 'POST', encodeSql($initialMetaQuery));
        } else {
            // If the meta field is not null, perform an UPDATE
            $updateSignatureMediaQuery = sprintf(
                "UPDATE users SET meta = JSON_SET(meta, '$.signature', '%s', '$.fundingApprovalRequired', '%s', '$.typeOfSigner', '%s') WHERE id = %d",
                $signatureBaseFormat,
                $fundingApprovalRequired ? 'true' : 'false',  // Convert boolean to string for SQL
                $typeOfSigner,
                intval($signatureUserId)
            );
            callApiUrlGuzzle($sqlUrl, 'POST', encodeSql($updateSignatureMediaQuery));
        }

        return ["Success" => "Signature updated successfully"];
    } else {
        return ["Error" => "Failed to upload signature"];
    }
} catch (\GuzzleHttp\Exception\RequestException $e) {
    return ["Error" => "Request failed: " . $e->getMessage()];
}