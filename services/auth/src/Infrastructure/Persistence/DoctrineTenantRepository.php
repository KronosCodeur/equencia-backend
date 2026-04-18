<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence;

use App\Domain\Entity\Tenant;
use App\Domain\Repository\TenantRepositoryInterface;
use Doctrine\ORM\EntityManagerInterface;
use Equencia\Shared\ValueObject\TenantId;

final class DoctrineTenantRepository implements TenantRepositoryInterface
{
    public function __construct(
        private readonly EntityManagerInterface $em,
    ) {}

    public function save(Tenant $tenant): void
    {
        $this->em->persist($tenant);
        $this->em->flush();
    }

    public function findById(TenantId $id): ?Tenant
    {
        return $this->em->createQueryBuilder()
            ->select('t')
            ->from(Tenant::class, 't')
            ->where('t.id = :id')
            ->setParameter('id', $id->value)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findBySlug(string $slug): ?Tenant
    {
        return $this->em->createQueryBuilder()
            ->select('t')
            ->from(Tenant::class, 't')
            ->where('t.slug = :slug')
            ->setParameter('slug', $slug)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function slugExists(string $slug): bool
    {
        return $this->findBySlug($slug) !== null;
    }
}
