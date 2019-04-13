<?php
/**
 * API Wrapper
 */
namespace OpenBlock\Api;

use Http\Discovery\HttpClientDiscovery;
use Http\Discovery\MessageFactoryDiscovery;

class ApiClient
{
    /**
     * @@var Authenticator|null Authentication Creation Object
     */
    private $authenticator;

    /**
     * Constructor which takes a request factory and an authentication class
     * @param RequestFactory $requestFactory Http Client
     * @param Authenticator $authenticator Authentication Object
     */
    public function __construct(Authenticator $authenticator)
    {
        $this->authenticator = $authenticator;
    }

    /**
     * Create a new signed request and return the response
     * @param string $method HTTP Method
     * @param string $endpoint HTTP Endpoint
     * @param array $data HTTP Request Data
     * @return array JSON Ressponse Data
     */
    public function request(string $method, string $endpoint, array $data) : ?array
    {
        $client = HttpClientDiscovery::find();
        $messageFactory = MessageFactoryDiscovery::find();

        $url = 'http://178.62.21.9/' . $endpoint;
        $nonce = (int) time();
        $signature = $this->signRequest($method, $url, $nonce, $data);

        $headers = [
            'X-API-NONCE' => $nonce,
            'X-API-KEY' => $this->authenticator->getPublicKey(),
            'X-API-SIGNATURE' => $signature
        ];

        // Send Request
        $request = $messageFactory->createRequest(
            $method,
            $url,
            $headers,
            http_build_query($data)
        );
        $response = $client->sendRequest($request)->getBody();
        return json_decode($response);
    }

    /**
     * Sign Request Data and return the signature
     * @param string $method HTTP Method to use
     * @param string $url Full URL
     * @param int $nonce Nonce in Request
     * @param array $data HTTP Request Data
     */
    public function signRequest(string $method, string $url, int $nonce, array $data) : string
    {
        $data['nonce'] = $nonce;
        $data['method'] = $method;
        $data['url'] = $url;
        return $this->authenticator->sign($data);
    }
}
