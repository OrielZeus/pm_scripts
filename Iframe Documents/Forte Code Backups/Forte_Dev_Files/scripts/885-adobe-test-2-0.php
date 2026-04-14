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

use GuzzleHttp\Client;
use GuzzleHttp\Exception\BadResponseException;

$apiInstance = $api->requestFiles();

$apiClient = setApiClient();

getApiResp('https://api.na3.adobesign.com/api/rest/v6/baseUris',$apiClient);

$participants = [[
    'memberInfos' => [
        [
            'name' => 'cristian',
            'email' =>'cristian.ferrufino@processmaker.com',
            'securityOption' => [
                'authenticationMethod' => 'NONE',
            ],
        ],
    ],
    'order' => 1,
    'role' => 'SIGNER',
]];

$pathFile = getRequestFile();
$response = createTransient('my_file',$pathFile,$pathFile,$apiClient);
$files[] = [
    'transientDocumentId' => $response['response']['transientId'],
]; 

return createAgreement('my first aggrement', $participants, $files, $apiClient);

return $files;

function setApiClient()
{
    $client = new Client();
    $apiClient = new Client(
        [
            'headers' => [
                "Authorization" => "Bearer 3AAABLblqZhAiscKvt7eg1zIJfiH4ImwP74jKAcYtKTGKFQFS0yQCSIZ_I7lF_vxmhWhNJJONevYw3Ch3BO5zfAVb8bYEx44N",
                "Content-Type" => "application/json",
                "cache-control" => "no-cache",
                "x-api-user" => "email:diego.tapia@processmaker.com"
            ],
            'base_uri' => 'https://api.na3.adobesign.com/'
        ]
    );
    return $apiClient;
}

function getApiResp($url, $apiClient)
{
    
    try {
        $response = $apiClient->get($url);
        $response = json_decode($response->getBody()->getContents());
        return [
            'response' => $response,
            'status' => 200,
        ];
    } catch (BadResponseException $e) {
        return [
            'response' => $e->getMessage(),
            'status' => $e->getResponse()->getStatusCode(),
        ];
    }
}

function getRequestFile() 
{
    global $apiInstance, $data;
    $processRequestId = $data["_request"]["id"];
    $fileId = $data["fileUploadId"];
    $file = $apiInstance->getRequestFilesById($processRequestId, $fileId);
    return $file->getPathname();
} 


function createTransient($dataName,$file,$mimeType,$apiClient)
{

    $file = file_get_contents($file);
    $mimeType = mime_content_type($mimeType);
    try {
       
        $response = $apiClient->post('api/rest/v6/transientDocuments', [
            'multipart' => [
                [
                    'name' => 'File-Name',
                    'contents' => $dataName,
                ],
                [
                    'name' => 'File',
                    'contents' => $file,
                ],
                [
                    'name' => 'Mime-Type',
                    'contents' => $mimeType,
                ],
            ],
        ]);
       
        $response = json_decode($response->getBody()->getContents());
        return [
            'response' => [
                'transientId' => $response->transientDocumentId,
            ],
            'status' => 200,
        ];
    } catch (BadResponseException $e) {
        return [
            'response' => ['message' => $e->getMessage()],
            'status' => $e->getResponse()->getStatusCode(),
        ];
    }
}

function createAgreement($name, $participants,$files, $apiClient)
{
    
    $matchedData = [];
    try {
        $response = $apiClient->post(
            'api/rest/v6/agreements',
            [
                'json' => [
                    'fileInfos' => $files,
                    'name' => $name,
                    'participantSetsInfo' => $participants,
                    'signatureType' => 'ESIGN',
                    'state' => 'IN_PROCESS',
                    //'mergeFieldInfo' => $matchedData,
                    'documentVisibilityEnabled' => false,
                ],
            ]
        );
        $response = json_decode($response->getBody()->getContents());
        return [
            'response' => [
                'agreement_id' => $response->id,
                'documents' => $documents,
                'participants' => $participants,
            ],
            'status' => 200,
        ];
    } catch (BadResponseException $e) {
        return [
            'response' => ['message' => $e->getMessage()],
            'status' => $e->getResponse()->getStatusCode(),
        ];
    }
}