<?php

declare(strict_types=1);

namespace App\Application\Command\RegisterTenant;

use App\Domain\Entity\Tenant;
use App\Domain\Entity\TenantPlan;
use App\Domain\Entity\User;
use App\Domain\Entity\UserRole;
use App\Domain\Repository\TenantRepositoryInterface;
use App\Domain\Repository\UserRepositoryInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[AsMessageHandler]
final class RegisterTenantHandler
{
    public function __construct(
        private readonly TenantRepositoryInterface $tenants,
        private readonly UserRepositoryInterface $users,
        private readonly UserPasswordHasherInterface $hasher,
    ) {}

    public function __invoke(RegisterTenantCommand $command): string
    {
        $slug = $this->generateSlug($command->companyName);

        if ($this->tenants->slugExists($slug)) {
            $slug = $slug . '-' . substr(uniqid(), -4);
        }

        if ($this->users->emailExists($command->adminEmail)) {
            throw new \DomainException('Un compte existe déjà avec cet email.');
        }

        $tenant = Tenant::create($command->companyName, $slug, TenantPlan::Starter);
        $this->tenants->save($tenant);

        $adminUser = User::create(
            tenantId: $tenant->id(),
            email: $command->adminEmail,
            passwordHash: '',
            role: UserRole::Admin,
            firstName: $command->adminFirstName,
            lastName: $command->adminLastName,
            phoneWhatsapp: $command->adminPhone,
        );

        $hashedPassword = $this->hasher->hashPassword($adminUser, $command->adminPassword);
        $adminUser->setPasswordHash($hashedPassword);

        $this->users->save($adminUser);

        return $tenant->id()->value;
    }

    private function generateSlug(string $name): string
    {
        $slug = strtolower($name);
        $slug = preg_replace('/[^a-z0-9\s-]/', '', $slug);
        $slug = preg_replace('/[\s-]+/', '-', $slug);
        return trim($slug, '-');
    }
}
