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
        $userId = (int)$request->query->get('user_id');
        $limit = min((int)$request->query->get('limit', 50), 100);

        $chats = $this->chatService->listChats($userId, $limit);

        $data = array_map(function($chat) {
            return [
                'id' => $chat->getId(),
                'userId' => $chat->getUserId(),
                'operatorId' => $chat->getOperatorId(),
                'status' => $chat->getStatus(),
                'lastMessageAt' => $chat->getLastMessageAt() ? $chat->getLastMessageAt()->format('Y-m-d H:i:s') : null,
            ];
        }, $chats);

        return new JsonResponse($data);
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
    public function sendMessage(int $id, Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        
        if (!isset($data['user_id']) || !isset($data['message'])) {
            return new JsonResponse(['error' => 'User id and message required'], 400);
        }

        try {
            $message = $this->chatService->sendMessage($id, $data['user_id'], $data['message'], $$data['client_msg_id']);
            return new JsonResponse([
                'id' => $message->getId(),
                'text' => $message->getText(),
                'createdAt' => $message->getCreatedAt()->format('Y-m-d H:i:s')
            ]);
        } catch (\Exception $e) {
            return new JsonResponse(['error' => $e->getMessage()], 400);
        }
    }

    /**
     * GET /api/chats/{id}/messages
     * 
     * @Route("/{id}/messages", methods={"GET"})
     */
    public function getMessages(int $id, Request $request): JsonResponse
    {
        $limit = min((int)$request->query->get('limit', 50), 100);
        $beforeId = $request->query->get('before_id') ? (int)$request->query->get('before_id') : null;
        $afterId = $request->query->get('after_id') ? (int)$request->query->get('after_id') : null;
        $userId = $request->query->get('user_id') ? (int)$request->query->get('user_id') : null;

        $messages = $this->chatService->getMessages($id, $userId, $limit, $beforeId, $afterId);

        $data['messages'] = array_map(function($message) {
            return [
                'id' => $message->getId(),
                'text' => $message->getText(),
                'direction' => $message->getDirection(),
                'userId' => $message->getUserId(),
                'createdAt' => $message->getCreatedAt()->format('Y-m-d H:i:s'),
                'clientMsgId' => $message->getClientMsgId(),
                'status' => $message->getStatus(),
            ];
        }, $messages);

        $chat = $this->chatService->getChatById($id);
        $data['chatStatus'] = $chat->getStatus();

        return new JsonResponse($data);
    }

    /**
     * POST /api/chats/{id}/assign-operator
     * 
     * @Route("/{id}/assign-operator", methods={"POST"})
     */
    public function assignOperator(int $id, Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
    
        if (!isset($data['operator_id'])) {
            return new JsonResponse(['error' => 'Operator id not provided'], 400);
        }

        $this->chatService->assignOperator($id, $data['operator_id']);

        return new JsonResponse(['status' => 'operator assigned']);
    }

    /**
     * GET /api/chats/{id}/events
     * 
     * @Route("/{id}/events", methods={"GET"})
     */
    public function events(int $id, Request $request)
    {
        // $afterId = $request->query->get('after_id') ? (int)$request->query->get('after_id') : 0;

        // $response = new \Symfony\Component\HttpFoundation\StreamedResponse();
        // $response->setCallback(function () use ($id, $afterId) {
        //     $lastId = $afterId;
        //     while (true) {
        //         $messages = $this->chatService->getMessages($id, 100, null, $lastId);
        //         foreach ($messages as $message) {
        //             echo "data: " . json_encode([
        //                 'id' => $message->getId(),
        //                 'text' => $message->getText(),
        //                 'direction' => $message->getDirection(),
        //                 'userId' => $message->getUserId(),
        //                 'createdAt' => $message->getCreatedAt()->format('Y-m-d H:i:s'),
        //             ]) . "\n\n";
        //             $lastId = $message->getId();
        //         }
        //         ob_flush();
        //         flush();
        //         sleep(1); // Poll every second
        //     }
        // });

        // $response->headers->set('Content-Type', 'text/event-stream');
        // $response->headers->set('Cache-Control', 'no-cache');
        // $response->headers->set('Connection', 'keep-alive');

        // return $response;
    }
}