<?php

declare(strict_types=1);

namespace App\Shared\Service\AmoCrm\AccessTokenManager;

use App\Shared\Exception\AmoCrmAuthorizationException;
use Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\HttpExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

class AmoCrmAccessTokenManager implements AmoCrmAccessTokenManagerInterface
{
    protected ?ResponseInterface $authorizationResponse = null;

    private const REQUEST_METHOD = 'POST';
    private const REQUEST_PROTOCOL = 'https';
    private const REQUEST_HOST = 'amocrm.ru';
    private const AUTH_PATH = '/oauth2/access_token';
    private const AUTH_GRANT_TYPE = 'authorization_code';
    private const REFRESH_GRANT_TYPE = 'refresh_token';
    private const REQUEST_CONTENT_TYPE = 'application/json';

    public function __construct(
        private HttpClientInterface $httpClient,
        private string $accountName,
        private string $clientId,
        private string $clientSecret,
        private string $authorizationCode,
        private string $redirectUri,
    ) {
    }

    public function sendAuthorizationRequest(): void
    {
        $authUrl = self::REQUEST_PROTOCOL . '://' . $this->accountName . '.' . self::REQUEST_HOST . self::AUTH_PATH;
        $this->authorizationResponse = $this->httpClient->request(self::REQUEST_METHOD, $authUrl, [
            'headers' => [
                'Content-Type' => self::REQUEST_CONTENT_TYPE,
            ],
            'body' => [
                'client_id' => $this->clientId,
                'client_secret' => $this->clientSecret,
                'grant_type' => self::AUTH_GRANT_TYPE,
                'code' => $this->authorizationCode,
                'redirect_uri' => $this->redirectUri,
            ],
        ]);
    }

    public function getAccessToken(): string
    {
        return $this->getAuthorizationResponseContentValueByKeyOrThrowException('access_token');
    }

    public function getAccessTokenExpirationSeconds(): int
    {
        return $this->getAuthorizationResponseContentValueByKeyOrThrowException('expires_in');
    }

    public function getRefreshTokenExpirationMonths(): int
    {
        return 3;
    }

    public function refreshAccessToken(): void
    {
        $refreshUrl = self::REQUEST_PROTOCOL . '://' . $this->accountName . '.' . self::REQUEST_HOST . self::AUTH_PATH;
        $this->authorizationResponse = $this->httpClient->request(
            self::REQUEST_METHOD,
            $refreshUrl,
            [
                'headers' => [
                    'Content-Type' => self::REQUEST_CONTENT_TYPE,
                ],
                'body' => [
                    'client_id' => $this->clientId,
                    'client_secret' => $this->clientSecret,
                    'grant_type' => self::REFRESH_GRANT_TYPE,
                    'refresh_token' => $this->getRefreshToken(),
                    'redirect_uri' => $this->redirectUri,
                ],
            ]
        );
    }

    protected function getRefreshToken(): string
    {
        return $this->getAuthorizationResponseContentValueByKeyOrThrowException('refresh_token');
    }

    private function getAuthorizationResponseContentValueByKeyOrThrowException(string $key): int|string
    {
        if ($this->authorizationResponse === null) {
            throw new \BadMethodCallException('Method' . __METHOD__ . ' called without sending authorization request');
        }

        // since responses are lazy, check for exceptions before fetching content
        // see https://symfony.com/doc/current/http_client.html#handling-exceptions
        try {
            return $this->authorizationResponse->toArray()[$key];
        } catch (HttpExceptionInterface $httpException) {
            throw new AmoCrmAuthorizationException(
                'Response from AmoCRM returned bad status code',
                previous: $httpException
            );
        } catch (TransportExceptionInterface $transportException) {
            throw new AmoCrmAuthorizationException(
                'Low level exception occurred',
                previous: $transportException
            );
        } catch (DecodingExceptionInterface $decodingException) {
            throw new AmoCrmAuthorizationException(
                'Unable to decode content of request',
                previous: $decodingException
            );
        }
    }
}
