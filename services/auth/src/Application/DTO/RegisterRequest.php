<?php

declare(strict_types=1);

namespace App\Application\DTO;

use Symfony\Component\Validator\Constraints as Assert;

final readonly class RegisterRequest
{
    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Length(min: 2, max: 100)]
        public readonly string $companyName,

        #[Assert\NotBlank]
        #[Assert\Length(max: 50)]
        public readonly string $sector,

        #[Assert\NotBlank]
        #[Assert\Email]
        public readonly string $email,

        #[Assert\NotBlank]
        #[Assert\Length(min: 8)]
        public readonly string $password,

        #[Assert\NotBlank]
        #[Assert\Length(min: 2, max: 50)]
        public readonly string $firstName,

        #[Assert\NotBlank]
        #[Assert\Length(min: 2, max: 50)]
        public readonly string $lastName,

        public readonly ?string $phone = null,
    ) {}
}
