<?php 

/*****************************************
* Clone data from other request
*
* by Diego Tapia
* Modified by Adriana Centellas
*****************************************/

use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Request;
require_once("/CQP_Generic_Functions.php");

// Set manual replace variables and values
$manualFiles = [
    [
        "old_var" => "CQP_NEW_FILE_UPLOAD",
        "new_var" => "CQP_SLIP"
    ],
    [
        "old_var" => "CQP_REPLACE_SLIP",
        "new_var" => "CQP_SLIP"
    ]
];

$manualVariables = [
    // Copy the validation variable of the file revision
    [
        "original" => "CQP_SLIP",
        "copy" => "CQP_GEMINI_FILE_VALIDATE",
        "condition" => [
            "variable" => "CQP_BROKER_STATUS",
            "value" => "OPEN"
        ]
    ],
    [
        "original" => "CQP_NEW_FILE_UPLOAD",
        "copy" => "CQP_GEMINI_FILE_COMPARE",
        "condition" => [
            "variable" => "CQP_BROKER_STATUS",
            "value" => "OPEN"
        ]
    ]
];
  
$cleanVariables = [
    [
        "cleanVar" => "CQP_NUMBER_OF_PERIODS",
        "conditionNeeded" => true,
        "condition" => [
            "variable" => "CQP_ACTION",
            "value" => "RENEWAL"
        ]

    ],
    [
        "cleanVar" => "CQP_SUMMARY_CLAIMS",
        "conditionNeeded" => true,
        "condition" => [
            "variable" => "CQP_ACTION",
            "value" => "RENEWAL"
        ]

    ],
    [
        "cleanVar" => "CQP_NUMBER_OF_PERIODS_PERIOD",
        "conditionNeeded" => true,
        "condition" => [
            "variable" => "CQP_ACTION",
            "value" => "RENEWAL"
        ]

    ],
    [
        "cleanVar" => "ROWS",
        "conditionNeeded" => true,
        "condition" => [
            "variable" => "CQP_ACTION",
            "value" => "RENEWAL"
        ]

    ],
    [
        "cleanVar" => "AS_IF",
        "conditionNeeded" => true,
        "condition" => [
            "variable" => "CQP_ACTION",
            "value" => "RENEWAL"
        ]

    ],
    [
        "cleanVar" => "CQP_GEMINI_RESPONSE_SLIP",
        "conditionNeeded" => true,
        "condition" => [
            "variable" => "CQP_ACTION",
            "value" => "RENEWAL"
        ]

    ],
    [
        "cleanVar" => "CQP_GEMINI_RESPONSE_NEW",
        "conditionNeeded" => true,
        "condition" => [
            "variable" => "CQP_ACTION",
            "value" => "RENEWAL"
        ]

    ],
    [
        "cleanVar" => "CQP_GEMINI_FILE_VALIDATE",
        "conditionNeeded" => true,
        "condition" => [
            "variable" => "CQP_ACTION",
            "value" => "RENEWAL"
        ]

    ],
    [
        "cleanVar" => "CQP_GEMINI_FILE_COMPARE",
        "conditionNeeded" => true,
        "condition" => [
            "variable" => "CQP_ACTION",
            "value" => "RENEWAL"
        ]

    ],
    [
        "cleanVar" => "CQP_NEW_FILE_UPLOAD",
        "conditionNeeded" => true,
        "condition" => [
            "variable" => "CQP_ACTION",
            "value" => "RENEWAL"
        ]

    ],
    [
        "cleanVar" => "CQP_INSTALLMENTS_DATE",
        "conditionNeeded" => false,
        "condition" => []
    ],
    [
        "cleanVar" => "CQP_N_INSTALLMENTS",
        "conditionNeeded" => false,
        "condition" => []
    ],
    [
        "cleanVar" => "CQP_FILE_LIST",
        "conditionNeeded" => false,
        "condition" => []
    ],
    [
        "cleanVar" => "CQP_CLONE_REQUEST_STARTER",
        "conditionNeeded" => false,
        "condition" => []
    ],
    [
        "cleanVar" => "CQP_ADOBE_WORKFLOW_SELECTED",
        "conditionNeeded" => false,
        "condition" => []
    ],
    [
        "cleanVar" => "CQP_ADOBE_WORKFLOW_DOCUMENTS",
        "conditionNeeded" => false,
        "condition" => []
    ],
    [
        "cleanVar" => "CQP_ADOBE_WORKFLOW_LIST_SELECT",
        "conditionNeeded" => false,
        "condition" => []
    ],
    [
        "cleanVar" => "CQP_ADOBE_WORKFLOW_LIST",
        "conditionNeeded" => false,
        "condition" => []
    ],
    [
        "cleanVar" => "CQP_STATUS",
        "conditionNeeded" => false,
        "condition" => []
    ],
    [
        "cleanVar" => "CQP_REPLACE_SLIP",
        "conditionNeeded" => false,
        "condition" => []
    ],
    [
        "cleanVar" => "CQP_HIDE_SAVE",
        "conditionNeeded" => false,
        "condition" => []
    ],
    [
        "cleanVar" => "CQP_CURRENT_STATUS_GREEMENT",
        "conditionNeeded" => false,
        "condition" => []
    ]
];

// Set initial variables
$apiInstanceFiles = $api->requestFiles();
$apiHost = getenv('API_HOST');
$headers = [
    "Content-Type" => "application/json",
    "Accept" => "application/json",
    "Authorization" => "Bearer " . getenv('API_TOKEN')
];

$client = new Client([
    'verify' => false,
    'defaults' => ['verify' => false]
]);    

// Get Clone request information
$pmql = "";
$getFromCollection = false;


if ($data["CQP_ACTION"] == "CLONE") {
    $pmql = 'id = ' . $data["CQP_INSURED_CLONE"]["CQP_REQUEST_ID"];
} elseif ($data["CQP_ACTION"] == "RENEWAL") {
    if ($data["CQP_CLONE_RENEWAL_ORIGIN"] != "requests") {
        $getFromCollection = true;
    } else {
        $pmql = 'id = ' . $data["CQP_CLONE_RENEWAL_ID"];
    }
} else {
    $pmql = 'data.CQP_INSURED_NAME="' . $data["CQP_INSURED_NAME"] . '"' ;
}

if ($getFromCollection) {
     $sQCollectionsId = "
        SELECT data
        FROM " . $data["CQP_CLONE_RENEWAL_ORIGIN"]  . "
        WHERE id = " . $data["CQP_CLONE_RENEWAL_ID"];

    $unionArray = json_decode(getSqlData("POST", $sQCollectionsId)[0]["data"], true);
    $unionArray["CQP_RENEWAL_CARGO"] = "YES";
    $unionArray["CQP_BROKER_STATUS"] = "OPEN";
    $unionArray["CQP_STORAGE_EXPOSURE"] = [];
    $unionArray["CQP_STORAGE_EXPOSURE"][] = [];
    $unionArray["CQP_STORAGE_EXPOSURE"][0] = [
        "CQP_STORAGE_AGG" => $unionArray["CQP_STORAGE_AGG"],
        "CQP_STORAGE_EEL" => $unionArray["CQP_STORAGE_EEL"],
    ];
    $unionArray["CQP_SUMMARY_DETAILS"] = [];
    $unionArray["CQP_SUMMARY_DETAILS"][] = [];
    $unionArray["CQP_SUMMARY_DETAILS"][0] = [
        "CQP_TAX_USD" => $unionArray["CQP_TAX_USD_IF_APPLAY"],
        "CQP_UNDERWRITING_EXPENSES_USD" => $unionArray["CQP_UNDERWRITING_EXPENSES"]
    ];
} else {
    $response = $client->request('GET', $apiHost .'/requests?pmql=(' . $pmql . ' and id != ' . $data["_request"]["id"] . ')&page=1&per_page=1&include=data&order_by=id&order_direction=DESC&advanced_filter=[{"subject":{"type":"Field","value":"name"},"operator":"=","value":"Cargo Quotation Process"}]', [
        'headers' => $headers
    ]);

    $requests = json_decode($response->getBody()->getContents(), true);
    $unionArray = ["CQP_CLONED_DATA" => false];
    
    if (count($requests["data"]) > 0) {
        // Set cloned Data
        $cloneID = $requests["data"][0]["id"];
        $cloneData = $requests["data"][0]["data"];
        $unionArray = $data;
        
        foreach ($cloneData as $key => $value) {
            
            // If key is in the exceptions list, skip it
            $exceptions = [
                "CQP_CUSTOM_EXPIRATION_DATE",
                "FORTE_ERROR",
                "CQP_SHOW_ERROR",
                "CQP_INSTALLMENTS_DATE",
                "CQP_ADOBE_DECLINED_COMMENT",
                "CQP_NOT_QUOTE_COMMENTS",
                "CQP_COMMENTS",
                "CQP_NOT_QUOTE_REASON",
                "CQP_REASON",
                "CQP_SEARCH_CODE",
                "CQP_COLLECTION_REQUEST_ID",
                "CQP_ACTION",
                "CQP_SUBMITION_DATE",
                "CQP_INSURED_CLONE",
                "CQP_INSURED_RENEWAL",
                "CQP_INSURED_NAME_NEW",
                "CQP_QUOTE",
                "CQP_UNDERWRITING_YEAR",
                "dateToday",
                "CQP_STARTER_USER",
                "CQP_INSURED_NAME",
                "CQP_INSURED_NAME",
                "CQP_BROKER_STATUS"
            ];
            
            if (in_array($key, $exceptions, true)) {
                continue;
            }
            
            $unionArray[$key] = $value;
        }

        if ($data["CQP_ACTION"] == "CLONE") {
            $unionArray["CQP_SEARCH_CODE"] = $cloneData["CQP_SEARCH_CODE"];
        }

        if ($data["CQP_ACTION"] == "RENEWAL") {
            $unionArray["CQP_RENEWAL_CARGO"] = "YES";
        }
        
        $unionArray["CQP_CLONED_DATA"] = true;
        $unionArray["CQP_CLONE_PARENT"] = $cloneID;

        // Get Clone Files
        $clonedFiles = $apiInstanceFiles->getRequestFiles($cloneID);
        
        foreach ($clonedFiles->getData() as $file) {
            $indexFile = findValuePath($unionArray, $file->getId(), $file["custom_properties"]["data_name"]);
            $setFile = $apiInstanceFiles->getRequestFilesById($cloneID, $file->getId());
            $fileContents = file_get_contents($setFile->getPathname());
            $filePath = '/tmp/' . $file["file_name"];
            file_put_contents($filePath, $fileContents);
            
            if ($indexFile == "") {
                $newFile = $apiInstanceFiles->createRequestFile($data["_request"]["id"], $file["custom_properties"]["data_name"], $filePath);
            } else {
                // Manually update Variables in files
                $replaceIndex = false;

                foreach ($manualFiles as $indexManual => $manual) {
                    if ($manual["old_var"] == explode(".", $indexFile)[0]) {
                        $replaceIndex = $indexManual;
                    }
                }

                if ($replaceIndex !== false && $data["CQP_ACTION"] == "RENEWAL") {
                    $indexFile = str_replace(explode(".", $indexFile)[0], $manualFiles[$replaceIndex]["new_var"], $indexFile);
                } 

                $newFile = $apiInstanceFiles->createRequestFile($data["_request"]["id"], $indexFile, $filePath);
                setValueByPath($unionArray, $indexFile, $newFile->getFileUploadId());
            }
        }
        
        // Update individual variables
        foreach($manualVariables as $manual) {
            if ($unionArray[$manual["original"]] != null && $unionArray[$manual["condition"]["variable"]] == $manual["condition"]["value"]) {
                $unionArray[$manual["copy"]] = $unionArray[$manual["original"]];
            }
        }

        // Clean variables in the current data
        foreach($cleanVariables as $cleanVar) {
            if ($cleanVar["conditionNeeded"] === false || ($cleanVar["conditionNeeded"] === true  && $unionArray[$cleanVar["condition"]["variable"]] == $cleanVar["condition"]["value"])) {
                $unionArray[$cleanVar["cleanVar"]] = null;
            }
        }

        if ($data["CQP_ACTION"] == "CLONE") {
            // Update Value in collection of the cloned data
            $insuredCollection = getCollectionId('CQP_FORTE_CARGO_INSURED', getEnv("API_HOST") . getEnv("API_SQL"));
            $apiInstance = $api->collections();
            $record = new \ProcessMaker\Client\Model\RecordsEditable();

            $record->setData([
                'CQP_SEARCH_CODE' => $unionArray["CQP_SEARCH_CODE"],
                'CQP_INSURED_NAME' => $unionArray["CQP_INSURED_NAME"],
                'CQP_INSURED_CODE' => $unionArray["CQP_INSURED_CODE"],
                'CQP_TYPE' => $unionArray["CQP_TYPE"],
                'CQP_COUNTRY' => $unionArray["CQP_COUNTRY"],
                'CQP_INCEPTION_DATE' => $unionArray["CQP_INCEPTION_DATE"],
                'CQP_UNDERWRITING_YEAR' => $unionArray["CQP_UNDERWRITING_YEAR"],
                'CQP_REINSURANCE_BROKER' => $unionArray["CQP_REINSURANCE_BROKER"],
                'CQP_COMMODITIES_PROFILE' => $unionArray["CQP_COMMODITIES_PROFILE"],
                'CQP_BROKER_STATUS' => $unionArray["CQP_BROKER_STATUS"]
            ]);

            $apiInstance->patchRecord($insuredCollection, $data["CQP_COLLECTION_REQUEST_ID"], $record);
        } elseif ($data["CQP_ACTION"] == "RENEWAL") {
            // Update historical values in request data
            $unionArray["CQP_TRANSIT_EXPOSURE"][0]["CQP_MLI_HISTORICAL"] = $unionArray["CQP_TRANSIT_EXPOSURE"][0]["CQP_MLI_USD"];
            $unionArray["CQP_TRANSIT_EXPOSURE"][0]["CQP_MLE_HISTORICAL"] = $unionArray["CQP_TRANSIT_EXPOSURE"][0]["CQP_MLE_USD"];
            $unionArray["CQP_TRANSIT_EXPOSURE"][0]["CQP_MLD_HISTORICAL"] = $unionArray["CQP_TRANSIT_EXPOSURE"][0]["CQP_MLD_USD"];
            $unionArray["CQP_STORAGE_EXPOSURE"][0]["CQP_HISTORICAL_EEL"] = $unionArray["CQP_STORAGE_EXPOSURE"][0]["CQP_STORAGE_EEL"];
            $unionArray["CQP_STORAGE_EXPOSURE"][0]["CQP_HISTORICAL_AGG"] = $unionArray["CQP_STORAGE_EXPOSURE"][0]["CQP_STORAGE_AGG"];
            $unionArray["CQP_TRANSIT"][0]["CQP_TOTAL_IMPORTS_HISTORICAL"] = $unionArray["CQP_TRANSIT"][0]["CQP_TOTAL_IMPORTS_ANNUAL_USD"];
            $unionArray["CQP_TRANSIT"][0]["CQP_TOTAL_EXPORTS_HISTORICAL"] = $unionArray["CQP_TRANSIT"][0]["CQP_TOTAL_EXPORTS_ANNUAL_USD"];
            $unionArray["CQP_TRANSIT"][0]["CQP_TOTAL_DOMESTIC_HISTORICAL"] = $unionArray["CQP_TRANSIT"][0]["CQP_TOTAL_DOMESTIC_ANNUAL_USD"];
            $unionArray["CQP_TRANSIT"][0]["CQP_TOTAL_TTP_HISTORICAL"] = $unionArray["CQP_TRANSIT"][0]["CQP_TOTAL_TTP_ANNUAL_USD"];
            $unionArray["CQP_STORAGE"][0]["CQP_MAXIMUM_INVENTORIES_HISTORICAL_TERMS"] = $unionArray["CQP_STORAGE"][0]["CQP_MAXIMUM_INVENTORIES_USD"];
            $unionArray["CQP_STORAGE"][0]["CQP_AVERAGE_INVENTORIES_HISTORICAL_TERMS"] = $unionArray["CQP_STORAGE"][0]["CQP_AVERAGE_INVENTORIES_USD"];
            $unionArray["CQP_SUMMARY_DETAILS"][0]["CQP_TOTAL_ESTIMATED_HISTORICAL"] = $unionArray["CQP_SUMMARY_DETAILS"][0]["CQP_TOTAL_ESTIMATED_USD"];
            $unionArray["CQP_SUMMARY_DETAILS"][0]["CQP_ESTIMATED_HISTORICAL"] = $unionArray["CQP_SUMMARY_DETAILS"][0]["CQP_ESTIMATED_USD"];
            $unionArray["CQP_SUMMARY_DETAILS"][0]["CQP_COMBINED_RATE_HISTORICAL"] = $unionArray["CQP_SUMMARY_DETAILS"][0]["CQP_COMBINED_RATE_USD"];
            $unionArray["CQP_SUMMARY_DETAILS"][0]["CQP_MINDEP_DROPDOWN_HISTORICAL"] = $unionArray["CQP_SUMMARY_DETAILS"][0]["CQP_MINDEP_DROPDOWN"];
            $unionArray["CQP_SUMMARY_DETAILS"][0]["CQP_MINDEP_HISTORICAL"] = $unionArray["CQP_SUMMARY_DETAILS"][0]["CQP_MINDEP_USD"];
            $unionArray["CQP_SUMMARY_DETAILS"][0]["CQP_FORTE_LINE_SUPPORT_HISTORICAL"] = $unionArray["CQP_SUMMARY_DETAILS"][0]["CQP_FORTE_LINE_SUPPORT_USD"];
            $unionArray["CQP_SUMMARY_DETAILS"][0]["CQP_FORTE_GWP_HISTORICAL"] = $unionArray["CQP_SUMMARY_DETAILS"][0]["CQP_FORTE_GWP_USD"];
            $unionArray["CQP_SUMMARY_DETAILS"][0]["CQP_BROKER_DEDUCTIONS_HISTORICAL"] = $unionArray["CQP_SUMMARY_DETAILS"][0]["CQP_BROKER_DEDUCTIONS_USD"];
            $unionArray["CQP_SUMMARY_DETAILS"][0]["CQP_FORTE_GWP_HISTORICAL_BROKER"] = $unionArray["CQP_SUMMARY_DETAILS"][0]["CQP_FORTE_GWP_USD_BROKER"];
            $unionArray["CQP_SUMMARY_DETAILS"][0]["CQP_UNDERWRITING_EXPENSES_HISTORICAL"] = $unionArray["CQP_SUMMARY_DETAILS"][0]["CQP_UNDERWRITING_EXPENSES_USD"];
            $unionArray["CQP_SUMMARY_DETAILS"][0]["CQP_NET_PREMIUM_HISTORICAL"] = $unionArray["CQP_SUMMARY_DETAILS"][0]["CQP_NET_PREMIUM_USD"];
            $unionArray["CQP_SUMMARY_DETAILS"][0]["CQP_TAX_HISTORICAL"] = $unionArray["CQP_SUMMARY_DETAILS"][0]["CQP_TAX_USD_VIEW"];

            if ($unionArray["CQP_SUMMARY_CLAIMS"] != null) {
                foreach ($unionArray["CQP_SUMMARY_CLAIMS"] as &$summaryData) {
                    $summaryData["CQP_TOTAL_CLAIMS_COMBINED_HISTORICAL"] = $summaryData["CQP_TOTAL_CLAIMS_TRANSIT"];
                }
            }

            // Update Max Cap and Names for reinsurers
            //Get current information from collection
            $currentMarketsDist = getCurrentReinsurersDistribution(
                $unionArray["CQP_COUNTRY"]["COUNTRY"]
            );
            $indexedCurrentMarketsDist = [];
            foreach ($currentMarketsDist as $item) {
                if (!empty($item['CQP_REINSURER'])) {
                    $indexedCurrentMarketsDist[$item['CQP_REINSURER']] = [
                        'data' => $item,
                        'used' => false
                    ];
                }
            }
            //Get Markets variable
            $oldMarkets = $unionArray["CQP_MARKETS"];

            //Compare and update with current distribution values
            foreach ($oldMarkets as &$market) {

                $reinsurer = $market['CQP_REINSURER'] ?? null;

                if ($reinsurer && isset($indexedCurrentMarketsDist[$reinsurer])) {

                    $info = $indexedCurrentMarketsDist[$reinsurer]['data'];

                    $market['CQP_REQUIRED'] = $info['CQP_REQUIRED'];
                    $market['CQP_REINSURER_FULLNAME'] = $info['CQP_REINSURER_NAME'];
                    $market['CQP_MAXIMUN_CAP'] = (float) $info['CQP_MAX_CAP'];

                    $indexedCurrentMarketsDist[$reinsurer]['used'] = true;
                }
            }

            //In case old data only had less markets than actual - Update and add missing markets
            foreach ($indexedCurrentMarketsDist as $entry) {

                if ($entry['used']) {
                    continue;
                }

                $info = $entry['data'];

                $oldMarkets[] = [
                    "taken" => false,
                    "CQP_REINSURER" => $info['CQP_REINSURER'],
                    "CQP_MAXIMUN_CAP" => (float) $info['CQP_MAX_CAP'],
                    "CQP_REINSURER_FULLNAME" => $info['CQP_REINSURER_NAME'],
                    "CQP_USD" => 0,
                    "CQP_FORTE_SHARE" => 0,
                    "CQP_REQUIRED" => $info["CQP_REQUIRED"],
                    "EDIT_DIST" => ""
                ];
            }
            //Return new Markets Array
            $unionArray["CQP_MARKETS"] = $oldMarkets;
        }
    }
}

$unionArray["CQP_CLIENT_HISTORY"] = $data["CQP_CLIENT_HISTORY"];
$unionArray["CQP_CLONE_RENEWAL_ORIGIN"] = $data["CQP_CLONE_RENEWAL_ORIGIN"];
$unionArray["CQP_CARGO_CURRENT_STATUS"] = "QUOTED";
$unionArray["CQP_INSURED_NAME"] = $data["CQP_INSURED_NAME"];

return $unionArray;

/* Search File in the object
*
 * @param array $array
 * @param string $searchValue
 * @param string $prefix
 * @return string $matches
 *
 * by Diego Tapia
*/

function findValuePath($array, $searchValue, string $prefix = '') { 
    $matches = "";
    $exceptions = ["CQP_MAIL_FILE_IDS", "CQP_SLIP_ATTACHMENT"];

    foreach ($exceptions as $exception) {
        unset($array[$exception]);
    }

    $walker = function ($node, string $currentPath) use (&$walker, $searchValue, $prefix, &$matches) {
        if (is_array($node)) {
            foreach ($node as $key => $child) {
                $segment = is_int($key) ? (string) $key : $key;
                $newPath = $currentPath === '' ? $segment : $currentPath . '.' . $segment;
                $walker($child, $newPath);
            }
        } else {
            $startsWithPrefix = $prefix === '' || substr($currentPath, 0, strlen($prefix)) === $prefix;
            if ($startsWithPrefix) {
                if ($node === $searchValue || (string)$node === (string)$searchValue) {
                    $matches = $currentPath;
                }
            }
        }
    };

    $walker($array, '');
    return $matches;
}


/* Update Value in array base on the index
 *
 * @param array $array
 * @param string $path
 * @param string $value
 *
 * by Diego Tapia
*/
function setValueByPath(&$array, $path, $value) {
    $keys = explode('.', $path);
    $temp = &$array;

    foreach ($keys as $key) {
        if (!isset($temp[$key]) || !is_array($temp[$key])) {
            $temp[$key] = [];
        }

        $temp = &$temp[$key];
    }

    $temp = $value;
}

/* 
 * Call Processmaker API with Guzzle
 *
 * @param string $requestType
 * @param array $postdata
 * @param bool $contentFile
 * @return array $res 
 *
 * by Diego Tapia
 */
function getSqlData ($requestType, $postdata = [], bool $contentFile = false) {
    $headers = [
        "Accept" => $acceptType,
        "Authorization" => "Bearer " . getenv("API_TOKEN"),
        "Content-Type" => $contentFile ? "'application/octet-stream'" : "application/json"

    ];
    $client = new \GuzzleHttp\Client([
        'verify' => false,
    ]);
    $request = new \GuzzleHttp\Psr7\Request($requestType, getenv('API_HOST') . getenv('API_SQL'), $headers, json_encode(["SQL" => base64_encode($postdata)]));
    try {
        $res = $client->sendAsync($request)->wait();
        $res = $res->getBody()->getContents();
        //$res = json_decode($res, true);
        if ($contentFile === false) {
            $res = json_decode($res, true);
        }
    } catch (\GuzzleHttp\Exception\RequestException | \Exception | \Error | \Throwable $e) {
        $res = [
            'error_message' => $e->getMessage(),
            'error_detail' => base64_encode($e->getFile() . ":" . ($e->getLine() ?? '')),
            'error_response' => $e->getResponse() ?? ''
        ];
    }
    return $res;
}

/**IN PROGRESS**/
/* 
 * Get current information from Reinsurers collection
 *
 * @param string $countrySelected
 * @return array $responseDistribution 
 *
 * by Adriana Centellas
 */
function getCurrentReinsurersDistribution($countrySelected)
{
  // Global Variables
  $apiHost = getEnv("API_HOST");
  $apiToken = getEnv("API_TOKEN");
  $apiSql = getEnv("API_SQL");
  $apiUrl = $apiHost . $apiSql;

  // Resolve collection id
  $reinsurerCollectionId = getCollectionId('CQP_FORTE_CARGO_REINSURER', $apiUrl);

  // SQL Query
  $sQDistribution = "SELECT
    data->>'$.CQP_MAX_CAP' AS CQP_MAX_CAP,
    data->>'$.CQP_REQUIRED' AS CQP_REQUIRED,
    data->>'$.CQP_REINSURER' AS CQP_REINSURER,
    data->>'$.CQP_REINSURER_NAME' AS CQP_REINSURER_NAME
    FROM collection_" . $reinsurerCollectionId . "
    WHERE UPPER(data->>'$.CQP_STATUS') = 'ACTIVE'
    AND JSON_CONTAINS(
            data,
            '\"" . $countrySelected . "\"',
            '$.CQP_COUNTRIES'
        );";

  //Get response
  try {
    $responseDistribution = callApiUrlGuzzle($apiUrl, "POST", encodeSql($sQDistribution)) ?? [];
  } catch (Throwable $e) {
    $responseDistribution = [];
  }

  return $responseDistribution;
}