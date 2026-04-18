<?php

declare(strict_types=1);

namespace App\Infrastructure\EventListener;

use App\Infrastructure\Security\TenantContext;
use Doctrine\DBAL\Connection;
use Equencia\Shared\ValueObject\TenantId;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

#[AsEventListener(event: KernelEvents::REQUEST, priority: 10)]
final class TenantMiddleware
{
    public function __construct(
        private readonly TokenStorageInterface $tokenStorage,
        private readonly TenantContext $tenantContext,
        private readonly Connection $connection,
    ) {}

    public function __invoke(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $token = $this->tokenStorage->getToken();

        if ($token === null) {
            return;
        }

        $payload = $token->getAttribute('payload');

        if (!isset($payload['tenant_id'])) {
            return;
        }

        $tenantId = TenantId::from($payload['tenant_id']);
        $this->tenantContext->set($tenantId);

        $this->connection->executeStatement(
            "SET app.current_tenant_id = ?",
            [(string) $tenantId],
        );
    }
}
