<?php

declare(strict_types=1);

namespace Equencia\Shared\ValueObject;

use Symfony\Component\Uid\Uuid;

final readonly class UserId
{
    private function __construct(public readonly string $value) {}

    public static function from(string $value): self
    {
        if (!Uuid::isValid($value)) {
            throw new \InvalidArgumentException(sprintf('UserId invalide : "%s"', $value));
        }

        return new self($value);
    }

    public static function generate(): self
    {
        return new self((string) Uuid::v4());
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
