<?php 

/*****************************************
* Analize file/s in gemini
*
* by Diego Tapia
* Modified by Natalia Mendez
*****************************************/

use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Utils;
use GuzzleHttp\Exception\RequestException;
ini_set('memory_limit', '256M'); 

// Set initial data and Gemini Object
$pmapi = $api->requestFiles();
$cw = new CloudAPIClient();
$grid = "";
$requestId = $data["_request"]["id"]; 
$fileId_1 = $data['CQP_SLIP']; 
$fileId_2 = $data['CQP_NEW_FILE_UPLOAD']; 
$responseScript = [];

// Prepare the Prompt for Gemini clause validation
$promptValidate = 'Analize the document and validate the text of the clauses/sub clauses and the numered validation descript (if the cluse/sub clause) exist using the following rules:
    - INSTITUTE CARGO CLAUSE:
        1) For clause "Institute Cargo Clauses (A)" In respect of goods moving by sea, road or rail  not wider than 01.01.09 CL 382.
        2) For clause "Institute Strikes Clauses (Cargo)" In respect of goods moving by sea, road or rail not wider than 01.01.09 CL 386.
        3) For clause "Institute War Clauses (Cargo)" In respect of goods moving by sea, road or rail not wider than 01.01.09 CL 385.
        4) For clause "Institute Cargo Clauses (Air)" In respect of goods moving by air not wider than 01.01.09 CL 387.
        5) For clause "Institute Strikes Clauses (Air)" In respect of goods moving by air not wider than 01.01.09 CL 389.
        6) For clause "Institute War Clauses (Air) (Excluding sendings by Post)" In respect of goods moving by air not wider than 01.01.09 CL 388.
        7) For clause "Institute War Clauses (sendings) by Post" In respect of goods moving by air not wider than 01.03.09 CL 390.
        8) For clause "Institute frozen food Clause" In respect of temperature Controlled goods -24 hours 01.03.17 CL 
        9) For clause "Institute Strikes Clauses (Frozen food)" In respect of temperature Controlled goods 01.03.17 CL 424. 
        10) For clause "Institute Cargo Clauses (C)" In respect of used Machinery and Equipment 01.01.09 CL 384. 
        11) For clause "Institute Replacement Clause" In respect of used Machinery and Equipment 01.12.08 CL 372.
        12) For clause "Institute Container Clause" In respect of container coverage 01.01.87 CL 338.
        13) For clause "Institute Container Clauses" In respect of container coverage Time Total loss, general average, salvage, Salvage charges, 
        14) For clause "Institute Radioactive Contamination, Chemical, Biological and Electromagnetic Weapons Clause" For all interest 10.11.03CL 370.
        15) For clause "Institute Classification Clause" For all interest 01.01.01 CL 354.
        16) For clause "Termination of Transit Clause (Terrorism) 2009 JC2009/056 dated" For all interest 01.01.09.
        17) For clause "Munich Re Sanction and Limitation Clause (including Switzerland) (provided that this does not violate current EU and/or specific national law applicable to the undersigned reinsurer)" For all interest 
        18) For clause "Munich Re Claims Cooperation Clause MR1." For all interest 
        19) For clause "LMA 5402 11/11/2019 Marine Cyber Exclusion" For all interest 
        20) For clause "Communicable Disease  Exclusion JC 2020 11(Cargo)" For all interest 
        21) For clause "Premium Collection Clause." For all interest 
        22) For clause "GDPR Requirement." For all interest 
        23) For clause "General conditions of Münchener Rück for Facultative Businesses V12 F1 to apply." In respect of facultative business 
        24) For clause "Subject to all terms, clauses and conditions as original policy but no wider than the terms contained herein." In respect of facultative business
        25) For clause "Excluding ex-gratia settlements and without prejudice payments" In respect of facultative business
        26) For clause "Cover to commence and end as original" In respect of facultative business
    - other exclusions and Warranties in respect of facultative business (if apply):
        1) Terrorism is excluded in warehouses that are different from transit. (STP)
        2) Excluding Rusting, Oxidation and Discoloration caused by unpacked units.(CARGO AND STP)
        3) Excluding twisting, bending and distortion of unpacked products (CARGO AND STP) 
        4) Underwriters shall not be liable for any loss that constitutes a mysterious disappearance and losses arising from an act of infidelity of the assured and/or their employees.   (STP)
        5) Excluding loss market. (Cargo + STP)
        6) Excluding misappropriation. (STP) 
        7) Excluding spontaneous combustion. 
        8) Excluding damage from authorities.
        9) Excluding confiscation at customs.
        10) Excluding shipments to and from Russia, Ukraine and Belarus
        11) Excluding war coverage in Ukraine, Russia or Belarus or any other country categorized with “Very High”, “Severe” or “Extreme” as per JCC Watchlist  (https://watchlists.ihsmarkit.com/watchlists-viewer) 
        12) CLÁUSULA DE RENUNCIA A LA SUBROGACIÓN:Sin perjuicio de cualquier disposición en contrario contenida en el presente documento, se entiende y acuerda expresamente que el ASEGURADOR renunciará al derecho de subrogación contra el TOMADOR DEL SEGURO, excepto como resultado de una conducta dolosa o negligencia grave. Esta renuncia no constituye en modo alguno un derecho del TOMADOR DEL SEGURO a emitir renuncias de recurso a cualquier transportista, subcontratista o cualquier otro tercero.
        13) WAIVER OF SUBROGATION CLAUSE: Notwithstanding anything to the contrary contained herein, it is expressly understood and agreed that the INSURER shall waive the right of subrogation against the INSURED except as a result of willful misconduct or gross negligence. This waiver shall in no way constitute a right of the INSURED to issue waivers of recourse to any carrier, subcontractor or any other third party.
    - Warranties  In respect  Bulk cargo: For vessels older than 10 years, it is recorded that the risk of wetting is covered subject to: Prior to the loading of the vessel a hydraulic test and / or ultrasonic test has to be carried out by companies or experts with recognized responsibility in the matter. stating that the degree of tightness of the ships wells is acceptable for the adventure. 
        1) Cleaning Certificate:  tanks, hoses and other cargo compartments
        2) Sampling Certificate; representative samples of both land and ship tanks should be taken, as well as the distribution of the samples and samples to be duly identified, stamped, sealed, marked and numbered, the names of all the participants in the sampling  process as well as the name of the laboratory that will carry out the analyzes. A complete set of counter samples must be delivered to the Captain of the vessel and consigned to the insured.
        3) Shipment weight certificate (draught surveys)
        4) Quality Certificate; where the quality specifications requested are duly verified and recorded in the certificate, including the test method used for the analyzes that determine the quality.
        5) Classified building with current P & I club
        6) Watertightness certificate of the warehouses.
    - ADDITIONAL CLAUSES AS USED MOSTLY IN STOCK THROUGHPUT: Subject to any provisions in the schedule, the following clauses supplement and qualify the applicable Institute clauses. Unless specifically stated to the contrary, in the event of any inconsistency or conflict between the clauses contained in this document and the Institute clauses, the clauses in this document shall take precedence.
        1) Cancellation Clause
        2) Translation of Documents
        3) Claims Control Clause.
        4) Accumulation extension 
        5) Aircraft
        6) Apportionment of recoveries
        7) Attachment and termination Clause
        8) Automatic acquisition Clause
        9) Brands Clause
        10) Cargo ISM Endorsement (JC 98/019 1 May 1998)
        11) Cargo ISM Expenses.
        12) Civil Authority 
        13) “Claused” Bills of Lading
        14) Container
        15) Control of Damage Goods
        16) Customs
        17) Deliberate Damage – Pollution hazard clause
        18) Demurrage Charges
        19) Errors and Omissions 
        20) Event
        21) Extra Expense Clause
        22) Forwarding Etc. Expenses
        23) Full value Reporting
        24) Fumigation 
        25) General average
        26) Goods Purchased by the insured on C.I.F. Terms (Buyers’ Interest)
        27) Goods Purchased by the insured on F.O.B., C.F.R. or similar Terms
        28) Inchmaree Clause 
        29) Increased Value on arrival Clause (Including Duty / Surcharges)
        30) Interruption of transit of damaged goods.
        31) Labels Clause
        32) Local Insurance 
        33) Letter of credit 
        34) Location definition
        35) Loss adjustment and Claim preparation expenses
        36) Misuse of bills of lading  
        37) Mysterious Disappearance exclusion 
        38) Negligence Clause
        39) Payment on account
        40) Precedence of conditions 
        41) Process Clause
        42) Removal of Debris
        43) Replacement by Air 
        44) Salesperson’s samples 
        45) Sanction limitation and exclusion Clause
        46) Second hand replacement clause 
        47) Sellers’ interest / unpaid vendors cover
        48) Shipments
        49) Shore Clause 
        50) Subrogation waiver 
        51) Sue and Labour 
        52) Survey 
        53) Testing, sorting and segregation
        54) Profit Commission.
    Return the information in a JSON structure as defined below:
    {
        "page_number": integer,
        "clause": string,
        "error": string 
    }
    ## IMPORTANT
    - The current document is considered as a ' . $data["CQP_TYPE"] . '
    - Do not infer, assume, or interpret.
    - No synonyms.
    - Repeat the original contract text verbatim whenever possible.
    - Do not introduce your own language.
    - ascending Sort the response by number of page in the page_number parameter
    - Always return valid JSON format in your response.
    - Do not add any additional explanation to the response.
    - Do not apply markdown styling. Return JSON only.
    - in error response section explain why that clause is not matching the rules
    - Do not add in the list any clause that match the caluse condition
    - Do not validate the not existense of the clause in the document that are not part of the "INSTITUTE CARGO CLAUSE" list
    - In the clause index of the response, only add the title of the section, or the title of the rule if the firts one is not available
    - Do not consider small diferences between uppercase and lowercase, spacess or date format as errors
    - add in the error index value the section that the issue  is located, this title is the text on the left of the paragraph, this title can be present in a previous page, but its considered the same title until another one apears in the document
    - if in the document is not present any clausule in the validation list, mark in the response as "clause" empty and in error add that decument is not clasified as a slip in the array of responses
    - Keep the format of this example in the responses: page_number-> 6, clause -> LMA5403 - Marine Cyber Exclusion 11.11.19.,error-> The clause text "LMA5403 - Marine Cyber Exclusion" does not exactly match the expected "LMA 5402 Marine Cyber Exclusion" and the validation descript "11.11.19." does not match "11/11/2019" in section REINSURANCE CONDITIONS."
    ## Steps generate the response
    1. Run analysis
    2. Validate JSON + fields
    3. OK → save
    4. FAIL → retry once with prompt reinforcement';

// Prepare the Prompt for Gemini Document comparition
$promptCompare = 'ROLE:
You are a deterministic document comparison engine.
You do NOT interpret, summarize, or infer meaning.
You ONLY compare textual content between two documents.

TASK:
Compare Document A (original) and Document B (revised).

Identify ONLY factual textual differences, excluding headers and footers.

DEFINITIONS:
- ADDED: Text or sections that appear in Document B but do not exist in Document A.
- REMOVED: Text or sections that appear in Document A but do not exist in Document B.
- MODIFIED: Text or sections that exist in both documents but differ in content.
  - For modified text, show the exact original text and the revised text.
- Header and footer content must be ignored entirely and never reported.

SCOPE:
- Compare content by logical sections or paragraphs when possible.
- Do not split changes into individual words unless the entire line or section is modified.
- Treat differences in capitalization, spacing, or line breaks as modifications ONLY if the meaning or wording changes.

OUTPUT POLICY:
- Report ONLY detected differences.
- If no comparable sections can be matched between the two documents,
  return a single response indicating that the files are impossible to match.

---

OUTPUT FORMAT (MANDATORY):

Return ONLY a valid JSON array.
Each element must strictly follow this structure:

{
  "page_number": integer,
  "subtitle": string,
  "original_text": string,
  "revised_text": string
}

FIELD RULES:
- page_number:
  - Page in Document B where the change appears.
  - Use 0 if pages cannot be aligned.
- subtitle:
  - Section title where the change occurs.
  - If no section title exists, use an empty string.
- original_text:
  - Text from Document A.
  - Use empty string "" for ADDED content.
- revised_text:
  - Text from Document B.
  - Use empty string "" for REMOVED content.

SPECIAL FAILURE CONDITION:
If no sections or lines can be logically matched between the two documents,
return exactly ONE array element:

{
  "page_number": 0,
  "subtitle": "",
  "original_text": "Impossible to match files",
  "revised_text": ""
}

IMPORTANT RULES:
- Always return valid JSON.
- Do not include explanations.
- Do not include comments.
- Do not use markdown.
- Do not include unchanged content.
- Do not infer missing sections.
- Do not reorder the detected differences.
- Preserve the original wording exactly as it appears in each document.
- If the response in empty return an empty json array';

// Upload the first PDF file to Gemini
$requestFile = $pmapi->getRequestFilesById($requestId, $fileId_1);
$requestFilePath_1 = $requestFile->getPathname();
$fileUri_1 = $cw->doUpload($requestFilePath_1, 'Original', mime_content_type($requestFilePath_1));
$paramGeminiFile1 = ['file_data' => ['mime_type' => mime_content_type($requestFilePath_1), 'file_uri' => $fileUri_1]];
$paramGemini = [
    ['text' => utf8_encode($promptValidate)]
];

// Set Type of analisys
if ($data["CQP_SUBMIT_SLIP"] == "VALIDATE") {
    if (isset($fileId_1)) {
        $grid = "SLIP";
        $runScript = true;
        $responseScript["CQP_GEMINI_FILE_" . $data["CQP_SUBMIT_SLIP"]] = $fileId_1;
        $paramGemini[] = $paramGeminiFile1;
        $responseScript["CQP_GEMINI_FILE_COMPARE"] = null;
    }
} else {
    if (isset($fileId_1) && isset($fileId_2)) {
        $grid = "NEW";
        $runScript = true;
        $responseScript["CQP_GEMINI_FILE_" . $data["CQP_SUBMIT_SLIP"]] = $fileId_2;

        // Upload the seconf PDF file to Gemini
        $requestFile = $pmapi->getRequestFilesById($requestId, $fileId_2);
        $requestFilePath_2 = $requestFile->getPathname();
        $fileUri_2 = $cw->doUpload($requestFilePath_2, 'Revised', mime_content_type($requestFilePath_2));
        $paramGeminiFile2 = ['file_data' => ['mime_type' => mime_content_type($requestFilePath_2), 'file_uri' => $fileUri_2]];
        
        // Compare Slip File, with new file
        $paramGeminiCompare = [
            ['text' => utf8_encode($promptCompare)],
            $paramGeminiFile1,
            $paramGeminiFile2
        ];
        
        $responseGemini = sentToGemini($paramGeminiCompare);
        $responseScript['CQP_GEMINI_RESPONSE_COMPARE'] = $responseGemini['CQP_GEMINI_RESPONSE'];
        $responseScript['CQP_GEMINI_ERROR_COMPARE'] = $responseGemini['CQP_GEMINI_ERROR'];
        $responseScript['CQP_GEMINI_RESULT_COMPARE'] = $responseGemini['CQP_GEMINI_RESULT'];
        $responseScript['CQP_GEMINI_ERROR_COMPARE'] = $responseGemini['CQP_GEMINI_ERROR'];

        $paramGemini[] = $paramGeminiFile2;
    }
}

// Validate clausules in file
if ($grid != "") {
    $responseGemini = sentToGemini($paramGemini);
    $responseScript['CQP_GEMINI_RESPONSE_' . $grid] = $responseGemini['CQP_GEMINI_RESPONSE'];
    $responseScript['CQP_GEMINI_ERROR_' . $grid] = $responseGemini['CQP_GEMINI_ERROR'];
    $responseScript['CQP_GEMINI_RESULT_' . $grid] = $responseGemini['CQP_GEMINI_RESULT'];
    $responseScript['CQP_GEMINI_ERROR_' . $grid] = $responseGemini['CQP_GEMINI_ERROR'];
}

// Clear error variable to avoid previous error to be shown afterwards
$responseScript["resErrorHandling"] = "";
$responseScript["FORTE_ERROR"] = ['data' => ["FORTE_ERROR_LOG" => ""]];
$responseScript["FORTE_ERROR_MESSAGE"] = "";

return $responseScript;


/* Sen Parameters to Gemini analisys
 *
 * @param array $param
 * @return array $responseGemini
 *
 * by Diego Tapia
*/

function sentToGemini($param) {
    global $cw;
    $responseGemini = [];

    try {
        $geminiAPI = new Client();
        $gemini_response_raw = $geminiAPI->request("POST", $cw->apiUrl . "/v1beta/models/{$cw->apiModel}:generateContent?key={$cw->apiKey}", [
            "headers" => [
                "Content-Type" => "application/json",
                "Accept" => "application/json"
            ],
            "json" => [
                "contents" => [
                    [
                        'parts' => $param
                    ]
                ],
                "generationConfig"=> [
                    "temperature"=> 0.0,
                    "topP"=> 1,
                    "topK"=> 1

                ]
            ]
        ]);
        
        $gemini_response = json_decode($gemini_response_raw->getBody()->getContents(), true);
        
        if (isset($gemini_response["candidates"][0]["content"]["parts"][0]["text"])) {
            $txt = $gemini_response["candidates"][0]["content"]["parts"][0]["text"];
            $responseGemini['CQP_GEMINI_RESPONSE'] = json_decode($txt, true);
        } elseif (isset($gemini_response["candidates"][0]["finishReason"])) {
            $responseGemini['CQP_GEMINI_ERROR'] = $gemini_response["candidates"];
        } else {
            $responseGemini['CQP_GEMINI_RESULT'] = $gemini_response;
        }
    } catch (RequestException $ex) {
        $responseGemini['CQP_GEMINI_ERROR'] = $ex->getMessage();
    }

    return $responseGemini;
}

class CloudAPIClient {
    public $apiUrl;
    public $apiKey;
    public $apiModel;
    public $client;
    
    public function __construct() {
        $this->client = new Client(['verify' => false]);
        $this->prepareAccessToken();
	}
    
	public function prepareAccessToken() {
    	$this->apiUrl = getenv("FORTE_GEMINI_URL");
        $this->apiKey = getenv("FORTE_GEMINI_KEY");
        $this->apiModel = getenv("FORTE_GEMINI_MODEL");

	}
    
    public function doUpload($docPath, $displayName, $mime_type) {
        $num_bytes = filesize($docPath);
        
        // Initial resumable request (start upload)
        $response = $this->client->post($this->apiUrl . "/upload/v1beta/files?key=" . $this->apiKey, [
            'headers' => [
                "X-Goog-Upload-Protocol" => "resumable",
                "X-Goog-Upload-Command" => "start",
                "X-Goog-Upload-Header-Content-Length" => (string)$num_bytes,
                "X-Goog-Upload-Header-Content-Type" => $mime_type,
                "Content-Type" => "application/json"
            ],
            'json' => ['file' => ['display_name' => $displayName]]
        ]);

        $uploadUrl = $response->getHeaderLine('X-Goog-Upload-Url');
        
        // Upload the PDF data
        $response = $this->client->post($uploadUrl, [
            'headers' => [
                "Content-Length" => (string)$num_bytes,
                "X-Goog-Upload-Offset" => "0",
                "X-Goog-Upload-Command" => "upload, finalize",
                "Content-Type" => "application/json"
            ],
           'body' => Utils::tryFopen($docPath, 'r')
        ]);

        $file_info = json_decode($response->getBody()->getContents(), true);
        
        if (!isset($file_info['file']['uri'])) {
            die("Error: Could not get file URI for {$display_name}. Response: " . json_encode($file_info) . "\n");
        }

        $fileUri = $file_info['file']['uri'];

        return $fileUri;
    }
}