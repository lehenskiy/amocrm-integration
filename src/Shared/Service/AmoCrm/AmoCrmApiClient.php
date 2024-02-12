<?php

declare(strict_types=1);

namespace App\Shared\Service\AmoCrm;

use App\Shared\Exception\AmoCrmAuthorizationException;
use App\Shared\Exception\AmoCrmSendApiRequestFailedException;
use App\Shared\Service\AmoCrm\AccessTokenManager\AmoCrmAccessTokenManagerInterface;
use Symfony\Contracts\HttpClient\Exception\HttpExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;
use Throwable;

class AmoCrmApiClient
{
    public const POST_REQUEST_METHOD = 'POST';
    private const REQUEST_ACCEPTED_CONTENT_TYPE = 'application/json';
    private const ACCESS_TOKEN_EXPIRED_STATUS_CODE = 401;

    public function __construct(
        private AmoCrmAccessTokenManagerInterface $amoCRMAccessTokenManager,
        private HttpClientInterface $httpClient,
    ) {
    }

    /**
     * @throws AmoCrmAuthorizationException
     * @throws AmoCrmSendApiRequestFailedException
     */
    public function request(string $method, string $url, string $jsonBody): void
    {
        $accessToken = $this->amoCRMAccessTokenManager->getAccessToken();
        $apiResponse = $this->sendAuthorizedRequest($method, $url, $jsonBody, $accessToken);

        // right way to handle HttpClient exceptions
        try {
            $apiResponse->getHeaders();
        } catch (HttpExceptionInterface $httpException) {
            if ($httpException->getResponse()->getStatusCode() === self::ACCESS_TOKEN_EXPIRED_STATUS_CODE) {
                $this->amoCRMAccessTokenManager->refreshAccessToken();
                $refreshedAccessToken = $this->amoCRMAccessTokenManager->getAccessToken();
                $responseWithRefreshedAccessToken = $this->sendAuthorizedRequest(
                    $method,
                    $url,
                    $jsonBody,
                    $refreshedAccessToken
                );

                try {
                    $responseWithRefreshedAccessToken->getHeaders();
                } catch (Throwable $exception) {
                    throw new AmoCrmSendApiRequestFailedException(
                        'Something went wrong during API request with refreshed access token',
                        previous: $exception
                    );
                }
            } else {
                throw new AmoCrmSendApiRequestFailedException(
                    'Unexpected HTTP status code returned by API response with refreshed access token',
                    previous: $httpException
                );
            }
        } catch (TransportExceptionInterface $exception) {
            throw new AmoCrmSendApiRequestFailedException(
                'Something went wrong during API request',
                previous: $exception
            );
        }
    }

    private function sendAuthorizedRequest(
        string $method,
        string $url,
        string $jsonBody,
        string $accessToken
    ): ResponseInterface {
        return $this->httpClient->request($method, $url, [
            'headers' => [
                'Content-Type' => self::REQUEST_ACCEPTED_CONTENT_TYPE,
                'Authorization' => 'Bearer ' . $accessToken,
            ],
            'body' => $jsonBody,
        ]);
    }
}
