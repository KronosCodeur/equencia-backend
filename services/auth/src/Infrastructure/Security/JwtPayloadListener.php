<?php

declare(strict_types=1);

namespace App\Infrastructure\Security;

use App\Domain\Entity\User;
use App\Domain\Repository\TenantRepositoryInterface;
use Equencia\Shared\ValueObject\TenantId;
use Lexik\Bundle\JWTAuthenticationBundle\Event\JWTCreatedEvent;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

#[AsEventListener(event: 'lexik_jwt_authentication.on_jwt_created')]
final class JwtPayloadListener
{
    public function __construct(
        private readonly TenantRepositoryInterface $tenants,
    ) {}

    public function __invoke(JWTCreatedEvent $event): void
    {
        $user = $event->getUser();

        if (!$user instanceof User) {
            return;
        }

        $tenant = $this->tenants->findById(TenantId::from($user->tenantId()->value));

        $payload = $event->getData();
        $payload['tenant_id']   = $user->tenantId()->value;
        $payload['tenant_slug'] = $tenant?->slug() ?? '';
        $payload['role']        = $user->role()->value;
        $payload['plan']        = $tenant?->plan()->value ?? 'starter';
        $payload['first_name']  = $user->firstName();
        $payload['last_name']   = $user->lastName();

        $event->setData($payload);
    }
}
