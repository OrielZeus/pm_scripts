<?php 
ini_set('memory_limit', '1024M');
require_once 'vendor/autoload.php';

use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Request;

class emailReminders {
    //email settings variables
    public $emailHost;
    public $emailPort;
    public $emailUsername;
    public $emailPassword;
    public $emailFromEmail;
    public $emailFromName;
    public $emailEncryptation;
    public $transport;
    //
    public $emailSubject = 'Pending Tasks Reminder';
    public $emailCC;
    public $useCustomHTML = false;
    public $customEmailBody = '';
    public $caseNumberColumn = true;
    public $requestIdColumn = false;
    public $caseTitleColumn = false;
    public $processNameColumn = false;
    public $taskNameColumn = false;
    public $lastUpdatedDateColumn = true;
    //
    public $processName;
    public $tasks = [];
    public $batchNumber = 100;
    //
    public $current_page;
    public $total_pages;
    public $totalRequests;
    //
    public $enableTesting = false;
    public $testingEmailTo;
    public $testingEmailCC;
    public $limitEmails;
    //
    public $checkSelfServiceAssignments = false;


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

        switch($method) {
            case 'get':
                $response = $client->get($api_host.$uri, [
                    'headers' => $headers
                ]);
            break;
            case 'put':
                $response = $client->put($api_host.$uri, [
                    'headers' => $headers,
                    'body' => json_encode($data_to_send)
                ]);
            break;
        }
        return $response;
    }

    function setEmailSettings() {        
        $url = "/settings?page=1&per_page=25&order_by=name&order_direction=ASC&filter=&group=Email%20Default%20Settings";
        $response = $this->callRestAPI($url, 'get');
        $response = json_decode($response->getBody()->getContents(), true);
        
        $aEmailsConnector = [];
        $responseQuery = $response["data"];
        foreach ($responseQuery as $value) {
            $aEmailsConnector[$value["key"]] = $value["config"];
        }

        $this->emailHost = ($aEmailsConnector["EMAIL_CONNECTOR_MAIL_HOST"]) ? $aEmailsConnector["EMAIL_CONNECTOR_MAIL_HOST"] : "";
        $this->emailPort = ($aEmailsConnector["EMAIL_CONNECTOR_MAIL_PORT"]) ? $aEmailsConnector["EMAIL_CONNECTOR_MAIL_PORT"] : "";
        $this->emailUsername = ($aEmailsConnector["EMAIL_CONNECTOR_MAIL_USERNAME"]) ? $aEmailsConnector["EMAIL_CONNECTOR_MAIL_USERNAME"] : "";
        $this->emailPassword = ($aEmailsConnector["EMAIL_CONNECTOR_MAIL_PASSWORD"]) ? $aEmailsConnector["EMAIL_CONNECTOR_MAIL_PASSWORD"] : "";
        $this->emailFromEmail = ($aEmailsConnector["EMAIL_CONNECTOR_MAIL_FROM_ADDRESS"]) ? $aEmailsConnector["EMAIL_CONNECTOR_MAIL_FROM_ADDRESS"] : "";
        $this->emailFromName = ($aEmailsConnector["EMAIL_CONNECTOR_MAIL_FROM_NAME"]) ? $aEmailsConnector["EMAIL_CONNECTOR_MAIL_FROM_NAME"] : "";
        $emailEncryptation = ($aEmailsConnector["EMAIL_CONNECTOR_MAIL_ENCRYPTION"]) ? $aEmailsConnector["EMAIL_CONNECTOR_MAIL_ENCRYPTION"] : "";
        switch ($emailEncryptation) {
            case '0':
                $emailEncryptation = "no";
                break;
            case '1':
                $emailEncryptation = "tls";
                break;
            case '0':
                $emailEncryptation = "ssl";
                break;
            default:
                $emailEncryptation = "no";
                break;
        }
        $this->emailEncryptation = $emailEncryptation;
        
        return $aEmailsConnector;
    }

    function ConfigureSMTPserver() {
        $this->transport = (new Swift_SmtpTransport($this->emailHost, $this->emailPort, $this->emailEncryptation))
            ->setUsername($this->emailUsername)
            ->setPassword($this->emailPassword);
        $mailer = new Swift_Mailer($this->transport);
        $mailer->getTransport()->start();
    }

    function SendEmail($userName, $userEmail, $body, $cc = false) {
        //return true;
        $mailer = new Swift_Mailer($this->transport);

        $message = (new Swift_Message($this->emailSubject))
            ->setFrom([$this->emailFromEmail => $this->emailFromName]) // sender's email address and name
            ->setTo([$userEmail => $userName]) // Recipient's email address and name
            ->setBody($body, 'text/html'); // Email body in HTML format

        if(!empty($cc)){
            $message->setCc($cc);
        }

        $result = $mailer->send($message);

        return $result ? true : false;
    }

    function getPendingTasksByProcess() {
        global $apiHost, $pmheaders, $client, $batchNumber;

        $status = 'ACTIVE';
        
        $advanced_filter = '[{"subject":{"type":"Field","value":"name"},"operator":"=","value":"'.$this->processName.'"}]';    
        $pmql = '(request != "")';
        //$pmql = '(request = "'.$this->processName.'")';
        //$pmql = '(process_id = "'.$processId.'")';

        if ($status) {
            $pmql .= ' AND (status = "'.$status.'")';
            //$pmql .= ' AND (participant = "shimo.li")';
        }

        $pmql = rawurlencode($pmql);
        $filter = '&order_by=id&order_direction=DESC';
        $url = "/requests?page=$this->current_page&per_page=$this->batchNumber&include=participants,activeTasks&pmql=$pmql&filter=$filter&advanced_filter=$advanced_filter";

        $response = $this->callRestAPI($url, 'get');
        $response = json_decode($response->getBody()->getContents(), true);

        if (!empty($response["data"])) {
            //$this->current_page = $response["meta"]["current_page"];
            $this->total_pages = $response["meta"]["total_pages"];
            $this->totalRequests = $response["meta"]["total"];
            
            foreach ($response["data"] as $request) {
                if (!empty($request['active_tasks'])) {
                    $taskDetails = [];
                    foreach ($request['active_tasks'] as $task) {
                        $allTasks = sizeof($this->tasks) ? false : true;
                        if ($allTasks || in_array($task['element_name'], $this->tasks)) {
                            if (!empty($task['user_id'])) {
                                foreach ($request['participants'] as $participant) {
                                    if ($task['user_id'] == $participant['id']) {
                                        $taskDetails[] = [
                                            'taskId' => $task['id'],
                                            'taskName' => $task['element_name'],
                                            'userId' => $participant['id'],
                                            'userEmail' => $participant['email'],
                                            'userFullname' => $participant['fullname']
                                        ];
                                    } 
                                }
                            } else { // self-service task
                                $taskDetails[] = [
                                    'taskId' => $task['id'],
                                    'taskName' => $task['element_name'],
                                    'userId' => null,
                                    'userEmail' => null,
                                    'userFullname' => null
                                ];
                            }                            
                        }                              
                    }                
                }
                if (sizeof($taskDetails)) {
                    $pendig[] = [
                        "case_number" => $request['case_number'],
                        "case_title" => $request['case_title'],
                        "requestId" => $request['id'],
                        "updated_at" => $request['updated_at'],
                        "updated_at_formatted" => date("m/d/Y", strtotime($request['updated_at'])),
                        "taskDetails" => $taskDetails
                    ]; 
                }                                   
            }
        }
        
        return $pendig;
    }

    function getPendingTasksByUser_($pendingTasks) {
        $result = []; 
        $requestsByUserAndCase = []; 

        foreach ($pendingTasks as $case) {
            $caseData = array_diff_key($case, ['taskDetails' => true]);

            foreach ($case['taskDetails'] as $task) {
                $userId = !empty($task['userId']) ? $task['userId'] : 'is_self_service';
                
                $key = $userId . '-' . $case['requestId'];

                if (!isset($result[$userId])) {
                    $result[$userId] = [
                        'userId' => $userId,
                        'userEmail' => $task['userEmail'],
                        'userFullname' => $task['userFullname'],
                        'requests' => [] 
                    ];
                }

                if (!isset($requestsByUserAndCase[$key])) {
                    $newCaseData = $caseData; 
                    $newCaseData['taskDetails'] = []; 
                    $requestsByUserAndCase[$key] = $newCaseData;
                }

                $requestsByUserAndCase[$key]['taskDetails'][] = [
                    'taskId' => $task['taskId'],
                    'taskName' => $task['taskName'],
                    'is_self_service' => ($userId == 'is_self_service')
                ];
            }
        }

        foreach ($requestsByUserAndCase as $key => $request) {
            $userId = explode('-', $key)[0];            
            $result[$userId]['requests'][] = $request;
        }

        return array_values($result);
    }

    function getPendingTasksByUser($pendingTasks) {
        $result = [];

        foreach ($pendingTasks as $case) {
            foreach ($case['taskDetails'] as $task) {
                $userId = !empty($task['userId']) ? $task['userId'] : 'is_self_service';

                if (!isset($result[$userId])) {
                    $result[$userId] = [
                        'userId' => $userId,
                        'userEmail' => $task['userEmail'],
                        'userFullname' => $task['userFullname'],
                        'requests' => []
                    ];
                }

                $caseData = array_diff_key($case, ['taskDetails' => true]);

                $caseData['taskDetails'] = [[
                    'taskId' => $task['taskId'],
                    'taskName' => $task['taskName'],
                    'is_self_service' => ($userId == 'is_self_service') ? true : false
                ]];

                $result[$userId]['requests'][] = $caseData;
            }
        }

        return array_values($result);
    }

    function getProcessedTasks($requestData) {
        foreach ($requestData as $request) {
            foreach ($request['taskDetails'] as $task) {
                $result[] = [
                    "case_number" => $request["case_number"],
                    "requestId" => $request["requestId"],
                    "case_title" => $request["case_title"],
                    "taskId" => $task["taskId"],
                    "taskName" => $task["taskName"],
                ];
            }
        }
        return $result;
    }

    function buildTasksTable($requestData) {
        $table = '<table><thead><tr>';
        if ($this->caseNumberColumn) $table .= '<th>Case #</th>';
        if ($this->requestIdColumn) $table .= '<th>Request #</th>';
        if ($this->caseTitleColumn) $table .= '<th>Case Title</th>';
        if ($this->processNameColumn) $table .= '<th>Process</th>';
        if ($this->taskNameColumn) $table .= '<th>Task</th>';
        if ($this->lastUpdatedDateColumn) $table .= '<th>Date</th>';
        $table .= '<th></th></tr></thead><tbody>';
   
        foreach ($requestData as $request) {
            foreach ($request['taskDetails'] as $task) {
                $label = ($task['is_self_service'] && $this->checkSelfServiceAssignments) ? 'Claim' : 'View';                    
                $table .= '<tr>';
                if ($this->caseNumberColumn) $table .= '<td>' . $request["case_number"] . '</td>';                
                if ($this->requestIdColumn) $table .= '<td>' . $request["requestId"] . '</td>';
                if ($this->caseTitleColumn) $table .= '<td>' . $request["case_title"] . '</td>';
                if ($this->processNameColumn) $table .= '<td>' . $this->processName . '</td>';
                if ($this->taskNameColumn) $table .= '<td>' . $task["taskName"] . '</td>';
                if ($this->lastUpdatedDateColumn) $table .= '<td>' . $request["updated_at_formatted"] . '</td>';
                $table .= '<td><a href="'.$_SERVER['HOST_URL'].'/tasks/' . $task["taskId"] . '/edit">' . $label . '</a></td></tr>';
            }
        }
        $table .= '</tbody></table>';
        return $table;
    }

    function buildEmailBody($userData) {
        $body = '<html>' .
            '<head>' .
            '<style>' .
                'table {border-collapse: collapse; width: 100%;}' .
                'table thead tr {background-color: #1d6cf2;color: #FFF;}' .
                'th, td {border: 1px solid; padding: 8px; text-align: center;}' .
            '</style>' .
            '</head>' .
            '<body>' .
            '<div><p>Dear ' . $userData['userFullname'] . ',</p></div>'.
            '<div><p>You have the following pending tasks:</p></div>';
        
        if (!empty($userData['requests']) && sizeof($userData['requests'])) {
            $body .= '<h2>Pending Requests</h2>';
            $body .= $this->buildTasksTable($userData['requests']);
        } else {
            $body .= '<h2>No Pending Requests</h2>';
        }

        $body .= '<div><p>Best regards.</p></div>' .
            '</body></html>';
        
        return str_replace(["\r\n", "\r", "\n"], "", $body);
    }

    function prepareCustomEmailBody($userData) {
        $body = $this->customEmailBody;
        $replacements = [
            '{userFullname}' => $userData['userFullname'],
            '{pendigTasksTable}' => $this->buildTasksTable($userData['requests'])
        ];

        $body = str_replace(array_keys($replacements), array_values($replacements), $body);
        return str_replace(["\r\n", "\r", "\n"], "", $body);
    }

    function getUsersByGroup($groupId) {
        $url = "/groups/$groupId/users?order_direction=asc&per_page=1000";
        $response = $this->callRestAPI($url, 'get');
        $response = json_decode($response->getBody()->getContents(), true);

        $desiredKeys = ['member_id', 'fullname', 'email'];

        $selectedColumns = array_map(function($record) use ($desiredKeys) {
            return array_intersect_key($record, array_flip($desiredKeys));
        }, $response['data']);

        return $selectedColumns;
    }

    function getTaskUsers($taskId) {
        $url = "/tasks/$taskId";
        $response = $this->callRestAPI($url, 'get');
        $response = json_decode($response->getBody()->getContents(), true);
        if ($response['is_self_service']) {
            if (!empty($response['self_service_groups']['groups'])) {
                foreach ($response['self_service_groups']['groups'] as $groupId) {
                    $usersArr[] = $this->getUsersByGroup($groupId);
                }
            }
            /*if (!empty($response['self_service_groups']['users'])) {
                foreach ($response['self_service_groups']['users'] as $userId) {
                    $usersArr[$userId] = $this->getUserData($userId);
                }
            }*/
        }
        return $usersArr;
    }

    function prepareUsersTask($usersTask, $requestsArr) {
        foreach ($usersTask as $user) {
            $result[] = [
                "userId" => $user['member_id'],
                "userEmail" => $user['email'],
                "userFullname" => $user['fullname'],
                "requests" => [$requestsArr]
            ];
        }  
        //print_r($result);die;
        return $result;
    }

    function groupTasksUsers($usersByTask) {
        $result = [];
        foreach ($usersByTask as $group) { 
            foreach ($group as $user) {
                $uid = $user['userId'];
                
                if (!isset($result[$uid])) {
                    $result[$uid] = [
                        'userId' => $user['userId'],
                        'userEmail' => $user['userEmail'],
                        'userFullname' => $user['userFullname'],
                        'requests' => []
                    ];
                }

                foreach ($user['requests'] as $req) {
                    $rid = $req['requestId'];

                    $alreadyExists = false;
                    foreach ($result[$uid]['requests'] as $existing) {
                        if ($existing['requestId'] === $rid) {
                            $alreadyExists = true;
                            break;
                        }
                    }

                    if (!$alreadyExists) {
                        $result[$uid]['requests'][] = $req;
                    }
                }
            }
        }
        return array_values($result);
    }

    function getSelfServiceUsers($selfServiceTasks) {
        $selfServiceTasks = array_values($selfServiceTasks)[0];

        //filter unique Tasks
        foreach ($selfServiceTasks['requests'] as $request) {
            foreach ($request['taskDetails'] as $task) {
                $tasks[$task['taskName']] = $task['taskId'];
            }
        }
        //get users by task
        if (is_array($tasks) && !empty($tasks)) {
            foreach ($tasks as $name => $id) {
                $usersArr[$name] = array_unique($this->getTaskUsers($id)); 
            }
        }

        foreach ($selfServiceTasks['requests'] as $request) {
            foreach ($request['taskDetails'] as $task) {
                $usersTask = $usersArr[$task['taskName']][0];
                $requestsArr = [
                    "case_number" => $request['case_number'],
                    "case_title" => $request['case_title'],
                    "requestId" => $request['requestId'],
                    "updated_at" => $request['updated_at'],
                    "updated_at_formatted" => $request['updated_at_formatted'],
                    "taskDetails" => [$task]
                ];

                $requests2[] = $this->prepareUsersTask($usersTask, $requestsArr);

                /*foreach ($usersTask as $user) {
                        $requests2[$user['member_id']] = [
                            "userId" => $user['member_id'],
                            "userEmail" => $user['email'],
                            "userFullname" => $user['fullname'],
                            "requests" => [$requestsArr]
                        ];     
                }   */                       
            }   
            
        }

        return $this->groupTasksUsers($requests2);
    }

    function mergeUserTasks($pendingTasksByUser, $selfServiceUserTasks) {
        $finalTaskList = [];
        foreach ($pendingTasksByUser as &$userTasks1) {
            foreach ($selfServiceUserTasks as $userTasks2) {
                if ($userTasks1['userId'] == $userTasks2['userId']) {
                    $userTasks1['requests'] = array_merge($userTasks1['requests'], $userTasks2['requests']);
                    $userMatch[] = $userTasks2['userId'];
                } else {
                    $userNoMatch[] = $userTasks2['userId'];
                }
            }            
        }

        if (!empty($userMatch) && is_array($userMatch)) {
            $selfServiceUserTasksNoMatch = array_values(array_filter($selfServiceUserTasks, function($u) use ($userMatch) {
                $id = is_array($u) ? (isset($u['userId']) ? $u['userId'] : null) : (isset($u->userId) ? $u->userId : null);
                return $id !== null && !in_array($id, $userMatch, true);
            }));
            return array_merge($pendingTasksByUser, $selfServiceUserTasksNoMatch);
        } else {
            return array_merge($pendingTasksByUser, $selfServiceUserTasks);
        }             
    }

    function getPendingTasksAndSendEmails() {
        $pendingTasks = $this->getPendingTasksByProcess();

        if (!$pendingTasks) {
            throw new Exception("No pending tasks found."); 
        }
        
        $pendingTasksByUser = $this->getPendingTasksByUser_($pendingTasks);

        if ($this->checkSelfServiceAssignments) {

            $selfServiceTasks = array_filter($pendingTasksByUser, function($task) {
                return $task['userId'] === 'is_self_service';
            });

            $pendingTasksByUser = array_values(array_filter($pendingTasksByUser, function($task) {
                return $task['userId'] !== 'is_self_service';
            }));

            if (!empty($selfServiceTasks)) {
                $selfServiceUserTasks = $this->getSelfServiceUsers($selfServiceTasks);
                $pendingTasksByUser = $this->mergeUserTasks($pendingTasksByUser, $selfServiceUserTasks);
            }
            //return $pendingTasksByUser;
        } else {
            $pendingTasksByUser = array_values(array_filter($pendingTasksByUser, function($task) {
                return $task['userId'] !== 'is_self_service';
            }));
        }

        foreach ($pendingTasksByUser as $i => $userData) {
            $emailBody = $this->useCustomHTML ? $this->prepareCustomEmailBody($userData) : $this->buildEmailBody($userData); 
            $userEmail = ($this->enableTesting && $this->testingEmailTo) ? $this->testingEmailTo : $userData['userEmail'];
            $emailCC = ($this->enableTesting && $this->testingEmailCC) ? $this->testingEmailCC : ($this->emailCC ? $this->emailCC : false);
            $sent = $this->SendEmail($userData['userFullname'], $userEmail, $emailBody, $emailCC);
            if ($sent) {
                $result[] = [
                    "userId" => $userData['userId'],
                    "userFullname" => $userData['userFullname'],
                    "userEmail" => $userEmail,
                    "tasks" => $this->getProcessedTasks($userData['requests'])
                ];
            }
            if ($this->enableTesting && $this->limitEmails == $i) {
                break;
            } 
        }
        return $result;
    }

    function init() {
        try {
            $this->setEmailSettings();
            $this->ConfigureSMTPserver();
            return $this->getPendingTasksAndSendEmails();
        } catch (Exception $e) {
            return [
                "status" => "error",
                "message" => $e->getMessage()
            ];
        }
    }
}

$obj = new emailReminders;
//set variables
/*
{
    "processName": "Invoice Process",
    "tasks": "DHS.01 Review Invoice Form Content",

    "emailSubject": "TEST FINAL",
    "useCustomHTML": false,
    "customEmailBody": "&lt;html&gt; &lt;head&gt; &lt;style&gt; table { border-collapse: collapse; width: 100%; } table thead tr { background-color: #711426; color: #FFF; } th, td { border: 1px solid; padding: 8px; text-align: center; } &lt;/style&gt; &lt;/head&gt; &lt;body&gt; &lt;div style=&quot;text-align:center;&quot;&gt; &lt;img src=&quot;https://northleaf.dev.cloud.processmaker.net/storage/3460/NorthLeafPdfNewLogo.png&quot; height=&quot;50&quot;/&gt; &lt;/div&gt; &lt;p&gt;Dear {userFullname},&lt;/p&gt; &lt;p&gt;You have the following pending tasks:&lt;/p&gt; &lt;h2&gt;Pending Requests&lt;/h2&gt; {pendigTasksTable} &lt;p&gt;Best regards.&lt;/p&gt; &lt;p&gt;Click &lt;a href=&quot;https://northleaf.dev.cloud.processmaker.net/&quot; target=&quot;_blank&quot;&gt;here&lt;/a&gt; to login to Processmaker.&lt;/p&gt; &lt;/body&gt; &lt;/head&gt;",   

    "checkSelfServiceAssignments": true,

    "caseNumberColumn": true,
    "requestIdColumn": true,
    "caseTitleColumn": true,
    "processNameColumn": true,
    "taskNameColumn": true,
    "lastUpdatedDateColumn": true,

    "enableTesting": true,
    "testingEmailTo": "marcelo.cuiza+ertest@processmaker.com",    
    "testingEmailCC": "marcelocuizaticona@gmail.com",
    "limitEmails": 1,

    "batchNumber": 100
}
*/
$config = isset($config["processName"]) ? $config : $data["_parent"]["config"];

$obj->processName = $config['processName'];
$obj->tasks = !empty($config['tasks']) ? array_map(function($a){return trim($a);}, explode(",", $config['tasks'])) : $obj->tasks;

$obj->emailSubject = !empty($config['emailSubject']) ? $config['emailSubject'] : $obj->emailSubject;
$obj->emailCC = !empty($config['emailCC']) ? array_fill_keys(array_map(function($a){return trim($a);}, explode(",", $config['emailCC'])), "CC User") : false;
$obj->useCustomHTML = $config['useCustomHTML'];
$obj->customEmailBody = html_entity_decode($config['customEmailBody']);

$obj->checkSelfServiceAssignments = !empty($config['checkSelfServiceAssignments']) ? $config['checkSelfServiceAssignments'] : $obj->checkSelfServiceAssignments;

$obj->caseNumberColumn = $config['caseNumberColumn'];
$obj->requestIdColumn = $config['requestIdColumn'];
$obj->caseTitleColumn = $config['caseTitleColumn'];
$obj->processNameColumn = $config['processNameColumn'];
$obj->taskNameColumn = $config['taskNameColumn'];
$obj->lastUpdatedDateColumn = $config['lastUpdatedDateColumn'];

$obj->enableTesting = !empty($config['enableTesting']) ? $config['enableTesting'] : $obj->enableTesting;
$obj->testingEmailTo = $config['testingEmailTo'];
$obj->testingEmailCC = !empty($config['testingEmailCC']) ? array_fill_keys(array_map(function($a){return trim($a);}, explode(",", $config['testingEmailCC'])), "Test User") : false;
$obj->limitEmails = !empty($config["limitEmails"]) ? (int)$config["limitEmails"] - 1 : $obj->batchNumber;

$obj->batchNumber = !empty($config['batchNumber']) ? $config['batchNumber'] : $obj->batchNumber;

$obj->current_page = !empty($data['current_page']) ? (int)$data['current_page']+1 : 1; 

$processedBatches = $obj->init();

$totalEmailsSent = !empty($data['totalEmailsSent']) ? (int)$data['totalEmailsSent']+count($processedBatches) : count($processedBatches);
$emailsBatch['batch_'.$obj->current_page] = $processedBatches;
$emailsSent = !empty($data['emailsSent']) ? array_merge($data['emailsSent'], $emailsBatch) : $emailsBatch; 

$completed = ($obj->current_page == $obj->total_pages) ? true : false;
if (
    ($obj->enableTesting && !empty($config["limitEmails"])) ||
    (isset($processedBatches['status']) && $processedBatches['status'] == 'error')
    ) {
    $completed = true;
}

return [
    'completed' => $completed,
    'processName' => $obj->processName,
    'tasks' => $obj->tasks,
    'emailSubject' => $obj->emailSubject,
    'batchNumber' => $obj->batchNumber,
    'emailsSent' =>$emailsSent,
    "totalEmailsSent" => $totalEmailsSent,
    'current_page' => $obj->current_page,
    'totalPages' => $obj->total_pages,    
    'totalRequests' => $obj->totalRequests,
    'enableTesting' => $obj->enableTesting,
    'testingEmailTo' => $obj->testingEmailTo,
    'testingEmailCC' => $obj->testingEmailCC,
    'limitEmails' => $obj->limitEmails
];