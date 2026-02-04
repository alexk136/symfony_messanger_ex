<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/api/chats")
 */
class ChatController extends AbstractController
{
    /**
     * POST /api/chats
     * 
     * @Route("", methods={"POST"})
     */
    public function create(Request $request)
    {
        // Logic to create a new chat
    }

    /**
     * GET /api/chats
     * 
     * @Route("", methods={"GET"})
     */
    public function list(Request $request)
    {
        // Logic to show a specific chat by ID
    }

    /**
     * POST /api/v1/chats/{id}/close
     * 
     * @Route("/{id}/close", methods={"POST"})
     */
    public function close(int $id, Request $request) {

    }

    /**
     * POST /api/chats/{id}/messages
     * 
     * @Route("/{id}/messages", methods={"POST"})
     */
    public function sendMessage(int $id, Request $request)
    {
        // Logic to show a specific chat by ID
    }
}