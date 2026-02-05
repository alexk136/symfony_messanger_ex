<?php

namespace App\Controller;

use App\Entity\User;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
use App\Service\UserService;

class UIController extends AbstractController
{
    private $userService;

    public function __construct(UserService $userService)
    {
        $this->userService = $userService;
    }

    /**
     * @Route("/", name="app_index")
     */
    public function index(): Response
    {
        return $this->render('index.html.twig');
    }

    /**
     * @Route("/login", name="app_login")
     */
    public function login(): Response
    {
        return $this->render('login.html.twig');
    }

    /**
     * @Route("/login-operator", name="app_login_operator")
     */
    public function loginOperator(): Response
    {
        return $this->render('login_operator.html.twig');
    }

    /**
     * @Route("/chats/{user_id}", name="app_chats", requirements={"user_id"="\d+"}, defaults={"user_id"=null})
     */
    public function chats(?int $user_id): Response
    {
        if (!$user_id || !($user = $this->userService->getUserById($user_id))) {
            return $this->redirectToRoute('app_index');
        }

        return $this->render('chats.html.twig', [
            'userId' => $user_id, 
            'userName' => $user->getName(), 
            'role' => $user->getRole(),
            ]);
    }
}