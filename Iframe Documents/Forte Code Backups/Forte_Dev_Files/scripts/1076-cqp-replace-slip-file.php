<?php 

/*****************************************
* Calculate CQP_PREMIUM_PER_INSTALLMENT for markets
*
* by Diego Tapia
* Modified by Natalia Mendez
*****************************************/

// Calculate premium pe intallent


if ($data["CQP_MARKETS"] != null && $data["CQP_MARKETS"] != "" && $data["CQP_MARKETS"] != "null") {
    foreach ($data["CQP_MARKETS"] as &$market) {
        if (!empty($market["CQP_NWP_MINDEP_REINSURER_SHARE"]) && !empty($data["CQP_N_INSTALLMENTS"])) {
            $market["CQP_PREMIUM_PER_INSTALLMENT"] = $market["CQP_NWP_MINDEP_REINSURER_SHARE"] / $data["CQP_N_INSTALLMENTS"];
            $market["CQP_PREMIUM_PER_INSTALLMENT_USD_VALUE"] = $data["CQP_CURRENCY"] == "USD" ? $market["CQP_PREMIUM_PER_INSTALLMENT"] : ($market["CQP_PREMIUM_PER_INSTALLMENT"] * $data["CQP_FX_RATE"]) ;
        }
    }
}

return [
    "CQP_MARKETS" => $data["CQP_MARKETS"], 
    "CQP_HIDE_SAVE" => true,
    
    "resErrorHandling"   => "", // Clear error variable to avoid previous error to be shown afterwards
    "FORTE_ERROR"       => ['data' => ["FORTE_ERROR_LOG" => ""]],
    "FORTE_ERROR_MESSAGE" => ""

];