<?php

use GuzzleHttp\Client;
use GuzzleHttp\Exception\BadResponseException;

class AzureHandler
{
    protected $tenantId;
    protected $clientId;
    protected $clientSecret;
    private $apiClient;

    public function __construct()
    {
        $this->tenantId = getenv('AZURE_TENANT_ID');
        $this->clientId = getenv('AZURE_CLIENT_ID');
        $this->clientSecret = getenv('AZURE_CLIENT_SECRET');
        $this->setApiClient();
    }

public function testConnection()
{
    try {
        // Cambia el endpoint a 'users', que es válido para el client credentials flow
        $response = $this->apiClient->get("users");

        // Si la solicitud es exitosa, devuelve un array con el resultado positivo y la respuesta del API
        return [
            'outcome' => 'success',
            'message' => 'Connection successful',
            'response' => json_decode($response->getBody()->getContents())
        ];
    } catch (BadResponseException $e) {
        // Maneja el error y devuelve el mensaje del error
        return [
            'outcome' => 'failed',
            'message' => json_decode($e->getResponse()->getBody()->getContents(), true),
        ];
    }
}



    /**
     * Set Guzzle API Client
     *
     * @return void
     */
    private function setApiClient()
    {
        try {
            $apiUrl = "https://login.microsoftonline.com/{$this->tenantId}/oauth2/v2.0/token";
            $client = new Client();
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
            return [
                'outcome' => 'failed',
                'message' => json_decode($e->getResponse()->getBody()->getContents(), true),
            ];
        }
    }

    /**
     * Format error response
     *
     * @param array $error
     * @return string
     */
    private function formatError($error)
    {
        return isset($error['error']) ? $error['error']['message'] : 'An unknown error occurred';
    }
}

$handler = new AzureHandler();
return $handler->testConnection();