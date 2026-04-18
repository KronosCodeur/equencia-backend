<?php

declare(strict_types=1);

namespace App\Infrastructure\Security;

use Doctrine\DBAL\Connection;
use Equencia\Shared\ValueObject\TenantId;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;

final class TenantMiddleware
{
    public function __construct(
        private readonly TenantContext $tenantContext,
        private readonly JWTTokenManagerInterface $jwtManager,
        private readonly Connection $connection,
    ) {}

    public function onKernelRequest(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $authHeader = $event->getRequest()->headers->get('Authorization', '');

        if (!str_starts_with($authHeader, 'Bearer ')) {
            return;
        }

        $payload = $this->jwtManager->parse(substr($authHeader, 7));

        if (!isset($payload['tenant_id'])) {
            return;
        }

        $tenantId = TenantId::from($payload['tenant_id']);
        $this->tenantContext->set($tenantId);

        // Positionne le tenant pour le RLS PostgreSQL
        $this->connection->executeStatement(
            "SET app.current_tenant_id = ?",
            [$tenantId->value],
        );
    }
}
