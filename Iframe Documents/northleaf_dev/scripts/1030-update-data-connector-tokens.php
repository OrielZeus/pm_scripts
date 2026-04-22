<?php
ini_set('memory_limit', '1024M');

use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Request;

class UpdateTokens {
    public $userApiToken;
    public $adminUserId = 1;
    public $perPageDataSources = 200;

    function callRestAPI($uri, $method, $data_to_send = []) {
        $api_token = getenv('API_TOKEN');
        $api_host = getenv('API_HOST');
        $headers = [
            "Content-Type" => "application/json",
            "Accept" => "application/json",
            "Authorization" => "Bearer " . $api_token
        ];
        $client = new Client([
            'base_uri' => $api_host,
            'verify' => false
        ]);

        switch ($method) {
            case 'get':
                $response = $client->get($api_host . $uri, [
                    'headers' => $headers
                ]);
                break;
            case 'put':
                $response = $client->put($api_host . $uri, [
                    'headers' => $headers,
                    'body' => json_encode($data_to_send)
                ]);
                break;
            case 'post':
                $response = $client->post($api_host . $uri, [
                    'headers' => $headers,
                    'body' => json_encode($data_to_send)
                ]);
                break;
            case 'getFileContents':
                $response = $client->get($uri, [
                    'http_errors' => false,
                    'headers' => $zendeskHeaders,
                    'sink' => $zipFileName
                ]);
                break;
        }
        return $response;
    }

    function generateNewUserAPIToken($adminUserId) {
        $tokenUrl = '/users/' . $adminUserId . '/tokens';
        $tokenPostValues = [
            'name' => 'API Token',
            'scopes' => [],
        ];
        $response = $this->callRestAPI($tokenUrl, 'post', $tokenPostValues);
        $response = $response->getBody()->getContents();
        $response = json_decode($response, true);

        if (isset($response['accessToken'])) {
            $return = [
                'accessToken' => $response['accessToken'],
                'created_at' => $response['token']['created_at'],
                'expires_at' => $response['token']['expires_at']
            ];            
        } else {
            throw new Exception('Failed to retrieve access token');
        }

        return $return;
    }

    function getDataSources() {
        $api_host = getenv('API_HOST');
        $dataSourcesUrl = '/data_sources?page=1&per_page=' . $this->perPageDataSources . '&filter=&order_by=name&order_direction=asc&include=categories,category&all_types=true';
        $dataSourcesResponse = $this->callRestAPI($dataSourcesUrl, 'get');
        $dataSourcesResponse = $dataSourcesResponse->getBody()->getContents();
        $dataSourcesResponse = json_decode($dataSourcesResponse, true);
        $dataSources = $dataSourcesResponse['data'] ?? [];
        $pmDataSources = [];
        if (count($dataSources) > 0) {
            foreach ($dataSources as $dataSource) {
                foreach ($dataSource['endpoints'] as $endpointName => $endpoint) {
                    $endpointUrl = $endpoint['url'];
                    if (strpos($endpointUrl, '/api/1.0/') === 0 || strpos($endpointUrl, $api_host) === 0) {
                        $pmDataSources[] = $dataSource;
                        break;
                    }
                }
            }
        }
        return $pmDataSources;
    }

    function updateDataSources($pmDataSources) {
        $resultUpdate = [];
        foreach ($pmDataSources as $dataSourceToUpdate) {
            try {
                $dataSourceIdToUpdate = $dataSourceToUpdate['id'];
                $updateUrl = '/data_sources/' . $dataSourceIdToUpdate;
                $payload = $dataSourceToUpdate;
                $payload['credentials'] = [
                    'password_type' => 'None',
                    'token' => $this->userApiToken,
                    'verify_certificate' => true,
                ];
                $result = $this->callRestAPI($updateUrl, 'put', $payload);
                $resultUpdate['successful'][] = $dataSourceToUpdate['id'] . ' - '. $dataSourceToUpdate['name'];
            } catch(Exception $e) {
                $resultUpdate['unsuccessful'][] = [
                    'name' => $dataSourceToUpdate['id'] . ' - '. $dataSourceToUpdate['name'],
                    'error' => $e->getMessage()
                ];
                continue;
            }
        }

        return $resultUpdate;
    }

    function init() {
        try {
            $dataToken = $this->generateNewUserAPIToken($this->adminUserId);
            $this->userApiToken = $dataToken['accessToken'];
            $pmDataSources = $this->getDataSources();
            $resultUpdate = $this->updateDataSources($pmDataSources);
            return [
                'UpdateTokens' => [
                    'dataSourcesTotal' => count($pmDataSources), 
                    'dataSourcesResult' => $resultUpdate,
                    //'dataSourcesTokensUpdated' => array_column($pmDataSources, 'name'), 
                    'newToken' => $this->userApiToken,
                    'created_at' => $dataToken['created_at'],
                    'expires_at' => $dataToken['expires_at']

                ]
            ];
        } catch(Exception $e) {
            return [
                'UpdateTokens' => [
                    'status' => 'error',
                    'message' => $e->getMessage()
                ]
            ];
        }
        
    }   
}

/*
//config sample:
{
    "adminUserId": 10,
    "perPageDataSources": 10
}
*/

$config = isset($config["adminUserId"]) ? $config : $data["_parent"]["config"];

$obj = new UpdateTokens(); 
$obj->adminUserId = isset($config['adminUserId']) ? $config['adminUserId'] : $obj->adminUserId;
$obj->perPageDataSources = isset($config['perPageDataSources']) ? $config['perPageDataSources'] : $obj->perPageDataSources;

return $obj->init();