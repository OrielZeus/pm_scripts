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
use GuzzleHttp\Psr7\Request;

try {
    //Get envs
    $getEnvs = localApi::get ('environment_variables?order_direction=asc&per_page=1000');
    $envList = $getEnvs['data'];
    $envResults = [];
    //Loop envs
    foreach($envList as $env) {
        //Get Values
        $envResults[$env['name']] = getenv($env['name']);
    }
    return $envResults;
} catch (Exception $e) {
    return 'Error';
}

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
        $response = json_encode($response);
        return json_decode($response, true);
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