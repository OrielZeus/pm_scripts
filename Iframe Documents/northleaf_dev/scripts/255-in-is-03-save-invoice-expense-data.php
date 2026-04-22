<?php 

require_once("/Northleaf_PHP_Library.php");

$apiHost = getenv('API_HOST');
$apiSql = getenv('API_SQL');
$apiUrl = $apiHost . $apiSql;

try {
    if(isset($data['JUMP_WATCHER'])){
        return [
            'status' => $data['JUMP_WATCHER']
        ];
    }
    $requestId = $data['IN_REQUEST_ID'];
    if(isset($data['CORPORATE']) AND $data['CORPORATE']){
        $dataGrid            = html_entity_decode($data['IN_DATA_GRID_1']);
        $textBase            = base64_decode($dataGrid);
        $newText             = mb_convert_encoding($textBase, 'UTF-8', mb_list_encodings());
        $expenseData         = json_decode($newText,true);
        $dataGrid            = html_entity_decode($data['IN_DATA_GRID_2']);
        $textBase            = base64_decode($dataGrid);
        $newText             = mb_convert_encoding($textBase, 'UTF-8', mb_list_encodings());
        $expenseDataDisabled = json_decode($newText,true);
        //return $expenseDataDisabled;
        
        $query = "DELETE FROM EXPENSE_TABLE WHERE IN_EXPENSE_CASE_ID = '" . $requestId . "'";
        $response = callApiUrlGuzzle($apiUrl, "POST", encodeSql($query));
        
        /*$dataGrid            = html_entity_decode($data['IN_DATA_GRID_1']);
        $expenseData         = json_decode(base64_decode($dataGrid),true);
        $dataGrid            = html_entity_decode($data['IN_DATA_GRID_2']);
        $expenseDataDisabled = json_decode(base64_decode($dataGrid),true);*/
        $mainIdex            = 0;
        foreach ($expenseDataDisabled as $key => $expense){
            $mainIdex++;
            $expense['IN_EXPENSE_DESCRIPTION'] = addslashes($expense['IN_EXPENSE_DESCRIPTION']);
            $expense['IN_EXPENSE_ROW_NUMBER'] = $mainIdex;
            $expense['IN_EXPENSE_TEAM_ROW_INDEX'] = $mainIdex;
            //$created[] = prepareExpense($apiUrl, $expense, $requestId);
            $created[] = $expense;
        }
        foreach ($expenseData as $key => $expense){
            $mainIdex++;
            $expense['IN_EXPENSE_DESCRIPTION'] = addslashes($expense['IN_EXPENSE_DESCRIPTION']);
            $expense['IN_EXPENSE_ROW_NUMBER'] = $mainIdex;
            $expense['IN_EXPENSE_TEAM_ROW_INDEX'] = $mainIdex;
            //$created[] = prepareExpense($apiUrl, $expense, $requestId);
            $created[] = $expense;
        }
        $created = array_chunk($created, 30);
        foreach($created as $batch){
            $createExpences = preparateExpense($batch, $requestId);
            $response = callApiUrlGuzzle($apiUrl, "POST", encodeSql($createExpences));
        }


        /*
        $query = "SELECT max(IN_EXPENSE_TEAM_ROW_INDEX) as maxIndex
                    FROM EXPENSE_TABLE
                    WHERE IN_EXPENSE_CASE_ID = '" . $requestId . "'
                    and IN_EXPENSE_TEAM_ROUTING_ID != 'CORP'";
        $response = callApiUrlGuzzle($apiUrl, "POST", encodeSql($query));
        $maxIndex = isset($response[0]) AND isset($response[0]['maxIndex']) ? $response[0]['maxIndex'] : 0;
        $query = "DELETE FROM EXPENSE_TABLE WHERE IN_EXPENSE_CASE_ID = '" . $requestId . "' AND IN_EXPENSE_TEAM_ROUTING_ID = 'CORP'";
        $response = callApiUrlGuzzle($apiUrl, "POST", encodeSql($query));
        foreach ($expenseData as $key => $expense){
            $maxIndex++;
            $expense['IN_EXPENSE_TEAM_ROW_INDEX'] = $maxIndex;
            $created[] = prepareExpense($apiUrl, $expense, $requestId);
        }*/
        return [sizeof($created),$updated];
    }
    
    $dataGrid    = html_entity_decode($data['IN_DATA_GRID']);
    $textBase    = base64_decode($dataGrid);
    $newText     = mb_convert_encoding($textBase, 'UTF-8', mb_list_encodings());
    $expenseData = json_decode($newText,true);

    if((isset($data['ALLOCATION']) AND $data['ALLOCATION'] == "SUBMIT") OR isset($data['TEAM_ID'])){
        if(isset($data['TEAM_ID'])){
            if($data['ACTION'] != 'SUBMIT'){
                foreach ($expenseData as $key => $expense){
                    $existRowId = readExpense($apiUrl, $expense['IN_EXPENSE_ROW_ID']);
                    if(empty($existRowId)){
                        $expense['IN_EXPENSE_DESCRIPTION'] = addslashes($expense['IN_EXPENSE_DESCRIPTION']);
                        //$created[] = prepareExpense($apiUrl, $expense, $requestId);
                        $created[] = $expense;
                    } else {
                        $updated[] = updateExpense($apiUrl, $expense);
                    }
                }
                $created = array_chunk($created, 30);
                foreach($created as $batch){
                    $createExpences = preparateExpense($batch, $requestId);
                    $response = callApiUrlGuzzle($apiUrl, "POST", encodeSql($createExpences));
                }
                //newSortData($requestId);
                return [sizeof($created), $updated];
            }
        }
        else{
            $rowIndexes = array_column($expenseData, 'IN_EXPENSE_TEAM_ROW_INDEX');
            array_multisort($rowIndexes, SORT_DESC, $expenseData);
            $deletedRow =[];

            if(isset($data['TEAM_NAME'])){
                $teamName = $data['TEAM_NAME'];
                $query    = "DELETE FROM EXPENSE_TABLE WHERE IN_EXPENSE_CASE_ID = '" . $requestId . "' AND IN_EXPENSE_TEAM_ROUTING_ID = '" . $teamName . "'";
                $response = callApiUrlGuzzle($apiUrl, "POST", encodeSql($query));
                foreach($expenseData as $rowData){
                    $rowData['IN_EXPENSE_DESCRIPTION'] = addslashes($rowData['IN_EXPENSE_DESCRIPTION']);
                    $created[] = $rowData;
                }
            }
            else{
                foreach($expenseData as $rowData){
                    $rowId = $rowData['IN_EXPENSE_TEAM_ROW_INDEX'];
                    if (!in_array($rowId, $deletedRow)){
                        //delete rows 
                        $query = "DELETE FROM EXPENSE_TABLE WHERE IN_EXPENSE_CASE_ID = '" . $requestId . "' AND IN_EXPENSE_TEAM_ROW_INDEX = '" . $rowId . "'";
                        $response = callApiUrlGuzzle($apiUrl, "POST", encodeSql($query));
                        $deletedRow[] = $rowId;
                    }
                    $rowData['IN_EXPENSE_DESCRIPTION'] = addslashes($rowData['IN_EXPENSE_DESCRIPTION']);
                    $created[] = $rowData;
                }
            }

            $created = array_chunk($created, 30);
            foreach($created as $batch){
                $createExpences = preparateExpense($batch, $requestId);
                $response = callApiUrlGuzzle($apiUrl, "POST", encodeSql($createExpences));
            }
            return $deletedRow;
        }
    }
    else{
        if(isset($data['ALLOCATION']) AND $data['ALLOCATION'] == "BACK"){
            return [
                "saveWithActions" => true
            ];
        }
        

        if(isset($data['TEAM_NAME'])){
            $teamName = $data['TEAM_NAME'];
            $query    = "DELETE FROM EXPENSE_TABLE WHERE IN_EXPENSE_CASE_ID = '" . $requestId . "' AND IN_EXPENSE_TEAM_ROUTING_ID = '" . $teamName . "'";
            $response = callApiUrlGuzzle($apiUrl, "POST", encodeSql($query));
            foreach($expenseData as $rowData){
                $rowData['IN_EXPENSE_DESCRIPTION'] = addslashes($rowData['IN_EXPENSE_DESCRIPTION']);
                $created[] = $rowData;
            }
        }
        else{
            $created = [];
            $updated = [];
            $indexTa = 0;
            $query   = "DELETE FROM EXPENSE_TABLE WHERE IN_EXPENSE_CASE_ID = '" . $requestId . "'";
            $response = callApiUrlGuzzle($apiUrl, "POST", encodeSql($query));
            foreach ($expenseData as $key => $expense){
                $indexTa++;
                $expense['IN_EXPENSE_DESCRIPTION'] = addslashes($expense['IN_EXPENSE_DESCRIPTION']);
                $expense['IN_EXPENSE_ROW_NUMBER'] = $indexTa;
                $expense['IN_EXPENSE_TEAM_ROW_INDEX'] = $indexTa;
                //$created[] = prepareExpense($apiUrl, $expense, $requestId);
                $created[] = $expense;
            }
        }
        $created = array_chunk($created, 30);
        foreach($created as $batch){
            $createExpences = preparateExpense($batch, $requestId);
            $response = callApiUrlGuzzle($apiUrl, "POST", encodeSql($createExpences));
        }
        //return $indexTa;
        return [
            "logSaveTable" => [
                "date" => date('Y-m-d H:i:s'),
                "numItems" => sizeof($created)
            ]
        ];
        //newSortData($requestId);

        /*foreach ($expenseData as $key => $expense){
            $existRowId = readExpense($apiUrl, $expense['IN_EXPENSE_ROW_ID']);
            if(empty($existRowId)){
                $created[] = prepareExpense($apiUrl, $expense, $requestId);
            } else {
                $updated[] = updateExpense($apiUrl, $expense);
            }
        }
        newSortData($requestId);
        return [$created, $updated];
        */
    }
    
} catch(Exception $exception){
    return 'Error' . $exception->getMessage();
}


function newSortData($requestId){
    global $apiUrl;
    $query = "SELECT IN_EXPENSE_ROW_ID,
                    IN_EXPENSE_ROW_NUMBER,
                    IN_EXPENSE_TEAM_ROW_INDEX,
                    ROUND((
                        SELECT sum(IN_EXPENSE_TOTAL) FROM EXPENSE_TABLE WHERE IN_EXPENSE_CASE_ID = '" . $requestId . "')
                    ,2) as TOTAL_AMOUNT,
                    IN_EXPENSE_TOTAL,
                    IN_EXPENSE_PERCENTAGE
                FROM EXPENSE_TABLE
                WHERE IN_EXPENSE_CASE_ID = '" . $requestId . "'
                ORDER BY IN_EXPENSE_ROW_NUMBER ASC";
    $response = callApiUrlGuzzle($apiUrl, "POST", encodeSql($query));
    $mainIndex = 1;
    $prevIndex = '';
    $wasSub   = false;
    foreach($response as $row){
        
        if(strpos($row['IN_EXPENSE_TEAM_ROW_INDEX'],'.')){
            $mainId = explode(".",$row['IN_EXPENSE_TEAM_ROW_INDEX']);
            $mainId = $mainId[0];
            $subId  = $mainId[1];
            if($prevIndex != '' AND $prevIndex != $mainId AND $wasSub)
                $mainIndex++;
            $prevIndex = $mainId;
            $newIndex = $mainIndex . '.' . $subId;
            $wasSub   = true;
        }
        else{
            if($wasSub)
                $mainIndex++;
            $newIndex = $mainIndex;
            $wasSub   = false;
        }
        $gralPercentage = round($row['IN_EXPENSE_TOTAL'] * 100 / $row['TOTAL_AMOUNT'],2);
        $query = "UPDATE EXPENSE_TABLE SET 
                IN_EXPENSE_ROW_NUMBER = '".$mainIndex."', 
                IN_EXPENSE_TEAM_ROW_INDEX = '".$newIndex."',
                -- IN_EXPENSE_PERCENTAGE = '" . $gralPercentage . "'
                WHERE IN_EXPENSE_ROW_ID = '" . $row['IN_EXPENSE_ROW_ID'] . "'";
        $response = callApiUrlGuzzle($apiUrl, "POST", encodeSql($query));
        if(!strpos($row['IN_EXPENSE_TEAM_ROW_INDEX'],'.'))
            $mainIndex++;
    }
    return $response;

}

function getEnabledRows($apiUrl, $requestId, $task){
	$query  = "";
	$query .= "SELECT * ";
	$query .= "FROM EXPENSE_TABLE ";
    $query .= "WHERE IN_EXPENSE_CASE_ID = '" . $requestId . "'";
    $query .= "AND IN_EXPENSE_TEAM_ROUTING_ID = '" . $task . "'";
	$query .= " ORDER BY cast(substring_index(IN_EXPENSE_ROW_NUMBER,'.',1) as unsigned),
				cast(substring_index(substring_index(IN_EXPENSE_ROW_NUMBER,'.',2),'.',-1) as unsigned)";
	$response = callApiUrlGuzzle($apiUrl, "POST", encodeSql($query));
	return $response;
}


function preparateExpense( $expense, $requestId) {
    $query  = '';
    $query .= 'INSERT INTO EXPENSE_TABLE (';
    $query .= 'IN_EXPENSE_CASE_ID, ';
    $query .= 'IN_EXPENSE_ROW_ID, ';
    $query .= 'IN_EXPENSE_ROW_NUMBER, ';
    $query .= 'IN_EXPENSE_TEAM_ROW_INDEX, ';
    $query .= 'IN_EXPENSE_DESCRIPTION, ';
    $query .= 'IN_EXPENSE_ACCOUNT_ID, ';
    $query .= 'IN_EXPENSE_ACCOUNT_LABEL, ';
    $query .= 'IN_EXPENSE_CORP_PROJ_ID, ';
    $query .= 'IN_EXPENSE_CORP_PROJ_LABEL, ';
    $query .= 'IN_EXPENSE_PRETAX_AMOUNT, ';
    $query .= 'IN_EXPENSE_HST, ';
    $query .= 'IN_EXPENSE_TOTAL, ';
    $query .= 'IN_EXPENSE_PERCENTAGE, ';
    $query .= 'IN_EXPENSE_PERCENTAGE_TOTAL, ';
    $query .= 'IN_EXPENSE_NR_ID, ';
    $query .= 'IN_EXPENSE_NR_LABEL, ';
    $query .= 'IN_EXPENSE_TEAM_ROUTING_ID, ';
    $query .= 'IN_EXPENSE_TEAM_ROUTING_LABEL, ';
    $query .= 'IN_EXPENSE_PROJECT_DEAL_ID, ';
    $query .= 'IN_EXPENSE_PROJECT_DEAL_LABEL, ';
    $query .= 'IN_EXPENSE_FUND_MANAGER_ID, ';
    $query .= 'IN_EXPENSE_FUND_MANAGER_LABEL, ';
    $query .= 'IN_EXPENSE_MANDATE_ID, ';
    $query .= 'IN_EXPENSE_MANDATE_LABEL, ';
    $query .= 'IN_EXPENSE_ACTIVITY_ID, ';
    $query .= 'IN_EXPENSE_ACTIVITY_LABEL, ';
    $query .= 'IN_EXPENSE_CORP_ENTITY_ID, ';
    $query .= 'IN_EXPENSE_CORP_ENTITY_LABEL, ';
    $query .= 'IN_EXPENSE_TRANSACTION_COMMENTS, ';
    $query .= 'IN_EXPENSE_OFFICE_ID, ';
    $query .= 'IN_EXPENSE_OFFICE_LABEL, ';
    $query .= 'IN_EXPENSE_DEPARTMENT_ID, ';
    $query .= 'IN_EXPENSE_DEPARTMENT_LABEL, ';
    $query .= 'IN_EXPENSE_INVESTRAN_EXP_DESCRIPTION, ';
    $query .= 'IN_EXPENSE_INVESTRAN_HST_DESCRIPTION, ';
    $query .= 'IN_EXPENSE_COMPANY_ID, ';
    $query .= 'IN_EXPENSE_COMPANY_LABEL, ';
    $query .= 'IN_EXPENSE_GL_CODE, ';
    $query .= 'IN_EXPENSE_VALIDATION ';
    $query .= ') ';
    $query .= 'VALUES ';
    foreach($expense as $expenseRow){
        $query .= '(';
        $query .= "'" . $requestId . "', ";
        $query .= "'" . $expenseRow['IN_EXPENSE_ROW_ID'] . "', ";
        $query .= ""  . $expenseRow['IN_EXPENSE_ROW_NUMBER'] . ", ";
        $query .= ""  . $expenseRow['IN_EXPENSE_TEAM_ROW_INDEX'] . ", ";
        $query .= "'" . $expenseRow['IN_EXPENSE_DESCRIPTION'] . "', ";
        $query .= "'" . (empty($expenseRow['IN_EXPENSE_ACCOUNT']['ID']) ? null : $expenseRow['IN_EXPENSE_ACCOUNT']['ID'])  . "', ";
        $query .= "'" . (empty($expenseRow['IN_EXPENSE_ACCOUNT']['LABEL']) ? null : $expenseRow['IN_EXPENSE_ACCOUNT']['LABEL'])  . "', ";
        $query .= "'" . (empty($expenseRow['IN_EXPENSE_CORP_PROJ']['ID']) ? '00000' : $expenseRow['IN_EXPENSE_CORP_PROJ']['ID'])  . "', ";
        $query .= "'" . (empty($expenseRow['IN_EXPENSE_CORP_PROJ']['LABEL']) ? 'Unallocated' : $expenseRow['IN_EXPENSE_CORP_PROJ']['LABEL'])  . "', ";
        $query .= "'" . $expenseRow['IN_EXPENSE_PRETAX_AMOUNT'] . "', ";
        $query .= "'" . $expenseRow['IN_EXPENSE_HST'] . "', ";
        $query .= "'" . $expenseRow['IN_EXPENSE_TOTAL'] . "', ";
        $query .= "'" . $expenseRow['IN_EXPENSE_PERCENTAGE'] . "', ";
        $query .= "'" . $expenseRow['IN_EXPENSE_PERCENTAGE_TOTAL'] . "', ";
        $query .= "'" . $expenseRow['IN_EXPENSE_NR'] . "', ";
        $query .= "'" . $expenseRow['IN_EXPENSE_NR'] . "', ";
        $query .= "'" . (empty($expenseRow['IN_EXPENSE_TEAM_ROUTING']['ID']) ? null : $expenseRow['IN_EXPENSE_TEAM_ROUTING']['ID'])  . "', ";
        $query .= "'" . (empty($expenseRow['IN_EXPENSE_TEAM_ROUTING']['LABEL']) ? null : $expenseRow['IN_EXPENSE_TEAM_ROUTING']['LABEL'])  . "', ";
        $query .= "'" . (empty($expenseRow['IN_EXPENSE_PROJECT_DEAL']['ID']) ? null : $expenseRow['IN_EXPENSE_PROJECT_DEAL']['ID'])  . "', ";
        $query .= "'" . (empty($expenseRow['IN_EXPENSE_PROJECT_DEAL']['LABEL']) ? null : $expenseRow['IN_EXPENSE_PROJECT_DEAL']['LABEL'])  . "', ";
        $query .= "'" . (empty($expenseRow['IN_EXPENSE_FUND_MANAGER']['ID']) ? null : $expenseRow['IN_EXPENSE_FUND_MANAGER']['ID'])  . "', ";
        $query .= "'" . (empty($expenseRow['IN_EXPENSE_FUND_MANAGER']['LABEL']) ? null : $expenseRow['IN_EXPENSE_FUND_MANAGER']['LABEL'])  . "', ";
        $query .= "'" . (empty($expenseRow['IN_EXPENSE_MANDATE']['ID']) ? null : $expenseRow['IN_EXPENSE_MANDATE']['ID'])  . "', ";
        $query .= "'" . (empty($expenseRow['IN_EXPENSE_MANDATE']['LABEL']) ? null : $expenseRow['IN_EXPENSE_MANDATE']['LABEL'])  . "', ";
        $query .= "'" . (empty($expenseRow['IN_EXPENSE_ACTIVITY']['ID']) ? null : $expenseRow['IN_EXPENSE_ACTIVITY']['ID'])  . "', ";
        $query .= "'" . (empty($expenseRow['IN_EXPENSE_ACTIVITY']['LABEL']) ? null : $expenseRow['IN_EXPENSE_ACTIVITY']['LABEL'])  . "', ";
        $query .= "'" . (empty($expenseRow['IN_EXPENSE_CORP_ENTITY']['ID']) ? null : $expenseRow['IN_EXPENSE_CORP_ENTITY']['ID'])  . "', ";
        $query .= "'" . (empty($expenseRow['IN_EXPENSE_CORP_ENTITY']['LABEL']) ? null : $expenseRow['IN_EXPENSE_CORP_ENTITY']['LABEL'])  . "', ";
        $query .= "'" . $expenseRow['IN_EXPENSE_TRANSACTION_COMMENTS'] . "', ";
        $query .= "'" . (empty($expenseRow['IN_EXPENSE_OFFICE']['ID']) ? null : $expenseRow['IN_EXPENSE_OFFICE']['ID'])  . "', ";
        $query .= "'" . (empty($expenseRow['IN_EXPENSE_OFFICE']['LABEL']) ? null : $expenseRow['IN_EXPENSE_OFFICE']['LABEL'])  . "', ";
        $query .= "'" . (empty($expenseRow['IN_EXPENSE_DEPARTMENT']['ID']) ? null : $expenseRow['IN_EXPENSE_DEPARTMENT']['ID'])  . "', ";
        $query .= "'" . (empty($expenseRow['IN_EXPENSE_DEPARTMENT']['LABEL']) ? null : $expenseRow['IN_EXPENSE_DEPARTMENT']['LABEL'])  . "', ";
        $query .= "'" . $expenseRow['IN_EXPENSE_INVESTRAN_EXP_DESCRIPTION'] . "', ";
        $query .= "'" . $expenseRow['IN_EXPENSE_INVESTRAN_HST_DESCRIPTION'] . "', ";
        $query .= "'" . (empty($expenseRow['IN_EXPENSE_COMPANY']['ID']) ? null : $expenseRow['IN_EXPENSE_COMPANY']['ID'])  . "', ";
        $query .= "'" . (empty($expenseRow['IN_EXPENSE_COMPANY']['LABEL']) ? null : $expenseRow['IN_EXPENSE_COMPANY']['LABEL'])  . "', ";
        $query .= "'" . $expenseRow['IN_EXPENSE_GL_CODE'] . "', ";
        $query .= "'" . $expenseRow['IN_EXPENSE_VALIDATION'] . "'";
        $query .= '),';
    }
    $query = substr($query, 0, -1).';';
    return $query;
    //return $query;

    //$response = callApiUrlGuzzle($apiUrl, "POST", encodeSql($query));
    //return $response;
}

function readExpense($apiUrl, $expenseId){
    $query  = "";
    $query .= "SELECT * ";
    $query .= "FROM EXPENSE_TABLE ";
    $query .= "WHERE IN_EXPENSE_ROW_ID = '" . $expenseId . "';";
    $response = callApiUrlGuzzle($apiUrl, "POST", encodeSql($query));
    //return $query;
    return $response;
}

function updateExpense($apiUrl, $expense) {

    $query  = '';
    $query .= 'UPDATE EXPENSE_TABLE SET ';
    //$query .= 'IN_EXPENSE_CASE_ID = "' . $expense['IN_EXPENSE_CASE_ID'] . '", ';
    $query .= 'IN_EXPENSE_ROW_NUMBER = ' . $expense['IN_EXPENSE_ROW_NUMBER'] . ', ';
    $query .= 'IN_EXPENSE_TEAM_ROW_INDEX = ' . $expense['IN_EXPENSE_TEAM_ROW_INDEX'] . ', ';
    $query .= 'IN_EXPENSE_DESCRIPTION = "' . $expense['IN_EXPENSE_DESCRIPTION'] . '", ';
    //$query .= 'IN_EXPENSE_ACCOUNT_ID = ' . (empty($expense['IN_EXPENSE_ACCOUNT']['ID']) ? null : '"' . $expense['IN_EXPENSE_ACCOUNT']['ID'] . '"') . ', ';

    $query .= 'IN_EXPENSE_ACCOUNT_ID = "' . (empty($expense['IN_EXPENSE_ACCOUNT']['ID']) ? null : $expense['IN_EXPENSE_ACCOUNT']['ID']) . '", ';
    $query .= 'IN_EXPENSE_ACCOUNT_LABEL = "' . (empty($expense['IN_EXPENSE_ACCOUNT']['LABEL']) ? null : $expense['IN_EXPENSE_ACCOUNT']['LABEL']) . '", ';
    $query .= 'IN_EXPENSE_CORP_PROJ_ID = "' . (empty($expense['IN_EXPENSE_CORP_PROJ']['ID']) ? null : $expense['IN_EXPENSE_CORP_PROJ']['ID']) . '", ';
    $query .= 'IN_EXPENSE_CORP_PROJ_LABEL = "' . (empty($expense['IN_EXPENSE_CORP_PROJ']['LABEL']) ? null : $expense['IN_EXPENSE_CORP_PROJ']['LABEL']) . '", ';
    $query .= 'IN_EXPENSE_PRETAX_AMOUNT = "' . $expense['IN_EXPENSE_PRETAX_AMOUNT'] . '", ';
    $query .= 'IN_EXPENSE_HST = "' . $expense['IN_EXPENSE_HST'] . '", ';
    $query .= 'IN_EXPENSE_TOTAL = "' . $expense['IN_EXPENSE_TOTAL'] . '", ';
    $query .= 'IN_EXPENSE_PERCENTAGE = "' . $expense['IN_EXPENSE_PERCENTAGE'] . '", ';
    $query .= 'IN_EXPENSE_PERCENTAGE_TOTAL = "' . $expense['IN_EXPENSE_PERCENTAGE_TOTAL'] . '", ';
    $query .= 'IN_EXPENSE_NR_ID = "' . $expense['IN_EXPENSE_NR'] . '", ';
    $query .= 'IN_EXPENSE_NR_LABEL = "' . $expense['IN_EXPENSE_NR'] . '", ';
    $query .= 'IN_EXPENSE_TEAM_ROUTING_ID = "' . (empty($expense['IN_EXPENSE_TEAM_ROUTING']['ID']) ? null : $expense['IN_EXPENSE_TEAM_ROUTING']['ID']) . '", ';
    $query .= 'IN_EXPENSE_TEAM_ROUTING_LABEL = "' . (empty($expense['IN_EXPENSE_TEAM_ROUTING']['LABEL']) ? null : $expense['IN_EXPENSE_TEAM_ROUTING']['LABEL']) . '", ';
    $query .= 'IN_EXPENSE_PROJECT_DEAL_ID = "' . (empty($expense['IN_EXPENSE_PROJECT_DEAL']['ID']) ? null : $expense['IN_EXPENSE_PROJECT_DEAL']['ID']) . '", ';
    $query .= 'IN_EXPENSE_PROJECT_DEAL_LABEL = "' . (empty($expense['IN_EXPENSE_PROJECT_DEAL']['LABEL']) ? null : $expense['IN_EXPENSE_PROJECT_DEAL']['LABEL']) . '", ';
    $query .= 'IN_EXPENSE_FUND_MANAGER_ID = "' . (empty($expense['IN_EXPENSE_FUND_MANAGER']['ID']) ? null : $expense['IN_EXPENSE_FUND_MANAGER']['ID']) . '", ';
    $query .= 'IN_EXPENSE_FUND_MANAGER_LABEL = "' . (empty($expense['IN_EXPENSE_FUND_MANAGER']['LABEL']) ? null : $expense['IN_EXPENSE_FUND_MANAGER']['LABEL']) . '", ';
    $query .= 'IN_EXPENSE_MANDATE_ID = "' . (empty($expense['IN_EXPENSE_MANDATE']['ID']) ? null : $expense['IN_EXPENSE_MANDATE']['ID']) . '", ';
    $query .= 'IN_EXPENSE_MANDATE_LABEL = "' . (empty($expense['IN_EXPENSE_MANDATE']['LABEL']) ? null : $expense['IN_EXPENSE_MANDATE']['LABEL']) . '", ';
    $query .= 'IN_EXPENSE_ACTIVITY_ID = "' . (empty($expense['IN_EXPENSE_ACTIVITY']['ID']) ? null : $expense['IN_EXPENSE_ACTIVITY']['ID']) . '", ';
    $query .= 'IN_EXPENSE_ACTIVITY_LABEL = "' . (empty($expense['IN_EXPENSE_ACTIVITY']['LABEL']) ? null : $expense['IN_EXPENSE_ACTIVITY']['LABEL']) . '", ';
    $query .= 'IN_EXPENSE_CORP_ENTITY_ID = "' . (empty($expense['IN_EXPENSE_CORP_ENTITY']['ID']) ? null : $expense['IN_EXPENSE_CORP_ENTITY']['ID']) . '", ';
    $query .= 'IN_EXPENSE_CORP_ENTITY_LABEL = "' . (empty($expense['IN_EXPENSE_CORP_ENTITY']['LABEL']) ? null : $expense['IN_EXPENSE_CORP_ENTITY']['LABEL']) . '", ';
    $query .= 'IN_EXPENSE_TRANSACTION_COMMENTS = "' . $expense['IN_EXPENSE_TRANSACTION_COMMENTS'] . '", ';
    $query .= 'IN_EXPENSE_OFFICE_ID = "' . (empty($expense['IN_EXPENSE_OFFICE']['ID']) ? null : $expense['IN_EXPENSE_OFFICE']['ID']) . '", ';
    $query .= 'IN_EXPENSE_OFFICE_LABEL = "' . (empty($expense['IN_EXPENSE_OFFICE']['LABEL']) ? null : $expense['IN_EXPENSE_OFFICE']['LABEL']) . '", ';
    $query .= 'IN_EXPENSE_DEPARTMENT_ID = "' . (empty($expense['IN_EXPENSE_DEPARTMENT']['ID']) ? null : $expense['IN_EXPENSE_DEPARTMENT']['ID']) . '", ';
    $query .= 'IN_EXPENSE_DEPARTMENT_LABEL = "' . (empty($expense['IN_EXPENSE_DEPARTMENT']['LABEL']) ? null : $expense['IN_EXPENSE_DEPARTMENT']['LABEL']) . '", ';
    $query .= 'IN_EXPENSE_INVESTRAN_EXP_DESCRIPTION = "' . $expense['IN_EXPENSE_INVESTRAN_EXP_DESCRIPTION'] . '", ';
    $query .= 'IN_EXPENSE_INVESTRAN_HST_DESCRIPTION = "' . $expense['IN_EXPENSE_INVESTRAN_HST_DESCRIPTION'] . '", ';
    $query .= 'IN_EXPENSE_COMPANY_ID = "' . (empty($expense['IN_EXPENSE_COMPANY']['ID']) ? null : $expense['IN_EXPENSE_COMPANY']['ID']) . '", ';
    $query .= 'IN_EXPENSE_COMPANY_LABEL = "' . (empty($expense['IN_EXPENSE_COMPANY']['LABEL']) ? null : $expense['IN_EXPENSE_COMPANY']['LABEL']) . '", ';
    $query .= 'IN_EXPENSE_GL_CODE = "' . $expense['IN_EXPENSE_GL_CODE'] . '", ';
    $query .= 'IN_EXPENSE_VALIDATION = "' .  $expense['IN_EXPENSE_VALIDATION'] . '" ';
    
    $query .= 'WHERE IN_EXPENSE_ROW_ID = "' . $expense['IN_EXPENSE_ROW_ID'] . '";';

    $response = callApiUrlGuzzle($apiUrl, "POST", encodeSql($query));

    return $response;

}

function getAllExpenseByRequest ($apiUrl, $requestId) {
    $query  = "";
    $query .= "SELECT * ";
    $query .= "FROM EXPENSE_TABLE ";
    $query .= "WHERE IN_EXPENSE_CASE_ID = '" . $requestId . "' ";
    $query .= "ORDER BY IN_EXPENSE_ROW_NUMBER ASC ; ";
    $response = callApiUrlGuzzle($apiUrl, "POST", encodeSql($query));
    return $response;
}

function checkRecordsByRequestId($apiUrl, $requestId){
    $query  = "";
    $query .= "SELECT COUNT(*) AS TOTAL_ROWS ";
    $query .= "FROM EXPENSE_TABLE ";
    $query .= "WHERE IN_EXPENSE_CASE_ID = '" . $requestId . "';";
    $response = callApiUrlGuzzle($apiUrl, "POST", encodeSql($query));
    return $response;
}