<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use App\Service\ChatService;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * @Route("/api/chats")
 */
class ChatController extends AbstractController
{
    private $chatService;

    public function __construct(ChatService $chatService)
    {
        $this->chatService = $chatService;
    }

    /**
     * POST /api/chats
     * 
     * @Route("", methods={"POST"})
     */
    public function create(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
    
        if (!isset($data['user_id'])) {
            return new JsonResponse(['error' => 'User id not provided'], 400);
        }

        $this->chatService->createChat($data['user_id']);

        return new JsonResponse(['status' => 'created']);
    }

    /**
     * GET /api/chats
     * 
     * @Route("", methods={"GET"})
     */
    public function list(Request $request): JsonResponse
    {
        $limit = min((int)$request->query->get('limit', 50), 100);

        $this->chatService->listChats($limit);

        return new JsonResponse(['status' => 'listed']);
    }

    /**
     * POST /api/chats/{id}/close
     * 
     * @Route("/{id}/close", methods={"POST"})
     */
    public function close(int $id, Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
    
        if (!isset($data['user_id'])) {
            return new JsonResponse(['error' => 'User id not provided'], 400);
        }

        $this->chatService->closeChat($id, $data['user_id']);

        return new JsonResponse(['status' => 'closed']);
    }

    /**
     * POST /api/chats/{id}/messages
     * 
     * @Route("/{id}/messages", methods={"POST"})
     */
    public function sendMessage(int $id, Request $request)
    {
        $data = json_decode($request->getContent(), true);
    
        if (!isset($data['user_id']) || !isset($data['message'])) {
            return new JsonResponse(['error' => 'User id and message required'], 400);
        }

        $this->chatService->sendMessage($id, $data['user_id'], $data['message']);

        return new JsonResponse(['status' => 'message sent']);
    }
}