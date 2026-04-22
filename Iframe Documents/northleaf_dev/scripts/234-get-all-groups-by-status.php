<?php

/**********************************
 * Get list of active groups
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
    'order_direction' => 'asc',
];


// Make the API request
$requestListRecords = localApi::get('groups', $params);
$requestListRecords = json_decode(json_encode($requestListRecords), true);

// Check if the response contains the 'data' array
if (isset($requestListRecords['data']) && is_array($requestListRecords['data'])) {
    // Filter the 'data' array for items with 'status' = 'ACTIVE'
    $activeGroups = array_filter($requestListRecords['data'], function($group) {
        return isset($group['status']) && $group['status'] === 'ACTIVE';
    });

    // Re-index the array to make the indices consecutive
    $activeGroups = array_values($activeGroups);

    // Map the filtered groups to only include 'id', 'name', and 'description'
    $filteredGroups = array_map(function($group) {
        return [
            'id' => $group['id'],
            'name' => $group['name'],
            'description' => $group['description']
        ];
    }, $activeGroups);

    // Return the filtered and mapped list of active groups
    return $filteredGroups;
} else {
    // If no 'data' array exists, handle the case where no groups are found
    return [];
}