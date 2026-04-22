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
require_once("/Northleaf_PHP_Library.php");
$apiHost = getenv('API_HOST');
$apiSql = getenv('API_SQL');
$apiUrl = $apiHost . $apiSql;

$action = $data['action'];

switch ($action) {
    case 'getInvoiceData':
        $requestId = $data['IN_REQUEST_ID'];
        $query  = "SELECT * 
                    FROM EXPENSE_TABLE 
                    WHERE IN_EXPENSE_CASE_ID = '" . $requestId . "'
                    ORDER BY CAST(IN_EXPENSE_ROW_NUMBER as UNSIGNED) ASC ; ";
        break;
    case 'submitterData':
        $userID = $data['userID'];
        $query = "SELECT json_extract(DEP.data, '$.SUBMITTER_OFFICE.NL_OFFICE_SYSTEM_ID_DB') as OFFICE_ID,
				json_extract(DEP.data, '$.SUBMITTER_OFFICE.OFFICE_LABEL') as OFFICE_NAME,
				json_extract(DEP.data, '$.SUBMITTER_DEPARTMENT.NL_DEPARTMENT_SYSTEM_ID_DB') as DEPARMENT_ID,
				json_extract(DEP.data, '$.SUBMITTER_DEPARTMENT.DEPARTMENT_LABEL') as DEPARMENT_NAME
				FROM collection_" . getCollectionId('IN_SUBMITTER_DEPARTMENT', $apiUrl) . " AS DEP 
				WHERE json_extract(DEP.data, '$.SUBMITTER.id') = " . $userID;
        break;
    case 'accountList':
        $departamentId = $data['departamentId'];
        $vendorId = $data['vendorId'];
        if (!empty($departamentId) && !empty($vendorId)) {
            //show full list of Active Accounts for 061 (Corporate Finance)
            $query = '
                SELECT * FROM (
                    SELECT 
                        data->>"$.EXPENSE_VENDOR_DEFAULT_ACCOUNT.ID" AS ID,
                        data->>"$.EXPENSE_VENDOR_DEFAULT_ACCOUNT.LABEL" AS LABEL,
                        0 AS sort_order
                    FROM collection_' . getCollectionId('IN_VENDORS', $apiUrl) . ' AS VENDOR
                    WHERE data->"$.VENDOR_SYSTEM_ID_ACTG" = "' . $vendorId . '"

                    UNION

                    SELECT 
                        data->>"$.NL_ACCOUNT_SYSTEM_ID_ACTG" AS ID,
                        data->>"$.ACCOUNT_LABEL" AS LABEL,
                        1 AS sort_order
                    FROM collection_' . getCollectionId('IN_EXPENSE_ACCOUNT', $apiUrl) . ' AS ACCOUNT
                    WHERE data->"$.ACCOUNT_STATUS" = "Active"
                    AND (
                        "' . $departamentId . '" = "061"
                        OR data->>"$.ACCOUNT_DEPARTMENTS_STRING" IS NULL
                        OR data->>"$.ACCOUNT_DEPARTMENTS_STRING" = ""
                        OR FIND_IN_SET("' . $departamentId . '",
                            REPLACE(data->>"$.ACCOUNT_DEPARTMENTS_STRING", " ", "")
                        )
                    )
                    AND data->>"$.NL_ACCOUNT_SYSTEM_ID_ACTG" NOT IN (
                        SELECT data->>"$.EXPENSE_VENDOR_DEFAULT_ACCOUNT.ID"
                        FROM collection_' . getCollectionId('IN_VENDORS', $apiUrl) . '
                        WHERE data->"$.VENDOR_SYSTEM_ID_ACTG" = "' . $vendorId . '"
                    )
                ) AS RESULT
                ORDER BY sort_order, LABEL
            ';
        } else {    
            $query = 'SELECT data->>"$.NL_ACCOUNT_SYSTEM_ID_ACTG" AS ID, 
                data->>"$.ACCOUNT_LABEL" AS LABEL 
                FROM collection_' . getCollectionId('IN_EXPENSE_ACCOUNT', $apiUrl) . ' AS ACCOUNT 
                WHERE data->"$.ACCOUNT_STATUS" = "Active" 
                ORDER BY LABEL';
        }  
        break;
    case 'activityRecList':
        $query  = "SELECT data->>'$.NL_ACTIVITY_SYSTEM_ID_ACTG' AS ID, 
                data->>'$.ACTIVITY_LABEL' AS LABEL,
                ACTIVITY.data->>'$.ACTIVITY_TYPE' as TYPE 
                FROM collection_" . getCollectionId('IN_EXPENSE_ACTIVITY', $apiUrl) . " AS ACTIVITY 
                WHERE ACTIVITY.data->>'$.ACTIVITY_STATUS' = 'Active' 
                -- AND ACTIVITY.data->>'$.ACTIVITY_TYPE' IN ('" . $recoverableOption . "','Both') 
                ORDER BY LABEL ASC";
        break;
    case 'teamRoutingFullList':
        $query  = "SELECT TEAM_ASSET.data->>'$.ASSET_ID' AS ID, 
                TEAM_ASSET.data->>'$.ASSET_LABEL' AS LABEL,
                TEAM_ASSET.data->>'$.ASSET_TYPE' as TYPE
                FROM collection_" . getCollectionId('IN_ASSET_CLASS', $apiUrl) . " AS TEAM_ASSET 
                WHERE TEAM_ASSET.data->>'$.DEAL_STATUS' = 'Active' 
                -- AND TEAM_ASSET.data->>'$.ASSET_TYPE' = '" . $recoverableOption . "' 
                ORDER BY LABEL ASC";        
        break;
    case 'corpProjList':
        $query = 'SELECT ID.data->>"$.NL_COMPANY_SYSTEM_ID_ACTG" AS ID, 
                ID.data->>"$.EXPENSE_CORPORATE_LABEL" AS LABEL 
                FROM collection_' . getCollectionId('IN_EXPENSE_CORP_PROJ', $apiUrl) . ' AS ID 
                WHERE JSON_UNQUOTE(ID.data->"$.NL_CORPPROJ_STATUS") = "Active" 
                ORDER BY LABEL';
        break;
    case 'corpEntityList':
        $query  = "SELECT data->>'$.NL_COMPANY_SYSTEM_ID_ACTG' AS ID, 
                data->>'$.EXPENSE_CORPORATE_LABEL' AS LABEL 
                FROM collection_" . getCollectionId('IN_EXPENSE_CORP_ENTITY', $apiUrl) . " AS CORP 
                WHERE CORP.data->>'$.EXPENSE_CORPORATE_STATUS' = 'Active' 
                ORDER BY LABEL ASC";
        break;
    case 'expenseDefault':
        $query  = "SELECT data->>'$.DEFAULT_REQUIRED' AS DEFAULT_REQUIRED, 
                data->>'$.DEFAULT_ID' AS DEFAULT_ID, 
                data->>'$.DEFAULT_TYPE' AS DEFAULT_TYPE, 
                data->>'$.DEFAULT_LABEL' AS DEFAULT_LABEL, 
                data->>'$.DEFAULT_STATUS' AS DEFAULT_STATUS, 
                data->>'$.DEFAULT_DISABLE' AS DEFAULT_DISABLE, 
                data->>'$.DEFAULT_VARIABLE' AS DEFAULT_VARIABLE 
                FROM collection_" . getCollectionId('IN_EXPENSE_DEFAULTS', $apiUrl) . " 
                WHERE data->>'$.DEFAULT_STATUS' = 'Active'";
        break;
    case 'departmentList':
        $query = "SELECT data->>'$.NL_DEPARTMENT_SYSTEM_ID_DB' AS ID, data->>'$.DEPARTMENT_LABEL' AS LABEL
				FROM collection_" . getCollectionId('IN_EXPENSE_DEPARTMENT', $apiUrl) . " AS DEP 
				WHERE DEP.data->>'$.DEPARTMENT_STATUS' = 'Active' 
				ORDER BY LABEL ASC";
        break;
    case 'officeList':
        $query = "SELECT data->>'$.NL_OFFICE_SYSTEM_ID_DB' AS ID, data->>'$.OFFICE_LABEL' AS LABEL
				FROM collection_" . getCollectionId('IN_EXPENSE_OFFICE', $apiUrl) . " AS OFFICE 
				WHERE OFFICE.data->>'$.OFFICE_STATUS' = 'Active' 
				ORDER BY LABEL ASC";
        break;

}
$response = callApiUrlGuzzle($apiUrl, "POST", encodeSql($query));
$response = array_values($response);

if($action == 'activityRecList'){
    $activityRecList = array_filter($response, function($v, $k) {
        return $v['TYPE'] == 'Recoverable' || $v['TYPE'] == 'Both';
    }, ARRAY_FILTER_USE_BOTH);
    $activityNoRecList = array_filter($response, function($v, $k) {
        return $v['TYPE'] == 'Non-Recoverable' || $v['TYPE'] == 'Both';
    }, ARRAY_FILTER_USE_BOTH);
    $response = [
        "activityRecList" => array_values($activityRecList),
        "activityNoRecList" => array_values($activityNoRecList)
    ];
}
if($action == 'teamRoutingFullList'){
    $teamRoutingFullList = $response;
    $teamRoutingRecList = array_filter($response, function($v, $k) {
        return $v['TYPE'] == 'Recoverable';
    }, ARRAY_FILTER_USE_BOTH);
    $teamRoutingNoRecList = array_filter($response, function($v, $k) {
        return $v['TYPE'] == 'Non-recoverable';
    }, ARRAY_FILTER_USE_BOTH);
    $teamRoutingFullLabel = array_column($teamRoutingFullList, 'LABEL');
    $mandateList = [];
    $fundManagerList = [];
    $dealList = [];


    $query1  = "SELECT data->>'$.DEAL_SYSTEM_ID_DB' AS ID, 
            data->>'$.DEAL_LABEL' AS LABEL,
            DEAL.data->>'$.DEAL_ASSETCLASS.ASSET_LABEL' as ASSET
            FROM collection_" . getCollectionId('IN_DEAL', $apiUrl) . " AS DEAL 
            WHERE DEAL.data->>'$.DEAL_STATUS' = 'Active' 
            -- AND DEAL.data->>'$.DEAL_ASSETCLASS.ASSET_LABEL' = '" . $assetLabel . "' 
            ORDER BY LABEL ASC";
	$response1 = callApiUrlGuzzle($apiUrl, "POST", encodeSql($query1));
    $dealInfra = array_filter($response1, function($v, $k) {
        return $v['ASSET'] == 'Infrastructure';
    }, ARRAY_FILTER_USE_BOTH);
    $dealList['Infrastructure'] = array_values($dealInfra);
    $dealCorp = array_filter($response1, function($v, $k) {
        return $v['ASSET'] == 'Corporate';
    }, ARRAY_FILTER_USE_BOTH);
    $dealList['Corporate'] = array_values($dealCorp);
    $dealPC = array_filter($response1, function($v, $k) {
        return $v['ASSET'] == 'Private Credit';
    }, ARRAY_FILTER_USE_BOTH);
    $dealList['Private Credit'] = array_values($dealPC);
    $dealPE = array_filter($response1, function($v, $k) {
        return $v['ASSET'] == 'Private Equity';
    }, ARRAY_FILTER_USE_BOTH);
    $dealList['Private Equity'] = array_values($dealPE);


    $query2  = "SELECT data->>'$.DEAL_SYSTEM_ID_DB' AS ID, 
            data->>'$.FUND_MANAGER_LABEL' AS LABEL,
            FUND_MANAGER.data->>'$.FUNDMANAGER_ASSETCLASS.ASSET_LABEL' as ASSET
            FROM collection_" . getCollectionId('IN_EXPENSE_FUND_MANAGER', $apiUrl) . " AS FUND_MANAGER 
            WHERE FUND_MANAGER.data->>'$.FUND_MANAGER_STATUS' = 'Active' 
            -- AND FUND_MANAGER.data->>'$.FUNDMANAGER_ASSETCLASS.ASSET_LABEL' = '" . $assetLabel . "' 
            ORDER BY LABEL ASC";
	$response2 = callApiUrlGuzzle($apiUrl, "POST", encodeSql($query2));
    $fundManInfra = array_filter($response2, function($v, $k) {
        return $v['ASSET'] == 'Infrastructure';
    }, ARRAY_FILTER_USE_BOTH);
    $fundManagerList['Infrastructure'] = array_values($fundManInfra);
    $fundManCorp = array_filter($response2, function($v, $k) {
        return $v['ASSET'] == 'Corporate';
    }, ARRAY_FILTER_USE_BOTH);
    $fundManagerList['Corporate'] = array_values($fundManCorp);
    $fundManPC = array_filter($response2, function($v, $k) {
        return $v['ASSET'] == 'Private Credit';
    }, ARRAY_FILTER_USE_BOTH);
    $fundManagerList['Private Credit'] = array_values($fundManPC);
    $fundManPE = array_filter($response2, function($v, $k) {
        return $v['ASSET'] == 'Private Equity';
    }, ARRAY_FILTER_USE_BOTH);
    $fundManagerList['Private Equity'] = array_values($fundManPE);

    $query3  = "SELECT data->>'$.MANDATE_SYSTEM_ID_ACTG' AS ID, 
            data->>'$.MANDATE_LABEL' AS LABEL,
            MANDATE.data->>'$.MANDATE_ASSETCLASS' as ASSET
            FROM collection_" . getCollectionId('IN_EXPENSE_MANDATES', $apiUrl) . " AS MANDATE 
            WHERE MANDATE.data->>'$.MANDATE_STATUS' = 'Active' 
            -- AND MANDATE.data->>'$.MANDATE_ASSETCLASS' = '" . $assetLabel . "' 
            ORDER BY LABEL ASC";
	$response3 = callApiUrlGuzzle($apiUrl, "POST", encodeSql($query3));
    $mandateInfra = array_filter($response3, function($v, $k) {
        return $v['ASSET'] == 'INFRA';
    }, ARRAY_FILTER_USE_BOTH);
    $mandateList['Infrastructure'] = array_values($mandateInfra);
    $mandateCorp = array_filter($response3, function($v, $k) {
        return $v['ASSET'] == 'CORP';
    }, ARRAY_FILTER_USE_BOTH);
    $mandateList['Corporate'] = array_values($mandateCorp);
    $mandatePC = array_filter($response3, function($v, $k) {
        return $v['ASSET'] == 'PC';
    }, ARRAY_FILTER_USE_BOTH);
    $mandateList['Private Credit'] = array_values($mandatePC);
    $mandatePE = array_filter($response3, function($v, $k) {
        return $v['ASSET'] == 'PE';
    }, ARRAY_FILTER_USE_BOTH);
    $mandateList['Private Equity'] = array_values($mandatePE);
    $response = [
        "teamRoutingFullList" => array_values($teamRoutingFullList),
        "teamRoutingRecList" => array_values($teamRoutingRecList),
        "teamRoutingNoRecList" => array_values($teamRoutingNoRecList),
        "mandateList" => $mandateList,
        "fundManagerList" => $fundManagerList,
        "dealList" => $dealList
    ];
}
if($action == 'expenseDefault'){
    $businessRules = ['RECOVERABLE' => [], 'NON_RECOVERABLE' => []];
	foreach($response as $key => $value) {
		$value['DEFAULT_DISABLE'] = filter_var($value['DEFAULT_DISABLE'], FILTER_VALIDATE_BOOLEAN);
		if($value['DEFAULT_TYPE'] == 'Recoverable') {
			$businessRules['RECOVERABLE'][$value['DEFAULT_VARIABLE']] = $value;
		} else {
			$businessRules['NON_RECOVERABLE'][$value['DEFAULT_VARIABLE']] = $value;
		}
	}
    $response = $businessRules;
}


return $response;