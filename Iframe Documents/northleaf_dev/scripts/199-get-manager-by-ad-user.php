<?php
/**********************************
 * OFF - Get Manager of a User by Display Name from Azure AD
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
     * Search for a user by their display name and return their userPrincipalName (email).
     *
     * @param string $displayName - The display name of the user to search.
     * @return string|null - The userPrincipalName of the found user or null if not found.
     *
     * by Adriana Centellas
     */
    public function getUserByDisplayName($displayName)
    {
        try {
            // Search for the user by display name
            $response = $this->apiClient->get("users?\$filter=displayName eq '{$displayName}'");
            $users = json_decode($response->getBody()->getContents());

            // Check if any users were found
            if (count($users->value) > 0) {
                // Return the userPrincipalName of the first matched user
                return $users->value[0]->userPrincipalName;
            }

            // If no user was found, return null
            return null;
        } catch (BadResponseException $e) {
            // Handle any error and return null
            return null;
        }
    }

    /**
     * Fetch the manager of a specific user from Azure AD by userPrincipalName.
     *
     * @param string $userPrincipalName - The userPrincipalName (email) of the user whose manager is being fetched.
     * @return array|null - Structured array of the manager's details or null if no manager is found.
     *
     * by Adriana Centellas
     */
    public function getUserManager($userPrincipalName)
    {
        try {
            // Make an API call to get the manager of the user
            $managerResponse = $this->apiClient->get("users/{$userPrincipalName}/manager");
            $manager = json_decode($managerResponse->getBody()->getContents());

            // Return the manager's details in a formatted array
            return [[
                'displayName' => $manager->displayName,
                'givenName' => $manager->givenName,
                'jobTitle' => $manager->jobTitle,
                'mail' => $manager->mail,
                'officeLocation' => $manager->officeLocation,
                'surname' => $manager->surname,
                'userPrincipalName' => $manager->userPrincipalName
            ]];
        } catch (BadResponseException $e) {
            // Handle any errors and return null
            return null;
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
}

$handler = new AzureHandler();
$displayName = $data["displayName"]; 

// Search for the user by display name
$userPrincipalName = $handler->getUserByDisplayName($displayName);

if ($userPrincipalName) {
    // Get the manager of the user if the user was found
    $managerInfo = $handler->getUserManager($userPrincipalName);
    return $managerInfo;
} else {
    return "User not found.";
}