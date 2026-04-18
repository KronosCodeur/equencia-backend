<?php

declare(strict_types=1);

namespace App\Interface\Controller;

use App\Application\Command\RegisterTenant\RegisterTenantCommand;
use App\Application\DTO\RegisterRequest;
use App\Domain\Entity\User;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\HandledStamp;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

#[Route('/api/auth', name: 'auth_')]
final class AuthController extends AbstractController
{
    public function __construct(private readonly MessageBusInterface $bus) {}

    #[Route('/register', name: 'register', methods: ['POST'])]
    public function register(#[MapRequestPayload] RegisterRequest $request): JsonResponse
    {
        $envelope = $this->bus->dispatch(new RegisterTenantCommand(
            companyName:    $request->companyName,
            sector:         $request->sector,
            adminEmail:     $request->email,
            adminPassword:  $request->password,
            adminFirstName: $request->firstName,
            adminLastName:  $request->lastName,
            adminPhone:     $request->phone,
        ));

        $tenantId = $envelope->last(HandledStamp::class)?->getResult();

        return $this->json(
            ['tenant_id' => $tenantId, 'message' => 'Compte créé avec succès.'],
            Response::HTTP_CREATED,
        );
    }

    #[Route('/me', name: 'me', methods: ['GET'])]
    public function me(#[CurrentUser] User $user): JsonResponse
    {
        return $this->json([
            'id'        => $user->id()->value,
            'email'     => $user->email(),
            'firstName' => $user->firstName(),
            'lastName'  => $user->lastName(),
            'fullName'  => $user->fullName(),
            'role'      => $user->role()->value,
            'tenantId'  => $user->tenantId()->value,
        ]);
    }

    #[Route('/logout', name: 'logout', methods: ['POST'])]
    public function logout(Request $request): JsonResponse
    {
        $response = $this->json(['message' => 'Déconnecté avec succès.']);

        $response->headers->setCookie(
            Cookie::create('REFRESH_TOKEN')
                ->withExpires(0)
                ->withPath('/api/auth/refresh')
                ->withHttpOnly(true)
                ->withSecure($request->isSecure())
                ->withSameSite('strict'),
        );

        return $response;
    }

    #[Route('/health', name: 'health', methods: ['GET'])]
    public function health(): JsonResponse
    {
        return $this->json(['status' => 'ok', 'service' => 'auth', 'version' => '1.0.0']);
    }
}
