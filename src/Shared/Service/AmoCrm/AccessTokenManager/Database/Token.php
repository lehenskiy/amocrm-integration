<?php

declare(strict_types=1);

namespace App\Shared\Service\AmoCrm\AccessTokenManager\Database;

use DateTimeImmutable;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: TokenRepository::class)]
class Token
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 20)]
    private string $service;

    #[ORM\Column(length: 2000)]
    private string $value;

    #[ORM\Column(type: Types::SMALLINT, enumType: TokenType::class)]
    private TokenType $type;

    #[ORM\Column]
    private DateTimeImmutable $expiresAt;

    public function __construct(string $service, string $value, TokenType $type, DateTimeImmutable $expiresAt)
    {
        $this->service = $service;
        $this->value = $value;
        $this->type = $type;
        $this->expiresAt = $expiresAt;
    }

    public function getValue(): string
    {
        return $this->value;
    }

    public function getExpiresAt(): DateTimeImmutable
    {
        return $this->expiresAt;
    }

    public function renew(string $value, DateTimeImmutable $expiresAt): self
    {
        $this->value = $value;
        $this->expiresAt = $expiresAt;

        return $this;
    }
}
