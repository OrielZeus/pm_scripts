<?php

/**********************************
 * Get list of active users
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
    'per_page' => 9999, // You can adjust this if you need fewer results
    'order_direction' => 'asc',
];

// Make the API request to get users
$requestListRecords = localApi::get('users', $params);
$requestListRecords = json_decode(json_encode($requestListRecords), true);

// Check if the response contains the 'data' array
if (isset($requestListRecords['data']) && is_array($requestListRecords['data'])) {
    // Filter the 'data' array for users with 'status' = 'ACTIVE' (if needed)
    $activeUsers = array_filter($requestListRecords['data'], function($user) {
        return isset($user['status']) && $user['status'] === 'ACTIVE';
    });

    // Re-index the array to make the indices consecutive
    $activeUsers = array_values($activeUsers);

    // Map the filtered users to only include 'id', 'email', 'firstname', 'lastname', and 'fullname'
    $filteredUsers = array_map(function($user) {
        return [
            'id' => $user['id'],
            'email' => $user['email'],
            'firstname' => $user['firstname'],
            'lastname' => $user['lastname'],
            'fullname' => isset($user['firstname']) && isset($user['lastname']) 
                ? $user['firstname'] . ' ' . $user['lastname'] 
                : '' // Or any other logic for fullname
        ];
    }, $activeUsers);

    // Return the filtered list of active users
    return $filteredUsers;
} else {
    // If 'data' array doesn't exist or is empty, return an empty array
    return [];
}