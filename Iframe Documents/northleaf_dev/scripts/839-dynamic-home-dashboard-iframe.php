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

// Global Variables
$apiToken = getenv('API_TOKEN');
$apiHost = getenv('API_HOST');
$onlyHost = getenv('HOST_URL');
$urlPathPsToolsPackage =  $apiHost.'/admin/package-proservice-tools/sql';

//Check if user is Admin
$currentUser = $data['currentUserId'] ?? false;
if (!$currentUser) {
    return [
        'PSTOOLS_RESPONSE_HTML' => '<h4>Unregistered user</h4>'
    ];
}
// Ger user Data
$userData = localApi::get('users/'. $currentUser);
$userIsAdmin = $userData['is_administrator'] ?? false;
$userGroups = [];
if (!$userIsAdmin) {
    // Get groups of User
    $userGroupsList = localApi::get('group_members?member_id=' . $currentUser . '&order_direction=asc&per_page=99');
    $userGroupsList = $userGroupsList['data'] ?? [];
    foreach($userGroupsList as $group) {
        $userGroups[] = $group['group_id'];
    }
}
// Get Process List
$processes = localApi::get ('processes?order_direction=asc&per_page=999&status=ACTIVE&include=launchpad');
$processes = $processes['data'] ?? [];
$processAvailables = [];
foreach($processes as $process) {
    // Exclude process because it starts with a timer
    if ($process['has_timer_start_events'] === true) {
        continue;
    }
    // Process Start Events
    $startEvents = [];
    $events = $process['start_events'] ?? [];
    // Exclude process if there is no start event in the process
    if (count($events) == 0) {
        continue;
    }
    // Loop Start Events
    foreach ($events as $event) {
        // If the current user is not an admin
        if (!$userIsAdmin) {
            // Exclude if no assignment rules are defined    
            if (!isset($event['assignment'])) {
                continue(2);
            }
            // Exclude if my user is not assigned to the process start event
            if ($event['assignment'] == 'user' && $event['assignedUsers'] != $currentUser) {
                continue(2);
            }
            // Exclude if my user does not belong to the group assigned to the start event of the process
            if ($event['assignment'] == 'group' && !in_array($event['assignedGroups'], $userGroups)) {
                continue(2);
            }
        }
        // Exclude if the start event is a web entry
        $isWebEntry = json_decode($event['config'], true);
        if (isset($isWebEntry['web_entry'])) {
            continue(2);
        }
        // Save Start Events
        $startEvents[] = [
            "id" => $event['id'],
            "name" => $event['name']
        ];
    }
    // Add Process to list
    $processAvailables[] = [
        "id" => $process['id'],
        "name" => $process['name'],
        "description" => $process['description'],
        "launchpad_properties" => json_decode($process['launchpad_properties']),
        "star_event_or" => $process['start_events'],
        "start_events" => $startEvents,
        "has_timer_start_events" => $process['has_timer_start_events']
    ];
}
//return [count($processes), count($processAvailables), 'admin', $userIsAdmin, $currentUser, $userGroups, $processAvailables];
$html = '';
$html .= '<head>
<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
<!-- Bootstrap CSS -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-..." crossorigin="anonymous">
<!-- Bootstrap JavaScript (with Popper) -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js" integrity="sha384-..." crossorigin="anonymous"></script>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css" />
</head>
<style>
    .ag-format-container {
        width: 1142px;
        margin: 0 auto;
    }

    .ag-courses_box {
        display: -webkit-box;
        display: -ms-flexbox;
        display: flex;
        -webkit-box-align: start;
        -ms-flex-align: start;
        align-items: flex-start;
        -ms-flex-wrap: wrap;
        flex-wrap: wrap;
        padding-bottom: 50px;
        padding-top:20px;
    }
    .ag-courses_item {
        -ms-flex-preferred-size: calc(33.33333% - 30px);
        flex-basis: calc(33.33333% - 30px);
        margin: 0 15px 30px;
        overflow: hidden;
        border-radius: 15px;
        box-sizing: border-box;
        box-shadow: 10px 12px 30px rgb(0 0 0 / 46%)
    }
    .ag-courses-item_link {
        display: block;
        padding: 30px 20px;
        background-color: #333333;
        overflow: hidden;
        position: relative;
        text-decoration: none;
        max-height: 240px;
        min-height: 240px;
    }
    .ag-courses-item_link:hover,
    .ag-courses-item_link:hover .ag-courses-item_date {
        text-decoration: none;
        color: #FFF;
    }
    .ag-courses-item_link:hover .ag-courses-item_bg {
        -webkit-transform: scale(10);
        -ms-transform: scale(10);
        transform: scale(10);
    }
    .ag-courses-item_title {
        /* min-height: 10px; */
        margin: 10px 0px;
        overflow: hidden;
        font-weight: 100;
        font-size: 1.5em;
        color: #FFF;
        z-index: 2;
        position: relative;
    }
    .ag-courses-item_date-box {
        margin-top: 10px;
        font-size: 0.8em;
        color: #FFF;
        z-index: 2;
        position: relative;
        font-weight: 100;
        scroll-y: true;
    }
    .ag-courses-item_date {
        font-weight: bold;
        color: #711426;

        -webkit-transition: color .5s ease;
        -o-transition: color .5s ease;
        transition: color .5s ease
    }
    .ag-courses-item_bg {
        height: 128px;
        width: 128px;
        background-color: #711426;
        z-index: 1;
        position: absolute;
        top: -75px;
        right: -75px;
        border-radius: 50%;
        -webkit-transition: all .5s ease;
        -o-transition: all .5s ease;
        transition: all .5s ease;
    }
    .content-btns {
        position: relative;
        z-index: 2;
    }
    .btn-start-case:active {
        transform: scale(0.9);
    }
    /*
    .ag-courses_item:nth-child(2n) .ag-courses-item_bg {
    background-color: #3ecd5e;
    }
    .ag-courses_item:nth-child(3n) .ag-courses-item_bg {
    background-color: #e44002;
    }
    .ag-courses_item:nth-child(4n) .ag-courses-item_bg {
    background-color: #952aff;
    }
    .ag-courses_item:nth-child(5n) .ag-courses-item_bg {
    background-color: #cd3e94;
    }
    .ag-courses_item:nth-child(6n) .ag-courses-item_bg {
    background-color: #4c49ea;
    }
    */


    @media only screen and (max-width: 979px) {
        .ag-courses_item {
            -ms-flex-preferred-size: calc(50% - 30px);
            flex-basis: calc(50% - 30px);
        }
        .ag-courses-item_title {
            font-size: 24px;
        }
    }

    @media only screen and (max-width: 767px) {
        .ag-format-container {
            width: 96%;
        }
    }
    @media only screen and (max-width: 639px) {
        .ag-courses_item {
            -ms-flex-preferred-size: 100%;
            flex-basis: 100%;
        }
        .ag-courses-item_title {
            min-height: 72px;
            line-height: 1;

            font-size: 24px;
        }
        .ag-courses-item_link {
            padding: 22px 40px;
        }
        .ag-courses-item_date-box {
            font-size: 16px;
        }
    }
</style>';
$html .= '<div id="body-home" style="background-color: #e4e4e4; width: 100%; height:100%; overflow-y:auto;">
    <div style="text-align: center; padding: 20px;">
        <span style="color: #711426; font-size: 3rem; font-weight:700;">Welcome</span>
        <span style="color: #711426; font-weight:100; font-size:2.5rem;"> ' .$userData['fullname']. ' </span>
    </div>
    <div class="ag-format-container">
    <div class="ag-courses_box">';
///process_events/16?event=node_14
$fadeIns = array("animate__fadeInUpBig", "animate__fadeInLeftBig", "animate__fadeInRightBig", "animate__fadeInDownBig");
foreach ($processAvailables as $process) {
    // Obtiene una clave (índice) aleatoria del array
    $random_key = array_rand($fadeIns);
    // Usa la clave aleatoria para obtener el valor del array
    $random_fadeInd = $fadeIns[$random_key];
    $html .= '<div class="ag-courses_item animate__animated ' . $random_fadeInd . '">
        <div class="ag-courses-item_link">
            <div class="ag-courses-item_bg"></div>

            <div class="ag-courses-item_title">
                '. $process['name'] .'
            </div>
            <div class="row content-btns">';
            foreach($process['start_events'] as $start) {
                $html .= '<div class="col-md-6">
                    <|button 
                        onclick="startCase('. $process['id'] .', \''. $start['id'] .'\')"
                        class="btn btn-outline-light btn-start-case"
                    > 
                    '. $start['name'] .'
                    </|button>
                </div>';
            }
    $html .= '</div>
            <div class="ag-courses-item_date-box">
                '. $process['description'] .'
            </div>
        </div>
    </div>';
}
if (count($processAvailables) == 0) {
    $html .= '<div style="flex: auto; text-align: center;">
    <span style="color: #711426; font-weight:10; font-size:2.5rem;">You don\'t have any Processes.</span>
    </div>';
}
$html .= '</div>
    </div>
</div>';
// JS Script
$html .= '
<script type="text/javascript">
    const iframeDoc = document;
    const parentDoc = iframeDoc.defaultView.parent.document;
    const primaryColor = getComputedStyle(parentDoc.documentElement).getPropertyValue("--primary") ?? "";
    console.log("primaryColor:", primaryColor);
    $(document).ready(function() {
        //Get Primary color of Parent
        if (primaryColor != "") {
            //$("#body-home").css("background-color", primaryColor);
        }
    });
    function startCase(processId, node) {
        console.log("processId", processId, " node:",node);
        window.open("/process_events/" + processId + "?event=" + node, "_parent");
    }
</script>';
return [
    'PSTOOLS_RESPONSE_HTML' => str_replace(['<|', '</|'], ['<', '</'], $html)
];


/**
 * Extra Functions
 **/
function apiGuzzle(string $url, string $requestType, array $postdata = [], bool $contentFile = false)
{
    static $apiToken = null;
    if ($apiToken === null) {
        $apiToken = getenv("API_TOKEN");
    }
    $acceptType = $contentFile ? "'application/octet-stream'" : "application/json";
    $headers = [
        "Accept" => $acceptType,
        'Authorization' => 'Bearer ' . $apiToken

    ];
    if ($contentFile === false) {
        $headers["Content-Type"] = "application/json";
    }
    $client = new \GuzzleHttp\Client([
        'verify' => false,
    ]);
    $request = new \GuzzleHttp\Psr7\Request($requestType, $url, $headers, json_encode($postdata));
    try {
        $res = $client->sendAsync($request)->wait();
        $res = $res->getBody()->getContents();
        $res = json_decode($res, true);
    } catch (\GuzzleHttp\Exception\RequestException | \Exception | \Error | \Throwable $e) {
        $res = [
            'error_message' => $e->getMessage(),
            'error_detail' => base64_encode($e->getFile() . ":" . ($e->getLine() ?? '')),
            'error_response' => $e->getResponse() ?? ''
        ];
    }
    return $res;
}

function encodeSql($string)
{
    $variablePut = [
        "SQL" => base64_encode($string)
    ];
    return $variablePut;
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