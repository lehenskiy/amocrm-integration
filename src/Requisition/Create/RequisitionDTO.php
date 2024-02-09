<?php

declare(strict_types=1);

namespace App\Requisition\Create;

use Symfony\Component\Validator\Constraints as Assert;

class RequisitionDTO
{
    #[Assert\NotBlank(allowNull: false)]
    #[Assert\NoSuspiciousCharacters]
    public string $name;

    #[Assert\NotBlank(allowNull: false)]
    #[Assert\Email]
    public string $email;

    #[Assert\NotBlank(allowNull: false)]
    #[Assert\Regex(pattern: '/^8[0-9]{10}$/', message: 'Number format: \'8\' + 10 any digits')]
    public string $phoneNumber;

    #[Assert\GreaterThanOrEqual(value: 0.0)]
    #[Assert\NotNull]
    public float $price;
}
