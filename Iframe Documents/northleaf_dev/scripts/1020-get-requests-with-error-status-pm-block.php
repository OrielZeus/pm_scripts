<?php

ini_set('memory_limit', '1024M');

use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Request;

class failedRequestReport
{
    //zendesk
    public $zendesk = false;
    public $zendeskCollaborators = [];
    public $zendeskRequesterEmail;
    public $zendeskTokenVarName;
    public $zendeskResult;

    //
    public $emailSubject = 'Failed Requests Report';
    public $emailTo;
    public $useCustomHTML = false;
    public $customEmailBody = '';
    public $sendEmailIfNoFailed = false;
    public $sendEmailIfNoFailedMessage = '<p>ProcessMaker has not detected any new request with error.<br>Regards.<p>';

    public $caseNumberColumn = true;
    public $requestIdColumn = true;
    public $processNameColumn = true;
    public $caseTitleColumn = true;
    public $errorMessageColumn = true;
    public $dateCreatedColumn = true;
    public $dateErrorColumn = true;

    //
    public $groupByProcess = false;
    public $processesToCheck = [];

    public $hasErrors = false;

    function callRestAPI($uri, $method, $data_to_send = [], $zendeskHeaders = [])
    {
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
            case 'postZendesk':
                $response = $client->post($uri, [
                    'http_errors' => false,
                    'headers' => $zendeskHeaders,
                    'json' => (array) $data_to_send
                ]);
                break;
        }
        return $response;
    }

    function createZendeskTicket($htmlError)
    {
        global $client;
        $data = [
            "ticket" => [
                "comment" => [
                    "html_body" => $htmlError
                ],
                "priority" => "urgent",
                "subject" => $this->emailSubject,
                "collaborators" => $this->zendeskCollaborators,
                /*"collaborators" => [ ["name" => "", "email" => ""] ],*/
                "requester" => [
                    "email" => $this->zendeskRequesterEmail
                ],
                "tags" => ["important", "automaticTicket"]
            ]
        ];

        $url = "https://processmaker.zendesk.com/api/v2/tickets.json";        
        $api_token = getenv($this->zendeskTokenVarName); //pm suppor user
        $headers = [
            "Authorization" => "Basic " . $api_token,
            "Accept" => "application/json",
        ];        
        $response = $this->callRestAPI($url, 'postZendesk', $data, $headers);
        return json_decode($response->getBody()->getContents(), true);
    }

    function getTableHeader() 
    {
        $tableHead = '<thead><tr style="font-weight: bold;">';
        if ($this->caseNumberColumn) $tableHead .= '<th style="width: 8%;">Case #</th>';
        if ($this->requestIdColumn) $tableHead .= '<th style="width: 9%;">Request #</th>';
        if ($this->processNameColumn) $tableHead .= '<th>Process</th>';
        if ($this->caseTitleColumn) $tableHead .= '<th>Case Title</th>';
        if ($this->errorMessageColumn) $tableHead .= '<th style="width: 35%; word-wrap: break-word;">Error Message</th>';
        if ($this->dateCreatedColumn) $tableHead .= '<th>Date Created</th>';
        if ($this->dateErrorColumn) $tableHead .= '<th>Date Error</th>';
        $tableHead .= '</tr></thead>';

        return $tableHead;
    }

    function getTableBody($errorRequests) 
    {
        $tableBody = '<tbody>';
        foreach ($errorRequests as $error) {                        
            $tableBody .= '<tr>';
            if ($this->caseNumberColumn) $tableBody .= '<td>' . $error['case_number'] . '</td>';
            if ($this->requestIdColumn) $tableBody .= '<td>' . $error['id'] . '</td>';
            if ($this->processNameColumn) $tableBody .= '<td>' . $error['name'] . '</td>';
            if ($this->caseTitleColumn) $tableBody .= '<td>' . $error['case_title'] . '</td>';
            if ($this->errorMessageColumn) $tableBody .= '<td style="word-wrap: break-word;">' . $error['errors'][0]['message'] . '</td>';
            if ($this->dateCreatedColumn) $tableBody .= '<td>' . date("m/d/Y H:i:s", strtotime($error['created_at'])) . '</td>';
            if ($this->dateErrorColumn) $tableBody .= '<td>' . date("m/d/Y H:i:s", strtotime($error['errors'][0]['created_at'])) . '</td>';
            $tableBody .= '</tr>';
        }
        $tableBody .= '</tbody>';

        return $tableBody;
    }

    function buildTasksTable($errorRequests) 
    {
        if ($this->groupByProcess) {
            $gruped = [];
            foreach ($errorRequests as $item) {
                $gruped[$item['name']][] = $item;
            }

            foreach ($gruped as $key => $process) {     
                $table .= '<table>';
                $table .= "<caption>$key</caption>";
                $table .= $this->getTableHeader();
                $table .= $this->getTableBody($process);
                $table .= '</table>';
                $table .= '<p>&nbsp;</p>';
            }
        } else {
            $table .= '<table>';
            $table .= $this->getTableHeader();
            $table .= $this->getTableBody($errorRequests);
            $table .= '</table>';
        }
        
        return $table;
    }

    function prepareCustomEmailBody($errorRequests)
    {
        $body = $this->customEmailBody;
        $replacements = [
            '{reportTable}' => $this->buildTasksTable($errorRequests)
        ];
        $body = str_replace(array_keys($replacements), array_values($replacements), $body);
        return str_replace(["\r\n", "\r", "\n"], "", $body);
    }

    function buildEmailBody($errorRequests)
    {
        $htmlError = '<html>' . '<head>' . 
        '<style>' . 
            'table {border-collapse: collapse; width: 130%; table-layout: fixed;}' . 
            'table thead tr {background-color: #1D6CF2; color: #FFF; height: 40px;}' . 
            'table caption {margin: 10px; font-size: large; font-weight: bolder;}' . 
            'th, td {border: 1px solid; padding: 1px; text-align: center;}' . 
        '</style>' . '</head>' . '<body>';

        $htmlError .= '<p>ProcessMaker has detected the following requests with error status:</p>';
        
        $htmlError .= $this->buildTasksTable($errorRequests); 

        $htmlError .= '<p>Please check the requests.<br>Thank you.<p>';
        $htmlError .= '</body></html>';

        $htmlError = str_replace(["\r\n", "\r", "\n"], "", $htmlError);
        return stripslashes($htmlError);
        //return str_replace('\"', '"', $htmlError);
    }

    function getProcessesRequests()
    {
        $processesToCheck = $this->processesToCheck;
        $params = [];
        $params['pmql'] = '(status="Error")';
        
        if (is_array($processesToCheck) && sizeof($processesToCheck)) {
            $imploded_string = implode('","', $processesToCheck);
            // Add the leading and trailing quotes
            $processes_string = '"' . $imploded_string . '"';
            $params['pmql'] .= " AND (request in [$processes_string])";
        } 
        
        $params['per_page'] = '999';
        //$params['include'] = 'data';
        $params['order_by'] = 'id';
        $params['order_direction'] = 'DESC';
        $queryString = http_build_query($params);
        $url = "/requests?$queryString";
        $response = $this->callRestAPI($url, 'get');
        return json_decode($response->getBody()->getContents(), true);
    }

    function init()
    {
        try {
            $errorRequests = $this->getProcessesRequests();
            if (!empty($errorRequests['data']) && is_array($errorRequests['data']) && sizeof($errorRequests['data'])) {
                $htmlReport = $this->useCustomHTML ? $this->prepareCustomEmailBody($errorRequests['data']) : $this->buildEmailBody($errorRequests['data']); 
                $this->hasErrors = true;
                if ($this->zendesk) {
                    $this->zendeskResult = $this->createZendeskTicket($htmlReport);
                }
            } else {
                $htmlReport = $this->sendEmailIfNoFailedMessage;
            }
            return $htmlReport;
        } catch (Exception $e) {
            return [
                "status" => "error",
                "message" => $e->getMessage()
            ];
        }
    }
}

$config = isset($config["emailTo"]) ? $config : $data["_parent"]["config"];

$obj = new failedRequestReport;
/*
//set config variables
{
    "processesToCheck": "HR Offboarding Process, Invoice Process,Private Equity Deal Closing Process, Private Equity Deal Closing Process Second Part,Private Equity Deal Closing Process Third Part",
    "groupByProcess": false,

    "emailSubject": "Dev - Failed Requests Report",
    "emailTo": "marcelo.cuiza@decisions.com",

    "sendEmailIfNoFailed": true,
    "sendEmailIfNoFailedMessage": "&lt;html&gt; &lt;head&gt; &lt;style&gt; table { border-collapse: collapse; width: 100%; } table thead tr { background-color: #711426; color: #FFF; } th, td { border: 1px solid; padding: 8px; text-align: center; } &lt;/style&gt; &lt;/head&gt; &lt;body&gt; &lt;div style=&quot;text-align:center;&quot;&gt; &lt;img src=&quot;https://northleaf.dev.cloud.processmaker.net/storage/3460/NorthLeafPdfNewLogo.png&quot; height=&quot;50&quot;/&gt; &lt;/div&gt; &lt;p style=&quot;width:130%;font-weight:bold;font-size:1.2rem;border-bottom:2px solid #711426&quot; aria-hidden=&quot;true&quot;&gt;&amp;nbsp;&lt;/p&gt; &lt;p&gt;ProcessMaker has not detected any new request with error status:&lt;/p&gt; &lt;p&gt;&amp;nbsp;&lt;/p&gt; {reportTable} &lt;p&gt;&amp;nbsp;&lt;/p&gt; &lt;p&gt;Please check the requests.&lt;br&gt;Thank you.&lt;p&gt; &lt;p&gt;Click &lt;a href=&quot;https://northleaf.dev.cloud.processmaker.net/&quot; target=&quot;_blank&quot;&gt;here&lt;/a&gt; to login to Processmaker.&lt;/p&gt; &lt;/body&gt; &lt;/html&gt;",

    "useCustomHTML": false,
    "customEmailBody": "",    

    "caseNumberColumn": true,
    "requestIdColumn": true,
    "processNameColumn": true,
    "caseTitleColumn": true,
    "errorMessageColumn": true,
    "dateCreatedColumn": true,
    "dateErrorColumn": true,

    "zendesk": false,
    "zendeskCollaborators": "marcelo.cuiza@decisions.com, marcelo.cuiza@processmaker.com",
    "zendeskRequesterEmail": "support-zendesk@processmaker.com",
    "zendeskTokenVarName": "ZENDESK_API_TOKEN"
}
*/

$obj->processesToCheck = isset($config['processesToCheck']) ? array_map('trim', explode(',', $config['processesToCheck'])) : $obj->processesToCheck;
$obj->groupByProcess = $config['groupByProcess'];

$obj->emailSubject = isset($config['emailSubject']) ? $config['emailSubject'] : $obj->emailSubject;
$obj->emailTo = $config['emailTo'];

$obj->sendEmailIfNoFailed = $config['sendEmailIfNoFailed'];
$obj->sendEmailIfNoFailedMessage = isset($config['sendEmailIfNoFailedMessage']) ? html_entity_decode($config['sendEmailIfNoFailedMessage']) : $obj->sendEmailIfNoFailedMessage;
$obj->useCustomHTML = $config['useCustomHTML'];
$obj->customEmailBody = html_entity_decode($config['customEmailBody']);

$obj->caseNumberColumn = isset($config['caseNumberColumn']) ? $config['caseNumberColumn'] : $obj->caseNumberColumn;
$obj->requestIdColumn = isset($config['requestIdColumn']) ? $config['requestIdColumn'] : $obj->requestIdColumn;
$obj->processNameColumn = isset($config['processNameColumn']) ? $config['processNameColumn'] : $obj->processNameColumn;
$obj->caseTitleColumn = isset($config['caseTitleColumn']) ? $config['caseTitleColumn'] : $obj->caseTitleColumn;
$obj->errorMessageColumn = isset($config['errorMessageColumn']) ? $config['errorMessageColumn'] : $obj->errorMessageColumn;
$obj->dateCreatedColumn = isset($config['dateCreatedColumn']) ? $config['dateCreatedColumn'] : $obj->dateCreatedColumn;
$obj->dateErrorColumn = isset($config['dateErrorColumn']) ? $config['dateErrorColumn'] : $obj->dateErrorColumn;

$obj->zendesk = isset($config['zendesk']) ? $config['zendesk'] : $obj->zendesk;
$obj->zendeskRequesterEmail = $config['zendeskRequesterEmail'];
$obj->zendeskCollaborators = isset($config['zendeskCollaborators']) ? array_map('trim', explode(',', $config['zendeskCollaborators'])) : $obj->zendeskCollaborators;
$obj->zendeskTokenVarName = $config['zendeskTokenVarName'];
if (!getenv($obj->zendeskTokenVarName) || empty($obj->zendeskRequesterEmail)) {
    $obj->zendesk = false;
}

$htmlReport = $obj->init();

return [
    'sendEmail' => ($obj->hasErrors || $obj->sendEmailIfNoFailed) ? true : false,
    'htmlReport' => $htmlReport,
    'emailSubject' => $obj->emailSubject,
    'processesToCheck' => $obj->processesToCheck,
    'emailTo' => $obj->emailTo,
    'zendesk' => $obj->zendeskResult['ticket']['id']
];