<?php

declare(strict_types=1);

namespace App\Shared\Service\AmoCrm\AccessTokenManager;

use App\Shared\Service\AmoCrm\AccessTokenManager\Database\Token;
use App\Shared\Service\AmoCrm\AccessTokenManager\Database\TokenRepository;
use App\Shared\Service\AmoCrm\AccessTokenManager\Database\TokenType;
use BadMethodCallException;
use DateInterval;
use DateTime;
use DateTimeImmutable;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class AmoCrmDatabaseAccessTokenManager extends AmoCrmAccessTokenManager
{
    private const TOKEN_SERVICE = 'amocrm';

    public function __construct(
        HttpClientInterface $httpClient,
        string $accountName,
        string $clientId,
        string $clientSecret,
        string $authorizationCode,
        string $redirectUri,
        private TokenRepository $tokenRepository,
    ) {
        parent::__construct($httpClient, $accountName, $clientId, $clientSecret, $authorizationCode, $redirectUri);
    }

    public function getAccessToken(): string
    {
        $accessTokenEntity = $this->tokenRepository->findOneBy([
            'service' => self::TOKEN_SERVICE,
            'type' => TokenType::Access,
        ]);
        if ($accessTokenEntity !== null) {
            $currentTime = new DateTimeImmutable();
            if ($currentTime >= $accessTokenEntity->getExpiresAt()) {
                $this->refreshAccessToken();

                return parent::getAccessToken();
            }

            return $accessTokenEntity->getValue();
        }

        $this->sendAuthorizationRequest();
        $accessTokenValue = parent::getAccessToken();
        $refreshTokenValue = parent::getRefreshToken();

        $accessTokenEntity = new Token(
            self::TOKEN_SERVICE,
            $accessTokenValue,
            TokenType::Access,
            $this->getAccessTokenExpirationTime()
        );
        $refreshTokenEntity = new Token(
            self::TOKEN_SERVICE,
            $refreshTokenValue,
            TokenType::Refresh,
            $this->getRefreshTokenExpirationTime(),
        );
        $this->tokenRepository->save($accessTokenEntity);
        $this->tokenRepository->save($refreshTokenEntity, true);

        return $accessTokenValue;
    }

    public function refreshAccessToken(): void
    {
        parent::refreshAccessToken();

        $accessTokenValue = parent::getAccessToken();
        $refreshTokenValue = parent::getRefreshToken();

        $accessTokenEntity = $this->tokenRepository->findOneBy([
            'service' => self::TOKEN_SERVICE,
            'type' => TokenType::Access,
            ]);
        $refreshTokenEntity = $this->tokenRepository->findOneBy([
            'service' => self::TOKEN_SERVICE,
            'type' => TokenType::Refresh,
        ]);

        if ($accessTokenEntity === null) {
            throw new BadMethodCallException(
                "Unexpected to be called without access token in database($accessTokenEntity), call \$this->getAccessToken() before"
            );
        }

        $accessTokenEntity->renew($accessTokenValue, $this->getAccessTokenExpirationTime());
        $refreshTokenEntity->renew($refreshTokenValue, $this->getRefreshTokenExpirationTime());

        $this->tokenRepository->save($accessTokenEntity);
        $this->tokenRepository->save($refreshTokenEntity, true);
    }

    protected function getRefreshToken(): string
    {
        $refreshTokenEntity = $this->tokenRepository->findOneBy([
            'service' => self::TOKEN_SERVICE,
            'type' => TokenType::Refresh,
        ]);

        if ($refreshTokenEntity === null) {
            throw new BadMethodCallException(
                "Unexpected to be called without refresh token in database($refreshTokenEntity), call \$this->getAccessToken() before"
            );
        }

        return $refreshTokenEntity->getValue();
    }

    private function getAccessTokenExpirationTime(): DateTimeImmutable
    {
        $currentTimePlusExpirationSeconds = new DateTime();
        // see https://en.wikipedia.org/wiki/ISO_8601#Durations
        $currentTimePlusExpirationSeconds->add(new DateInterval('PT' . $this->getAccessTokenExpirationSeconds() . 'S'));

        return DateTimeImmutable::createFromMutable($currentTimePlusExpirationSeconds);
    }

    private function getRefreshTokenExpirationTime(): DateTimeImmutable
    {
        $currentTimePlusExpirationMonths = new DateTime();
        $currentTimePlusExpirationMonths->add(new DateInterval('P' . $this->getRefreshTokenExpirationMonths() . 'M'));

        return DateTimeImmutable::createFromMutable($currentTimePlusExpirationMonths);
    }
}
