<?php

declare(strict_types=1);

namespace App\Infrastructure\Security;

use App\Domain\Repository\UserRepositoryInterface;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use App\Domain\Entity\User;

final class JwtUserProvider implements UserProviderInterface
{
    public function __construct(
        private readonly UserRepositoryInterface $users,
    ) {}

    public function loadUserByIdentifier(string $identifier): UserInterface
    {
        $user = $this->users->findByEmail($identifier);

        if ($user === null) {
            throw new UserNotFoundException(sprintf('User "%s" not found.', $identifier));
        }

        return $user;
    }

    public function refreshUser(UserInterface $user): UserInterface
    {
        if (!$user instanceof User) {
            throw new UnsupportedUserException(sprintf('Unsupported user class "%s".', $user::class));
        }

        return $this->loadUserByIdentifier($user->getUserIdentifier());
    }

    public function supportsClass(string $class): bool
    {
        return $class === User::class || is_subclass_of($class, User::class);
    }
}
