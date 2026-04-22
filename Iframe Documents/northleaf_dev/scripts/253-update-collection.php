<?php

/**********************************
 * Update Collection IN_EXPENSE_FUND_MANAGER
 *
 * by Manuel Monroy
 *********************************/
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Request;

class localApi
{
    private function getClient($json = true)
    {
        $headers['Authorization'] = 'Bearer ' . getenv('API_TOKEN');
        if ($json) {
            $headers['Content-Type'] = 'application/json';
            $headers['Accept'] = 'application/json';
        } else {
            $headers['Accept'] = 'application/octet-stream';
        }
        return new Client([
            'curl' => [CURLOPT_SSL_VERIFYPEER => 0, CURLOPT_SSL_VERIFYHOST => 0],
            'allow_redirects' => false,
            'cookies' => true,
            'verify' => false,
            'headers' => $headers
        ]);
    }

    public static function request($method, $url, $params = [], $data = null)
    {
        if (empty($params)) {
            $url = getenv('HOST_URL') . "/api/1.0/{$url}";
        } else {
            $queryString = http_build_query($params);
            $url = getenv('HOST_URL') . "/api/1.0/{$url}?{$queryString}";
        }

        if ($data) {
            $request = new Request($method, $url, [], json_encode($data));
        } else {
            $request = new Request($method, $url);
        }

        try {
            $response = self::getClient()->send($request);
            $content = $response->getBody()->getContents();
            return json_decode($content);
        } catch (Exception $e) {
            print_r([
                'url' => $url,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    public static function requestFile($method, $url, $params = [], $data = null)
    {
        if (empty($params)) {
            $url = getenv('HOST_URL') . "/api/1.0/{$url}";
        } else {
            $queryString = http_build_query($params);
            $url = getenv('HOST_URL') . "/api/1.0/{$url}?{$queryString}";
        }

        if ($data) {
            $request = new Request($method, $url, [], json_encode($data));
        } else {
            $request = new Request($method, $url);
        }
        try {
            $response = self::getClient(false)->send($request);
            $content = $response->getBody()->getContents();
            return json_decode($content);
        } catch (Exception $e) {
            print_r([
                'url' => $url,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    public static function get($url, $params = [])
    {
        $response = self::request('GET', $url, $params);
        return $response;
    }

    public static function post($url, $data)
    {
        $response = self::request('POST', $url, [], $data);
        return $response;
    }

    public static function delete($url)
    {
        $response = self::request('DELETE', $url, [], $data);
        return $response;
    }

    public static function put($url, $data)
    {
        $response = self::request('PUT', $url, [], $data);
        return $response;
    }

    public static function getFile($url, $params = [])
    {
        $response = self::requestFile('GET', $url, $params);
        return $response;
    }
}

$params = [
    'per_page' => 9999,
  //  'include' => 'data',
    'order_direction' => 'asc',
];


$idCollection = 65; 
//$idCollection = $data['idGroup'];

// Make the API request
$requestListRecords = localApi::get('collections/' . $idCollection . '/records', $params);
$requestListRecords = json_decode(json_encode($requestListRecords), true);
$dataCollection =  array_column($requestListRecords['data'], 'data');

// Make the API request
$idCollection = 53;
$requestListRecords = localApi::get('collections/' . $idCollection . '/records', $params);
$requestListRecords = json_decode(json_encode($requestListRecords), true);
$dataCollectionCurrency =  array_column($requestListRecords['data'], 'data');

// Make the API request IN_ASSET_CLASS
$idCollection = 62;
$requestListRecords = localApi::get('collections/' . $idCollection . '/records', $params);
$requestListRecords = json_decode(json_encode($requestListRecords), true);
$dataCollectionClass =  array_column($requestListRecords['data'], 'data');


foreach ($dataCollection as $key => $row ) {
    $dataRow = $row;
    foreach ($dataCollectionCurrency as $rowCurrency) {
        if ($dataRow['FUNDMANAGER_CURRENCY'] == $rowCurrency['CURRENCY_LABEL']) {
            $dataCollection[$key]['FUNDMANAGER_CURRENCY'] = $rowCurrency;
            break;
        }
    }
}

foreach ($dataCollection as $key => $row ) {
    $dataRow = $row;
    foreach ($dataCollectionClass as $rowClass) {
        if ($dataRow['FUNDMANAGER_ASSETCLASS'] == $rowClass['ASSET_LABEL']) {
            $dataCollection[$key]['FUNDMANAGER_ASSETCLASS'] = $rowClass;
            $params = [
                'data' =>  $dataCollection[$key],
            ];
            $updateData = localApi::put('collections/65/records/'.$dataCollection[$key]['id'] , $params);
            break;
        }
    }
}

return $dataCollection;

// Check if the response contains the user list
if (isset($requestListRecords['data']) && is_array($requestListRecords['data'])) {
    // Extract the list of users
    $users = $requestListRecords['data'];
    
    // Filter the list to get only users with "status" => "ACTIVE"
    $activeUsers = array_filter($users, function($user) {
        return isset($user['status']) && $user['status'] === 'ACTIVE';
    });

    // Re-index the array to make the indices consecutive
    $activeUsers = array_values($activeUsers);

    // Create a new array with only the required fields
    $filteredUsers = array_map(function($user) {
        return [
            'id' => $user['member_id'],
            'email' => $user['email'],
            'firstname' => $user['firstname'],
            'lastname' => $user['lastname'],
            'fullname' => $user['fullname']
        ];
    }, $activeUsers);

    // Return the list of active users with the requested fields
    return $filteredUsers;
} else {
    // If no users are found in the response, handle the error
    return []; // Return an empty array if no active users are found
}