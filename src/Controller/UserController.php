<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use App\Service\UserService;
use Exception;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * @Route("/api/users")
 */
class UserController extends AbstractController
{
    private $userService;

    public function __construct(UserService $userService)
    {
        $this->userService = $userService;
    }

    /**
     * POST /api/users/login
     *
     * @Route("/login", methods={"POST"})
     */
    public function login(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (!isset($data['email'])) {
            return new JsonResponse(['error' => 'Email is required'], 400);
        }

        if (!isset($data['name'])) {
            return new JsonResponse(['error' => 'Name is required'], 400);
        }

        if (!isset($data['role'])) {
            return new JsonResponse(['error' => 'Role is required'], 400);
        }

        try {
            $user = $this->userService->login($data);
            return new JsonResponse([
                'id' => $user->getId(),
                'email' => $user->getEmail(),
                'name' => $user->getName(),
                'role' => $user->getRole(),
            ]);
        } catch (\Exception $e) {
            return new JsonResponse(['error' => $e->getMessage()], 400);
        }
    }
}