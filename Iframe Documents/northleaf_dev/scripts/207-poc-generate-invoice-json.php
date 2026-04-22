<?php 
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Request;

try {
    // Fetch environment variables for API configuration
    $apiHost = getEnv("API_HOST");
    $apiToken = getEnv("API_TOKEN");
    $apiSql = getEnv("API_SQL");
    $urlSQL = $apiHost . $apiSql;
    $collection = "collection_80";

    // Get the uploaded file and its associated image
    $file1 = $data['FILE1'];
    $image1 = $data['FILE1_IMAGE'];

    // SQL query to get the generic data
    $sqlGeneric = "SELECT c.data->>'$.NAME' as NAME,
                          c.data->>'$.PROMPT_IA' as PROMPT_IA,
                          c.data->>'$.POST_PROCESSING_SCRIPT_ID' as SCRIPT_ID       
                   FROM collection_49 AS c
                   WHERE c.data->>'$.NAME' = 'GENERIC'
                     AND c.data->>'$.STATUS' = 'ACTIVE'";
    // Execute the SQL query via the API
    $resGeneric = apiGuzzle($urlSQL, "POST", encodeSql($sqlGeneric));
    $promptIA = $resGeneric[0]['PROMPT_IA'];

    // Get the necessary data from the response
    $name = $resGeneric[0]['NAME'];
    $postProcessingScriptID = $postProcessingScriptID == "null" ? '' : $postProcessingScriptID;

    // Fetch the post-processing script using its ID
    $urlScript = $apiHost . '/scripts/' . $postProcessingScriptID;
    $responseScript = apiGuzzle($urlScript, "GET", []);
    $scriptPostProcessingCode = $responseScript['code'] ?? '';
    // Clean up the script code
    $scriptPostProcessingCode = str_replace(['<?php', '?>', 'return $formatedDataInvoice;'], "", $scriptPostProcessingCode);
    
    // Process each image
    for ($i = 0; $i < count($image1); $i++) {
        // Fetch the file content for the current image
        $urlFile = $apiHost . "/files/" . $image1[$i] . "/contents";
        $fileContent = apiGuzzle($urlFile, "GET", [], true);
        // Save the file content to a temporary file
        $pathTmpFile = "/tmp/" . $file1 . "_" . $i . ".jpeg";
        file_put_contents($pathTmpFile, $fileContent);
        //$promptIA = "Extract the following information from the attached image and return it in JSON format:\n\n\"invoice_date\"\n\"invoice_number\"\n\"invoice_total_amount\"\n\"invoice_company\"\n\"invoice_tax\"\n\"invoice_currency_type\"";
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
                            "name" => "QG_IMAGE_VENDORS_01.jpeg",
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
        //"text": "Extract the following information from the attached image and return it in JSON format:\n\n\"invoice_date\"\n\"invoice_number\"\n\"invoice_total_amount\"\n\"invoice_company\"\n\"invoice_tax\"\n\"invoice_currency_type\""
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
    //$dataReturn["FILE_1_ALL_DATA"] = $flowGenieResponse;
    $dataReturn["FILE_1_DATA_PROCESS"] = $outputAITask;

    // Merge the output data into a single array
    $file1Data = mergeRow($outputAITask);
    $dataReturn["FILE_1_DATA"] = $file1Data;
    $dataReturn["T1_TOTAL_AMOUNT"] = $file1Data['invoice_total_amount'];
    $dataReturn["T1_TAX"] = $file1Data['invoice_tax'];
    $dataReturn["T1_INVOICE_DATE"] = $file1Data['invoice_date'];
    $dataReturn["T1_INVOICE"] = $file1Data['invoice_number'];
    $dataReturn["T1_CURRENCY"] = $file1Data['invoice_currency_type'];
    
    // Retrieve vendor information
    $sqlvendors = "SELECT c.data->>'$.NAME' as NAME   
                    FROM collection_49 AS c
                    WHERE c.data->>'$.NAME' != 'GENERIC'
                    AND c.data->>'$.STATUS' = 'ACTIVE'";
    $responseVendors = apiGuzzle($urlSQL, "POST", encodeSql($sqlvendors));
    $dataReturn["VENDOR1"] = mostSimilar($responseVendors, $file1Data['invoice_company']);

    return $dataReturn;

} catch (\Exception | \Error $e) {
    // Return error details if any exception occurs
    return [
        'ERROR_DETAIL' => $e->getMessage(),
        "FILE_ID_UPLOAD_SND_ERROR" =>  $data['FILE_ID_UPLOAD_SND'] ?? 'empty',
    ];
}

/* 
 * Call Processmaker API with Guzzle
 *
 * @param (String) $url
 * @param (String) $requestType
 * @param (Array) $postfiles
 * @return (Array) $res
 *
 * by Elmer Orihuela 
 */
function apiGuzzle($url, $requestType, $postfiles, $contentFile = false)
{
    global $apiToken, $apiHost;
    $acceptType = $contentFile ? "'application/octet-stream'" : "application/json";
    
    // Set headers for the API request
    $headers = [
        "Accept" => $acceptType,
        'Authorization' => 'Bearer ' . $apiToken
    ];
    if ($contentFile === false) {
        $headers["Content-Type"] = "application/json";
    }
    
    // Create a Guzzle client
    $client = new Client([
        'base_uri' => $apiHost,
        'verify' => false,
    ]);
    // Create a Guzzle request
    $request = new Request($requestType, $url, $headers, json_encode($postfiles));
    try {
        // Send the request and wait for the response
        $res = $client->sendAsync($request)->wait();
        $res = $res->getBody()->getContents();
    } catch (Exception $exception) {
        // Handle any exceptions and capture the response body
        $responseBody = $exception->getResponse()->getBody(true);
        $res = $responseBody;
    }
    // Decode the response if it's not a file content
    if ($contentFile === false) {
        $res = json_decode($res, true);
    }
    return $res;
}

/*
 * Encode SQL
 *
 * @param (String) $string
 * @return (Array) $variablePut
 *
 * by Elmer Orihuela
 */
function encodeSql($string)
{
    // Encode the SQL query to base64
    $variablePut = [
        "SQL" => base64_encode($string)
    ];
    return $variablePut;
}

// Function to merge rows from the output list
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

// Function to find the most similar vendor name
function mostSimilar($array, $value) {
    $mostSimilar = '';
    $highestSimilarity = 0;

    // Iterate over the array and calculate similarity
    foreach ($array as $item) {
        $name = $item["NAME"];
        // Calculate similarity percentage
        similar_text(strtoupper($value), strtoupper($name), $percent);
        
        // Update if the similarity is higher than previously found
        if ($percent > $highestSimilarity) {
            $highestSimilarity = $percent;
            $mostSimilar = $name;
        }
    }

    // Check if a similar value was found
    if ($highestSimilarity < 30) { // Adjust the similarity threshold as needed
        $mostSimilar = ''; // Return empty if no significant matches
    }
    return $mostSimilar;
}