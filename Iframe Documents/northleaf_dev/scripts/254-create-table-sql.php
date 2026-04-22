<?php 
/**********************************
 * IN - Create Table EXPENSE_TABLE
 *
 * by Manuel Monroy
 *********************************/
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Request;

/**
 * Call Api Url Guzzle
 *
 * @param string $url
 * @param string $method
 * @param array sendData
 * @return array $executionResponse
 *
 * by Manuel Monroy
 */ 
function callApiUrlGuzzle($url, $method, $sendData)
{
    global $apiToken, $apiHost;
    $headers = [
        "Content-Type" => "application/json",
        "Accept" => "application/json",
        'Authorization' => 'Bearer ' . $apiToken
    ];
    $client = new Client([
        'base_uri' => $apiHost,
        'verify' => false,
    ]);
    $request = new Request($method, $url, $headers, json_encode($sendData));
    try {
        $res = $client->sendAsync($request)->wait();
        $res = $res->getBody()->getContents();
    } catch (Exception $exception) {
        $responseBody = $exception->getResponse()->getBody(true);
        $res = $responseBody;
    }
    $executionResponse = json_decode($res, true);
    return $executionResponse;
}

/**
 * Encode SQL
 *
 * @param string $query
 * @return array $encodedQuery
 *
 * by Manuel Monroy
 */
function encodeSql($query)
{
    $encodedQuery = [
        "SQL" => base64_encode($query)
    ];
    return $encodedQuery;
}

//Set Global Variables
$apiHost = getenv('API_HOST');
$apiToken = getenv('API_TOKEN');
$apiSql = getenv('API_SQL');
$apiUrl = $apiHost . $apiSql;

// Get all records
$query  = "";
$query .= "SELECT * FROM EXPENSE_TABLE";
$response = callApiUrlGuzzle($apiUrl, "POST", encodeSql($query));
return $response;


// Add IN_EXPENSE_TEAM_ROW_INDEX row
$query  = '';
$query .= 'ALTER TABLE EXPENSE_TABLE ';
$query .= 'ADD COLUMN IN_EXPENSE_TEAM_ROW_INDEX INTEGER(10);';
$response = callApiUrlGuzzle($apiUrl, "POST", encodeSql($query));
return $response;

// Agregar la nueva columna
$query .= 'ALTER TABLE EXPENSE_TABLE ADD COLUMN IN_EXPENSE_VALIDATION MEDIUMTEXT;';

// Ejecutar la consulta SQL para crear la tabla y agregar la columna
$response = callApiUrlGuzzle($apiUrl, "POST", encodeSql($query));

return $response;


$query = '';
$query .= 'ALTER TABLE EXPENSE_TABLE ';
$query .= 'ADD COLUMN IN_EXPENSE_ROW_NUMBER INTEGER(10);';

// Ejecutar la consulta SQL para modificar la tabla
$response = callApiUrlGuzzle($apiUrl, "POST", encodeSql($query));

return $response;
/*
$query = 'SELECT * FROM EXPENSE_TABLE';
$response = callApiUrlGuzzle($apiUrl, "POST", encodeSql($query));

return $response;


$data = [
    'IN_EXPENSE_UID' => 'EXP12345',
    'IN_EXPENSE_CASE_ID' => 'CASE9876',
    'IN_EXPENSE_ROW_ID' => 'ROW555',
    'IN_EXPENSE_DESCRIPTION' => 'Compra de equipo',
    'IN_EXPENSE_ACCOUNT_ID' => 'ACCT123',
    'IN_EXPENSE_ACCOUNT_LABEL' => 'Cuenta de Oficina',
    'IN_EXPENSE_CORP_PROJ_ID' => 'PROJ678',
    'IN_EXPENSE_CORP_PROJ_LABEL' => 'Proyecto A',
    'IN_EXPENSE_PRETAX_AMOUNT' => 1500.00,
    'IN_EXPENSE_HST' => 150.00,
    'IN_EXPENSE_TOTAL' => 1650.00,
    'IN_EXPENSE_PERCENTAGE' => 10.00,
    'IN_EXPENSE_PERCENTAGE_TOTAL' => 165.00,
    'IN_EXPENSE_NR_ID' => 'NR123',
    'IN_EXPENSE_NR_LABEL' => 'Registro de Gasto',
    'IN_EXPENSE_TEAM_ROUTING_ID' => 'TEAM123',
    'IN_EXPENSE_TEAM_ROUTING_LABEL' => 'Equipo 1',
    'IN_EXPENSE_PROJECT_DEAL_ID' => 'PD123',
    'IN_EXPENSE_PROJECT_DEAL_LABEL' => 'Acuerdo A',
    'IN_EXPENSE_FUND_MANAGER_ID' => 'FM123',
    'IN_EXPENSE_FUND_MANAGER_LABEL' => 'Gestor A',
    'IN_EXPENSE_MANDATE_ID' => 'MD123',
    'IN_EXPENSE_MANDATE_LABEL' => 'Mandato A',
    'IN_EXPENSE_ACTIVITY_ID' => 'ACT123',
    'IN_EXPENSE_ACTIVITY_LABEL' => 'Reunión',
    'IN_EXPENSE_CORP_ENTITY_ID' => 'CE123',
    'IN_EXPENSE_CORP_ENTITY_LABEL' => 'Entidad A',
    'IN_EXPENSE_TRANSACTION_COMMENTS' => 'Pago por equipo de oficina',
    'IN_EXPENSE_OFFICE_ID' => 'OFF123',
    'IN_EXPENSE_OFFICE_LABEL' => 'Oficina Principal',
    'IN_EXPENSE_DEPARTMENT_ID' => 'DEP123',
    'IN_EXPENSE_DEPARTMENT_LABEL' => 'Departamento de TI',
    'IN_EXPENSE_INVESTRAN_EXP_DESCRIPTION' => 'Detalles de inversión',
    'IN_EXPENSE_INVESTRAN_HST_DESCRIPTION' => 'Detalles del HST',
    'IN_EXPENSE_COMPANY_ID' => 'COMP123',
    'IN_EXPENSE_COMPANY_LABEL' => 'Compañía X',
    'IN_EXPENSE_GL_CODE' => 'GL123'
];


$query = 'INSERT INTO EXPENSE_TABLE (';
$query .= 'IN_EXPENSE_UID, ';
$query .= 'IN_EXPENSE_CASE_ID, ';
$query .= 'IN_EXPENSE_ROW_ID, ';
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
$query .= 'IN_EXPENSE_GL_CODE) ';
$query .= 'VALUES (';
$query .= "'" . $data['IN_EXPENSE_UID'] . "', ";  // Comillas alrededor de las cadenas de texto
$query .= "'" . $data['IN_EXPENSE_CASE_ID'] . "', ";
$query .= "'" . $data['IN_EXPENSE_ROW_ID'] . "', ";
$query .= "'" . $data['IN_EXPENSE_DESCRIPTION'] . "', ";
$query .= "'" . $data['IN_EXPENSE_ACCOUNT_ID'] . "', ";
$query .= "'" . $data['IN_EXPENSE_ACCOUNT_LABEL'] . "', ";
$query .= "'" . $data['IN_EXPENSE_CORP_PROJ_ID'] . "', ";
$query .= "'" . $data['IN_EXPENSE_CORP_PROJ_LABEL'] . "', ";
$query .= $data['IN_EXPENSE_PRETAX_AMOUNT'] . ", ";  // No se necesitan comillas para valores numéricos
$query .= $data['IN_EXPENSE_HST'] . ", ";
$query .= $data['IN_EXPENSE_TOTAL'] . ", ";
$query .= $data['IN_EXPENSE_PERCENTAGE'] . ", ";
$query .= $data['IN_EXPENSE_PERCENTAGE_TOTAL'] . ", ";
$query .= "'" . $data['IN_EXPENSE_NR_ID'] . "', ";
$query .= "'" . $data['IN_EXPENSE_NR_LABEL'] . "', ";
$query .= "'" . $data['IN_EXPENSE_TEAM_ROUTING_ID'] . "', ";
$query .= "'" . $data['IN_EXPENSE_TEAM_ROUTING_LABEL'] . "', ";
$query .= "'" . $data['IN_EXPENSE_PROJECT_DEAL_ID'] . "', ";
$query .= "'" . $data['IN_EXPENSE_PROJECT_DEAL_LABEL'] . "', ";
$query .= "'" . $data['IN_EXPENSE_FUND_MANAGER_ID'] . "', ";
$query .= "'" . $data['IN_EXPENSE_FUND_MANAGER_LABEL'] . "', ";
$query .= "'" . $data['IN_EXPENSE_MANDATE_ID'] . "', ";
$query .= "'" . $data['IN_EXPENSE_MANDATE_LABEL'] . "', ";
$query .= "'" . $data['IN_EXPENSE_ACTIVITY_ID'] . "', ";
$query .= "'" . $data['IN_EXPENSE_ACTIVITY_LABEL'] . "', ";
$query .= "'" . $data['IN_EXPENSE_CORP_ENTITY_ID'] . "', ";
$query .= "'" . $data['IN_EXPENSE_CORP_ENTITY_LABEL'] . "', ";
$query .= "'" . $data['IN_EXPENSE_TRANSACTION_COMMENTS'] . "', ";
$query .= "'" . $data['IN_EXPENSE_OFFICE_ID'] . "', ";
$query .= "'" . $data['IN_EXPENSE_OFFICE_LABEL'] . "', ";
$query .= "'" . $data['IN_EXPENSE_DEPARTMENT_ID'] . "', ";
$query .= "'" . $data['IN_EXPENSE_DEPARTMENT_LABEL'] . "', ";
$query .= "'" . $data['IN_EXPENSE_INVESTRAN_EXP_DESCRIPTION'] . "', ";
$query .= "'" . $data['IN_EXPENSE_INVESTRAN_HST_DESCRIPTION'] . "', ";
$query .= "'" . $data['IN_EXPENSE_COMPANY_ID'] . "', ";
$query .= "'" . $data['IN_EXPENSE_COMPANY_LABEL'] . "', ";
$query .= "'" . $data['IN_EXPENSE_GL_CODE'] . "'";  // Comillas alrededor de las cadenas de texto
$query .= ');';

// Llamar a la API
$response = callApiUrlGuzzle($apiUrl, "POST", encodeSql($query));

return $response;
*/
/*
$query = '';
$query .= 'CREATE TABLE IF NOT EXISTS EXPENSE_TABLE (';
$query .= 'IN_EXPENSE_CASE_ID VARCHAR(50), ';
$query .= 'IN_EXPENSE_ROW_ID VARCHAR(50) NOT NULL, ';
$query .= 'IN_EXPENSE_DESCRIPTION MEDIUMTEXT, ';
$query .= 'IN_EXPENSE_ACCOUNT_ID VARCHAR(255), ';
$query .= 'IN_EXPENSE_ACCOUNT_LABEL VARCHAR(255), ';
$query .= 'IN_EXPENSE_CORP_PROJ_ID VARCHAR(255), ';
$query .= 'IN_EXPENSE_CORP_PROJ_LABEL VARCHAR(255), ';
$query .= 'IN_EXPENSE_PRETAX_AMOUNT VARCHAR(255), ';
$query .= 'IN_EXPENSE_HST VARCHAR(255), ';
$query .= 'IN_EXPENSE_TOTAL VARCHAR(255), ';
$query .= 'IN_EXPENSE_PERCENTAGE VARCHAR(255), ';
$query .= 'IN_EXPENSE_PERCENTAGE_TOTAL VARCHAR(255), ';
$query .= 'IN_EXPENSE_NR_ID VARCHAR(255), ';
$query .= 'IN_EXPENSE_NR_LABEL VARCHAR(255), ';
$query .= 'IN_EXPENSE_TEAM_ROUTING_ID VARCHAR(255), ';
$query .= 'IN_EXPENSE_TEAM_ROUTING_LABEL VARCHAR(255), ';
$query .= 'IN_EXPENSE_PROJECT_DEAL_ID VARCHAR(255), ';
$query .= 'IN_EXPENSE_PROJECT_DEAL_LABEL VARCHAR(255), ';
$query .= 'IN_EXPENSE_FUND_MANAGER_ID VARCHAR(255), ';
$query .= 'IN_EXPENSE_FUND_MANAGER_LABEL VARCHAR(255), ';
$query .= 'IN_EXPENSE_MANDATE_ID VARCHAR(255), ';
$query .= 'IN_EXPENSE_MANDATE_LABEL VARCHAR(255), ';
$query .= 'IN_EXPENSE_ACTIVITY_ID VARCHAR(255), ';
$query .= 'IN_EXPENSE_ACTIVITY_LABEL VARCHAR(255), ';
$query .= 'IN_EXPENSE_CORP_ENTITY_ID VARCHAR(255), ';
$query .= 'IN_EXPENSE_CORP_ENTITY_LABEL VARCHAR(255), ';
$query .= 'IN_EXPENSE_TRANSACTION_COMMENTS MEDIUMTEXT, ';
$query .= 'IN_EXPENSE_OFFICE_ID VARCHAR(255), ';
$query .= 'IN_EXPENSE_OFFICE_LABEL VARCHAR(255), ';
$query .= 'IN_EXPENSE_DEPARTMENT_ID VARCHAR(255), ';
$query .= 'IN_EXPENSE_DEPARTMENT_LABEL VARCHAR(255), ';
$query .= 'IN_EXPENSE_INVESTRAN_EXP_DESCRIPTION MEDIUMTEXT, ';
$query .= 'IN_EXPENSE_INVESTRAN_HST_DESCRIPTION MEDIUMTEXT, ';
$query .= 'IN_EXPENSE_COMPANY_ID VARCHAR(255), ';
$query .= 'IN_EXPENSE_COMPANY_LABEL VARCHAR(255), ';
$query .= 'IN_EXPENSE_GL_CODE VARCHAR(255), ';
$query .= 'PRIMARY KEY (IN_EXPENSE_ROW_ID)'; // Definir la clave primaria
$query .= ');';
*/
// Agregar índices adicionales para mejorar el rendimiento
//$query = 'CREATE INDEX idx_case_id ON EXPENSE_TABLE(IN_EXPENSE_CASE_ID);';
//$query = 'CREATE INDEX idx_row_id ON EXPENSE_TABLE(IN_EXPENSE_ROW_ID);';

// Usar la consulta generada en una llamada a la API o ejecutarla en tu base de datos

$data = [
    'IN_EXPENSE_CASE_ID' => 'CASE29876',
    'IN_EXPENSE_ROW_ID' => 'ROW52355',
    'IN_EXPENSE_DESCRIPTION' => 'Compra de equipo',
    'IN_EXPENSE_ACCOUNT_ID' => 'ACCT123',
    'IN_EXPENSE_ACCOUNT_LABEL' => 'Cuenta de Oficina',
    'IN_EXPENSE_CORP_PROJ_ID' => 'PROJ678',
    'IN_EXPENSE_CORP_PROJ_LABEL' => 'Proyecto A',
    'IN_EXPENSE_PRETAX_AMOUNT' => 1500.00,
    'IN_EXPENSE_HST' => 150.00,
    'IN_EXPENSE_TOTAL' => 1650.00,
    'IN_EXPENSE_PERCENTAGE' => 10.00,
    'IN_EXPENSE_PERCENTAGE_TOTAL' => 165.00,
    'IN_EXPENSE_NR_ID' => 'NR123',
    'IN_EXPENSE_NR_LABEL' => 'Registro de Gasto',
    'IN_EXPENSE_TEAM_ROUTING_ID' => 'TEAM123',
    'IN_EXPENSE_TEAM_ROUTING_LABEL' => 'Equipo 1',
    'IN_EXPENSE_PROJECT_DEAL_ID' => 'PD123',
    'IN_EXPENSE_PROJECT_DEAL_LABEL' => 'Acuerdo A',
    'IN_EXPENSE_FUND_MANAGER_ID' => 'FM123',
    'IN_EXPENSE_FUND_MANAGER_LABEL' => 'Gestor A',
    'IN_EXPENSE_MANDATE_ID' => 'MD123',
    'IN_EXPENSE_MANDATE_LABEL' => 'Mandato A',
    'IN_EXPENSE_ACTIVITY_ID' => 'ACT123',
    'IN_EXPENSE_ACTIVITY_LABEL' => 'Reunión',
    'IN_EXPENSE_CORP_ENTITY_ID' => 'CE123',
    'IN_EXPENSE_CORP_ENTITY_LABEL' => 'Entidad A',
    'IN_EXPENSE_TRANSACTION_COMMENTS' => 'Pago por equipo de oficina',
    'IN_EXPENSE_OFFICE_ID' => 'OFF123',
    'IN_EXPENSE_OFFICE_LABEL' => 'Oficina Principal',
    'IN_EXPENSE_DEPARTMENT_ID' => 'DEP123',
    'IN_EXPENSE_DEPARTMENT_LABEL' => 'Departamento de TI',
    'IN_EXPENSE_INVESTRAN_EXP_DESCRIPTION' => 'Detalles de inversión',
    'IN_EXPENSE_INVESTRAN_HST_DESCRIPTION' => 'Detalles del HST',
    'IN_EXPENSE_COMPANY_ID' => 'COMP123',
    'IN_EXPENSE_COMPANY_LABEL' => 'Compañía X',
    'IN_EXPENSE_GL_CODE' => 'GL123'
];

// Generación de la consulta SQL de INSERT
$query = '';
$query .= 'INSERT INTO EXPENSE_TABLE (';
$query .= 'IN_EXPENSE_CASE_ID, ';
$query .= 'IN_EXPENSE_ROW_ID, ';
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
$query .= 'IN_EXPENSE_GL_CODE) ';
$query .= 'VALUES (';
$query .= "'" . $data['IN_EXPENSE_CASE_ID'] . "', ";
$query .= "'" . $data['IN_EXPENSE_ROW_ID'] . "', ";
$query .= "'" . $data['IN_EXPENSE_DESCRIPTION'] . "', ";
$query .= "'" . $data['IN_EXPENSE_ACCOUNT_ID'] . "', ";
$query .= "'" . $data['IN_EXPENSE_ACCOUNT_LABEL'] . "', ";
$query .= "'" . $data['IN_EXPENSE_CORP_PROJ_ID'] . "', ";
$query .= "'" . $data['IN_EXPENSE_CORP_PROJ_LABEL'] . "', ";
$query .= "'" . $data['IN_EXPENSE_PRETAX_AMOUNT'] . "', ";
$query .= "'" . $data['IN_EXPENSE_HST'] . "', ";
$query .= "'" . $data['IN_EXPENSE_TOTAL'] . "', ";
$query .= "'" . $data['IN_EXPENSE_PERCENTAGE'] . "', ";
$query .= "'" . $data['IN_EXPENSE_PERCENTAGE_TOTAL'] . "', ";
$query .= "'" . $data['IN_EXPENSE_NR_ID'] . "', ";
$query .= "'" . $data['IN_EXPENSE_NR_LABEL'] . "', ";
$query .= "'" . $data['IN_EXPENSE_TEAM_ROUTING_ID'] . "', ";
$query .= "'" . $data['IN_EXPENSE_TEAM_ROUTING_LABEL'] . "', ";
$query .= "'" . $data['IN_EXPENSE_PROJECT_DEAL_ID'] . "', ";
$query .= "'" . $data['IN_EXPENSE_PROJECT_DEAL_LABEL'] . "', ";
$query .= "'" . $data['IN_EXPENSE_FUND_MANAGER_ID'] . "', ";
$query .= "'" . $data['IN_EXPENSE_FUND_MANAGER_LABEL'] . "', ";
$query .= "'" . $data['IN_EXPENSE_MANDATE_ID'] . "', ";
$query .= "'" . $data['IN_EXPENSE_MANDATE_LABEL'] . "', ";
$query .= "'" . $data['IN_EXPENSE_ACTIVITY_ID'] . "', ";
$query .= "'" . $data['IN_EXPENSE_ACTIVITY_LABEL'] . "', ";
$query .= "'" . $data['IN_EXPENSE_CORP_ENTITY_ID'] . "', ";
$query .= "'" . $data['IN_EXPENSE_CORP_ENTITY_LABEL'] . "', ";
$query .= "'" . $data['IN_EXPENSE_TRANSACTION_COMMENTS'] . "', ";
$query .= "'" . $data['IN_EXPENSE_OFFICE_ID'] . "', ";
$query .= "'" . $data['IN_EXPENSE_OFFICE_LABEL'] . "', ";
$query .= "'" . $data['IN_EXPENSE_DEPARTMENT_ID'] . "', ";
$query .= "'" . $data['IN_EXPENSE_DEPARTMENT_LABEL'] . "', ";
$query .= "'" . $data['IN_EXPENSE_INVESTRAN_EXP_DESCRIPTION'] . "', ";
$query .= "'" . $data['IN_EXPENSE_INVESTRAN_HST_DESCRIPTION'] . "', ";
$query .= "'" . $data['IN_EXPENSE_COMPANY_ID'] . "', ";
$query .= "'" . $data['IN_EXPENSE_COMPANY_LABEL'] . "', ";
$query .= "'" . $data['IN_EXPENSE_GL_CODE'] . "'";
$query .= ');';

$query .= ');';

// Ejecutar la consulta (suponiendo que la función `callApiUrlGuzzle()` es la que ejecuta las consultas)
$response = callApiUrlGuzzle($apiUrl, "POST", encodeSql($query));