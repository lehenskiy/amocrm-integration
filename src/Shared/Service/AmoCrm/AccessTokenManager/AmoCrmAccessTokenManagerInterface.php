<?php

declare(strict_types=1);

namespace App\Shared\Service\AmoCrm\AccessTokenManager;

use App\Shared\Exception\AmoCrmAuthorizationException;
use BadMethodCallException;

interface AmoCrmAccessTokenManagerInterface
{
    /**
     * @throws AmoCrmAuthorizationException
     * @throws BadMethodCallException
     */
    public function getAccessToken(): string;

    /**@throws BadMethodCallException */
    public function refreshAccessToken(): void;
}
