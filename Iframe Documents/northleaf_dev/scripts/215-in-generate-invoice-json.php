<?php 
/**********************************
 * IN - Generate Invoice Json
 *
 * by Helen Callisaya
 * Modified by Favio Mollinedo
 *********************************/
require_once("/Northleaf_PHP_Library.php");

//Get global Variables
$apiHost = getEnv("API_HOST");
$apiToken = getEnv("API_TOKEN");
$apiSql = getEnv("API_SQL");
$apiUrl = $apiHost . $apiSql;

try {
    //Get Collections IDs
    $collectionsToSearch = array('IN_VENDORS_PROMPT', 'IN_VENDORS', 'IN_CURRENCY');
    $collectionsArray = getCollectionIdMaster($collectionsToSearch, $apiUrl, 'IN_MASTER_COLLECTION_ID');
    // Get the uploaded file and its associated image
    $fileId = $data['IN_INVOICE_VENDOR'];
    $imageList = is_string($data['IN_INVOICE_VENDOR_IMAGE']) ? json_decode($data['IN_INVOICE_VENDOR_IMAGE'], true) : $data['IN_INVOICE_VENDOR_IMAGE'];

    // SQL query to get the generic data
    $sqlGeneric = "SELECT c.data->>'$.NAME' as NAME,
                          c.data->>'$.PROMPT_IA' as PROMPT_IA,
                          c.data->>'$.POST_PROCESSING_SCRIPT_ID' as SCRIPT_ID       
                   FROM collection_" . $collectionsArray['IN_VENDORS_PROMPT'] . " AS c
                   WHERE c.data->>'$.NAME' = 'GENERIC'
                     AND c.data->>'$.STATUS' = 'ACTIVE'";
    // Execute the SQL query via the API
    $resGeneric = callApiUrlGuzzle($apiUrl, "POST", encodeSql($sqlGeneric));
    $promptIA = $resGeneric[0]['PROMPT_IA'];

    // Get the necessary data from the response
    $name = $resGeneric[0]['NAME'];
    $postProcessingScriptID = $postProcessingScriptID == "null" ? '' : $postProcessingScriptID;

    // Fetch the post-processing script using its ID
    $urlScript = $apiHost . '/scripts/' . $postProcessingScriptID;
    $responseScript = callApiUrlGuzzle($urlScript, "GET", []);
    $scriptPostProcessingCode = $responseScript['code'] ?? '';
    // Clean up the script code
    $scriptPostProcessingCode = str_replace(['<?php', '?>', 'return $formatedDataInvoice;'], "", $scriptPostProcessingCode);

    // Process each image
    for ($i = 0; $i < count($imageList); $i++) {
        // Fetch the file content for the current image
        $urlFile = $apiHost . "/files/" . $imageList[$i] . "/contents";
        $fileContent = callApiUrlGuzzle($urlFile, "GET", [], true);
        // Save the file content to a temporary file
        $pathTmpFile = "/tmp/" . $fileId . "_" . $i . ".jpeg";
        file_put_contents($pathTmpFile, $fileContent);
        // Prepare the data for the AI task
        $urlTaskAI =  $apiHost . '/package-ai/runGenieTest';
        $dataArray = [
            "configuration" => [
                "testData" => "{}",
                "responseFormat" => "JSON",
                "model" => "gpt-4o",
                "temperature" => 1,
                "max_tokens" => "4095",
                "stop" => [],
                "top_p" => 1,
                "frequency_penalty" => 0,
                "presence_penalty" => 0,
            ],
            "conversation" => [
                [
                    "id" => "lzu6sbywtitjf2wxj",
                    "role" => "user",
                    "content" => [
                        [
                            "type" => "image_url",
                            "image_url" => [
                                "url" => "data:image/jpeg;base64," . base64_encode($fileContent),
                            ],
                            "name" => "IN_INVOICE_VENDOR_01.jpeg",
                            "size" => 251516,
                            "file_type" => "image/jpeg",
                            "file_id" => "lzu6sbywtitjf2wxj",
                        ],
                    ],
                    "request_variable_name" => "",
                    "include_request_variable" => null,
                ],
                [
                    "id" => "lzu6sommke13wbitz",
                    "role" => "user",
                    "content" => [
                        [
                            "type" => "text",
                            "text" => $promptIA,
                        ],
                    ],
                ],
            ],
            "system_message" => [
                "role" => "system",
                "content" => [
                    [
                        "type" => "text",
                        "text" => "",
                    ],
                ],
            ],
            "promptSessionId" => "ps-kcs28l953k8x",
            "nonce" => 794953250210918,
        ];

        // Initialize cURL for API request
        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => $urlTaskAI,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => json_encode($dataArray),
            CURLOPT_HTTPHEADER => array(
                'Authorization: Bearer ' . $apiToken,
                'Content-Type: application/json'
            ),
        ));

        // Retry mechanism for the AI task
        $attempFlowGenie = 0;
        do {
            $attempFlowGenie++;
            $response = curl_exec($curl);
            if ($response === false) {
                $response = curl_error($curl);
            }
            $response = json_decode($response, true);
            $responseMessage = json_decode($response['result']['output'], true);
        } while (!isset($response['result']['output']) && $attempFlowGenie <= 5);

        try {
            // Store the response for the current image
            $flowGenieResponse['page_' . ($i + 1)] = $response;
            $outputAITask['page_' . ($i + 1)] = json_decode($response['result']['output'], true) ?? [];
            curl_close($curl);
        } catch (\Error | \Exception | \Throwable $e) {
            $flowGenieResponse['page_' . ($i + 1)] = [
                "error_catch" => $e->getMessage()
            ];
        }
        // Wait before processing the next image
        sleep(15);
    }
    
    // Prepare return data
    $dataReturn = [];
    $dataReturn["IN_INVOICE_VENDOR_DATA_PROCESS"] = $outputAITask;

    // Merge the output data into a single array
    $fileData = mergeRow($outputAITask);
    $dataReturn["IN_INVOICE_VENDOR_DATA"] = $fileData;
    $dataReturn["IN_INVOICE_TOTAL"] = $fileData['invoice_total_amount'];
    $dataReturn["IN_INVOICE_TAX_TOTAL"] = number_format((float)preg_replace('/[^\d.]/', '', $fileData['invoice_tax']), 2);
    //$dataReturn["IN_INVOICE_PRE_TAX"] = !empty($fileData['invoice_total_amount']) && !empty($fileData['invoice_tax']) ? number_format(((float)preg_replace('/[^\d.]/', '', $fileData['invoice_total_amount']) - (float)preg_replace('/[^\d.]/', '', $fileData['invoice_tax'])), 2, '.', ',') : null;
    $dataReturn["IN_INVOICE_DATE"] = $fileData['invoice_date'];
    $dataReturn["IN_INVOICE_NUMBER"] = $fileData['invoice_number'];
    $dataReturn["IN_INVOICE_CURRENCY"] = $fileData['invoice_currency_type'];

    // Retrieve vendor information
    $sqlVendors = "SELECT c.data->>'$.VENDOR_SYSTEM_ID_ACTG' as ID,   
                          CONCAT(c.data->>'$.VENDOR_LABEL', '|', c.data->>'$.EXPENSE_VENDOR_CURRENCY', '|', c.data->>'$.EXPENSE_VENDOR_NAME_CITY') as LABEL
                   FROM collection_" . $collectionsArray['IN_VENDORS'] . " AS c
                   WHERE c.data->>'$.VENDOR_STATUS' = 'Active'
                   ORDER BY LABEL ASC";
    $responseVendors = callApiUrlGuzzle($apiUrl, "POST", encodeSql($sqlVendors));
    //$dataReturn["IN_INVOICE_VENDOR"] = findFirstMatch($responseVendors, $outputAITask, 50);
    //Deleted by Ana Castillo to not fill the value requested by the client 2025-01-02
    //$dataReturn["IN_INVOICE_VENDOR"] = findBestMatchingVendor($outputAITask, $responseVendors);
    $dataReturn['PM_VENDOR_SOURCE'] = $responseVendors;
    
    //Get Currency data Source
    $sqlCurrency = "SELECT c.data->>'$.CURRENCY_ID' as ID,   
                           c.data->>'$.CURRENCY_LABEL' as LABEL
                    FROM collection_" . $collectionsArray['IN_CURRENCY'] . " AS c
                    WHERE c.data->>'$.CURRENCY_STATUS' = 'Active'";
    $responseCurrency = callApiUrlGuzzle($apiUrl, "POST", encodeSql($sqlCurrency));
    $dataReturn['PM_CURRENCY_SOURCE'] = $responseCurrency;

    //$dataReturn['IN_CASE_TITLE'] = $dataReturn['IN_INVOICE_VENDOR_LABEL'] . '-' . $dataReturn['IN_INVOICE_NUMBER'] . '-' . $dataReturn['IN_INVOICE_DATE'];
    $vendorLabel = $fileData['invoice_company'] ?? '(VENDOR NOT FOUND)';
    $dataReturn['IN_CASE_TITLE'] = strtoupper($vendorLabel) . ' - ' . $dataReturn['IN_INVOICE_NUMBER'] . ' - ' . $dataReturn['IN_INVOICE_DATE'];
    return $dataReturn;

} catch (\Exception | \Error $e) {
    // Return error details if any exception occurs
    return [
        'ERROR_DETAIL' => $e->getMessage(),
        "FILE_ID_UPLOAD_SND_ERROR" =>  $data['FILE_ID_UPLOAD_SND'] ?? 'empty',
    ];
}

/* 
 * Merge rows from the output list 
 *
 * @param array $list
 * @return array $mergedData 
 *
 * by Helen Callisaya 
 */
function mergeRow($list) {
    $mergedData = [];

    // Iterate through each page of output
    foreach ($list as $page) {
        foreach ($page as $key => $value) {
            // Only process fields if the value is not empty or different
            if (!isset($mergedData[$key]) || empty($mergedData[$key])) {
                $mergedData[$key] = $value;
            }
        }
    }
    return $mergedData;
}

/**
 * Function to find the first match of 'invoice_company' in the list
 * 
 * @param array $list
 * @param array $listPage
 * @param float $threshold
 * @return string $closestMatch 
 *
 * by Helen Callisaya 
 */
function findFirstMatch($list, $listPage, $threshold = 0) {
    // Iterate through each page in the invoice data
    foreach ($listPage as $page) {
        // Check if 'invoice_company' is set and not empty
        if (isset($page['invoice_company']) && !empty($page['invoice_company'])) {
            $company = $page['invoice_company'];
            $highestSimilarity = 0;
            $closestMatch = '';
            $idClosestMatch = '';

            // Compare 'invoice_company' with each name in the list
            foreach ($list as $item) {
                $similarity = 0;
                similar_text($company, $item['LABEL'], $similarity);
                if ($similarity > $highestSimilarity) {
                    $highestSimilarity = $similarity;
                    $closestMatch = $item['LABEL'];
                    $idClosestMatch = $item['ID'];
                }
            }

            // Check if the highest similarity exceeds the threshold
            if ($highestSimilarity >= $threshold) {
                return $idClosestMatch;
            }
        }
    }
    // Return an empty string if no match is found
    return '';
}
/**
 * Function to find the best match of 'invoice_company' in the list
 * 
 * @param array $vendors
 * @param array $listPage
 * @return string $bestMatch 
 *
 * by Favio Mollinedo 
 */
function findBestMatchingVendor($listPage, $vendors) {
    // Iterates through each page in the invoice data
    //foreach ($listPage as $page) {
        // Check if 'invoice_company' is set and not empty
        if (isset($listPage["page_1"]['invoice_company']) && !empty($listPage["page_1"]['invoice_company'])) {
            $inputWords = explode(' ', strtolower($listPage["page_1"]['invoice_company']));
            $bestMatch = null;
            $maxSequentialMatches = 0;

            foreach ($vendors as $vendor) {
                $vendorWords = explode(' ', strtolower($vendor['LABEL']));
                $sequentialMatches = 0;

                // Compare the same position
                foreach ($inputWords as $index => $word) {
                    if (isset($vendorWords[$index]) && $vendorWords[$index] === $word) {
                        $sequentialMatches++;
                    } else {
                        break; // Stops the comparison if there are no more matches
                    }
                }

                //  Update if there are more matches
                if ($sequentialMatches > $maxSequentialMatches) {
                    $maxSequentialMatches = $sequentialMatches;
                    $bestMatch = $vendor['ID'];
                }
            }
        }
    //}
    return $bestMatch;
}