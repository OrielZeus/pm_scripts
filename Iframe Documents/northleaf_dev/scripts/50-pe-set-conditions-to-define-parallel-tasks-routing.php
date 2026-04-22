<?php 
/*******************************************************
 * PE - Set conditions to define parallel tasks routing
 *
 * by Cinthia Romero
 * modified by Telmo Chiri
 ******************************************************/
//Initialize variables
$dataReturn = array();

//Set conditions to route to DT.02
$mandatesGrid = empty($data["PE_MANDATES"]) ? array() : $data["PE_MANDATES"];
$cfofDocumentRequired = "NO";
$nvcfDocumentRequired = "NO";
foreach ($mandatesGrid as $mandate) {
    if (strpos($mandate["PE_MANDATE_NAME"], 'CFOF') !== false) {
        $cfofDocumentRequired = "YES";
    }
    if (strpos($mandate["PE_MANDATE_NAME"], 'NVCF') !== false) {
        $nvcfDocumentRequired = "YES";
    }
}
$dataReturn["PE_CFOF_DOCUMENT_REQUIRED"] = $cfofDocumentRequired;
$dataReturn["PE_NVCF_DOCUMENT_REQUIRED"] = $nvcfDocumentRequired;

$dataReturn["PE_IC_NECESSARY"] = $data["PE_IC_NECESSARY"] ?? "YES";

return $dataReturn;