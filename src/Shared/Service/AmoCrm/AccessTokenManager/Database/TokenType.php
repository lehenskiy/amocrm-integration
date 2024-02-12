<?php

declare(strict_types=1);

namespace App\Shared\Service\AmoCrm\AccessTokenManager\Database;

enum TokenType: int
{
    case Access = 1;
    case Refresh = 2;
}
