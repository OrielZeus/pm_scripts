<?php
/**********************************
 * OFF - Get All AD users information
 *
 * by Adriana Centellas
 *********************************/
use GuzzleHttp\Client;
use GuzzleHttp\Exception\BadResponseException;

class AzureHandler
{
    protected $tenantId;
    protected $clientId;
    protected $clientSecret;
    private $apiClient;

    /**
     * Constructor to initialize AzureHandler class with environment variables
     * for Azure tenant and client credentials. Also initializes the API client.
     */
    public function __construct()
    {
        // Set Azure tenant ID, client ID, and client secret from environment variables
        $this->tenantId = getenv('AZURE_TENANT_ID');
        $this->clientId = getenv('AZURE_CLIENT_ID');
        $this->clientSecret = getenv('AZURE_CLIENT_SECRET');

        // Initialize the API client
        $this->setApiClient();
    }

    /**
     * Fetch all users from the Azure AD and return formatted data.
     *
     * @return array - Structured array of employee users.
     *
     * by Adriana Centellas
     */
    public function getAllUsers()
    {
        try {
            // Set the API endpoint to fetch all users
            $url = "users";
            $employeeUsers = [];

            // Loop to handle pagination in API responses
            do {
                // Fetch the users from the API
                $response = $this->apiClient->get($url);
                $users = json_decode($response->getBody()->getContents());

                // Iterate through each user and retrieve their details
                foreach ($users->value as $user) {
                    // Format the employee user data without manager details
                    $employeeUser = [
                        'displayName' => $user->displayName,
                        'givenName' => $user->givenName,
                        'jobTitle' => $user->jobTitle,
                        'mail' => $user->mail,
                        'officeLocation' => $user->officeLocation,
                        'surname' => $user->surname,
                        'userPrincipalName' => $user->userPrincipalName
                    ];

                    // Add the formatted employee user data to the result array
                    $employeeUsers[] = $employeeUser;
                }

                // Check if there is a next page of users to fetch (via @odata.nextLink)
                $url = $users->{'@odata.nextLink'} ?? null;

            } while ($url); // Continue pagination if there are more users to fetch

            // Return the structured employee user data
            return [
                'employeeUsers' => $employeeUsers
            ];
        } catch (BadResponseException $e) {
            // Handle any error and return the error message
            return [
                'outcome' => 'failed',
                'message' => json_decode($e->getResponse()->getBody()->getContents(), true),
            ];
        }
    }

    /**
     * Initialize the Guzzle API client with an OAuth2 bearer token.
     *
     * @return void
     *
     * by Adriana Centellas
     */
    private function setApiClient()
    {
        try {
            // Construct the API URL for fetching the OAuth2 token
            $apiUrl = "https://login.microsoftonline.com/{$this->tenantId}/oauth2/v2.0/token";
            $client = new Client();

            // Request the OAuth2 token from Azure using the client credentials flow
            $response = json_decode(
                $client->post(
                    $apiUrl,
                    [
                        'form_params' => [
                            'client_id' => $this->clientId,
                            'client_secret' => $this->clientSecret,
                            'scope' => 'https://graph.microsoft.com/.default',
                            'grant_type' => 'client_credentials',
                        ],
                    ]
                )->getBody()->getContents()
            );

            // Initialize the Guzzle client with the received bearer token for future API requests
            $this->apiClient = new Client(
                [
                    'headers' => [
                        'Authorization' => 'Bearer ' . $response->access_token,
                        'Accept' => 'application/json',
                        'Content-Type' => 'application/json',
                    ],
                    'base_uri' => 'https://graph.microsoft.com/v1.0/',
                ]
            );
        } catch (BadResponseException $e) {
            // Handle the error and return the error message
            return [
                'outcome' => 'failed',
                'message' => json_decode($e->getResponse()->getBody()->getContents(), true),
            ];
        }
    }

    /**
     * Format error response for better readability.
     *
     * @param array $error - The error array returned by the API.
     * @return string - A formatted error message.
     *
     * by Adriana Centellas
     */
    private function formatError($error)
    {
        return isset($error['error']) ? $error['error']['message'] : 'An unknown error occurred';
    }
}

// Initialize the AzureHandler class
$handler = new AzureHandler();

// Set initial values
$dataReturn = array();

// Return the result
$getDataEmployees = $handler->getAllUsers();

//Filter Callrooms and AA type of users
$filteredUsers = array_filter($getDataEmployees["employeeUsers"], function($user) {
    return strpos($user['displayName'], 'Callroom') === false && strpos($user['displayName'], 'AA') === false && strpos($user['displayName'], 'Admin') === false;
});

//Format array to final
$dataReturn = [
    "employeeUsers" => array_values($filteredUsers)
];

return $dataReturn;