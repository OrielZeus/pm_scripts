<?php 
$config = [
    'headers' => ['Authorization' => 'Bearer ' . getenv('API_TOKEN')],
];
$client = new \GuzzleHttp\Client($config);

function sendZendeskTicket($htmlError) {
    global $client;

    $data = [ 
        "ticket" => [
            "comment" => [
                "html_body" => $htmlError
            ],
            "priority" => "urgent",
            "subject" => "(Dev) Error Requests Report",
            "collaborators" => [ 
                [
                    "name" => "Marcelo Cuiza", 
                    "email" => "marcelo.cuiza@processmaker.com"
                ]
            ],
            "requester" => [
                "email" => "client@email.com"
            ],
            "tags"=> ["important", "ClientCompany", "automaticTicket"]
        ]
    ];

    $url = "https://processmaker.zendesk.com/api/v2/tickets.json";   
    $api_token = "xxxx..."; //pm suppor user

    $headers = [
        "Authorization" => "Basic " . $api_token,
        "Accept"        => "application/json",
    ];
    $res = $client->request("POST", $url, [
        "http_errors" => false,
        "headers" => $headers,
        "json" => (array) $data
    ]);

    return json_decode($res->getBody(), true);
}

try{
    $processesToCheck = [
        'HR Offboarding Process',
        'Invoice Process', 
        'Private Equity Deal Closing Process', 
        'Private Equity Deal Closing Process Second Part',
        'Private Equity Deal Closing Process Third Part'    
    ];
    $imploded_string = implode('","', $processesToCheck);
    // Add the leading and trailing quotes
    $final_string = '"' . $imploded_string . '"';

    $params = [];
    $params['pmql'] = "(request in [$final_string])";
    $params['pmql'] .= ' AND (status="Error")';
    $params['per_page'] = '999';
    //$params['include'] = 'data';
    $params['order_by'] = 'id';
    $params['order_direction'] = 'DESC';
    $queryString = http_build_query($params);

    $response = $client->request('GET', getenv('API_HOST') . '/requests?'.$queryString);
    $errorRequests = json_decode($response->getBody());
//return $errorRequests->data;    
    $htmlError = '';
    if (count($errorRequests->data) > 0) {
         $htmlError = '<html>' .
            '<head>' .
            '<style>' .
                'table {border-collapse: collapse; width: 130%; table-layout: fixed;}' .
                'table thead tr {background-color: #711426; color: #FFF; height: 40px;}' .
                'th, td {border: 1px solid; padding: 1px; text-align: center;}' .
            '</style>' .
            '</head>' .
            '<body>';
        $htmlError .= '<p>ProcessMaker has detected the following requests with error status:</p>';
        $htmlError .= '<table>';
        //$htmlError .= '<table style="width: 100%; table-layout: fixed; border: solid; margin-left: auto; margin-right: auto; margin-top: 1rem; margin-bottom: 1rem;">'
        $htmlError .= '<thead><tr style="font-weight: bold;">
            <th style="width: 8%;">Case #</th>
            <th style="width: 9%;">Request #</th>
            <th>Process</th>
            <th style="width: 17%;">Case Title</th>
            <th style="width: 40%; word-wrap: break-word;">Error Message</th>
            <th>Date Created</th>
            <th>Date Error</th>
        </tr></thead>';
        foreach ($errorRequests->data as $error) {
            $htmlError .= '<tr>';
            $htmlError .= '<td>' . $error->case_number . '</td>';
            $htmlError .= '<td>' . $error->id . '</td>';
            $htmlError .= '<td>' . $error->name . '</td>';
            $htmlError .= '<td>' . $error->case_title . '</td>';
            $htmlError .= '<td style="word-wrap: break-word;">' . $error->errors[0]->message . '</td>';            
            $htmlError .= '<td>' . date("m/d/Y H:i:s", strtotime($error->created_at)) . '</td>';
            $htmlError .= '<td>' . date("m/d/Y H:i:s", strtotime($error->errors[0]->created_at)) . '</td>';
            $htmlError .= '</tr>';
        }
        $htmlError .= '</table>';

        $htmlError .= '<p>Please check the requests.<br>Thank you.<p>';
        $htmlError .= '</body></html>';

        //$zendesk = sendZendeskTicket($htmlError);
    } else {
        $htmlError .= '<p>ProcessMaker has not detected any new request with error.<br>Regards.<p>';
    }

    return [
        'body_content_error' => $htmlError,
        'totalErrorRequests' => count($errorRequests->data),
        'sendEmail' => true,
        'zendesk' => $zendesk
    ];
} catch (Exception $exception) {
    return ['content' => $htmlError, 'error' => $exception->getMessage()];
}