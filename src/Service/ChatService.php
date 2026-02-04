<?php

namespace App\Service;

use App\Entity\Chat;
use App\Entity\Message;
use Doctrine\DBAL\LockMode;
use Doctrine\ORM\EntityManagerInterface;

class ChatService
{
    private $em;

    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
    }

    public function createChat(int $userId){
        $this->em->beginTransaction();

        try {
            $chat = new Chat();
            $chat->setUserId($userId);
            $this->em->persist($chat);
            $this->em->flush();
            $this->em->commit();
        } catch (\Exception $e) {
            $this->em->rollback();
            throw $e;
        }
    }

    public function closeChat(int $id, int $userId)
    {
        $this->em->beginTransaction();

        try {
            $chat = $this->em->getRepository(Chat::class)->findOneBy([
                'id' => $id,
                'userId' => $userId
            ]);

            if (!$chat) {
                throw new \Exception('Chat not found');
            }

            $this->em->lock($chat, LockMode::PESSIMISTIC_WRITE);
            $this->em->refresh($chat);

            if ($chat->isClosed()) {
                throw new \RuntimeException('Chat already closed');
            }

            $chat->setClosedAt(new \DateTime());
            $this->em->persist($chat);
            $this->em->flush();
            $this->em->commit();
        } catch (\Exception $e) {
            $this->em->rollback();
            throw $e;
        }
    }

    public function sendMessage(int $chatId, int $userId, string $messageText)
    {
        $this->em->beginTransaction();

        try {
            $chat = $this->em->getRepository(Chat::class)->findOneBy([
                'id' => $chatId,
                'userId' => $userId
            ]);

            if (!$chat) {
                throw new \Exception('Chat not found');
            }

            $this->em->lock($chat, LockMode::PESSIMISTIC_WRITE);
            $this->em->refresh($chat);

            $message = new Message();
            $message->setChat($chat);
            $message->setUserId($userId);
            $message->setText($messageText);
            $message->setDirection(Message::DIRECTION_OUT);

            $this->em->persist($message);
            $this->em->flush();
            $this->em->commit();
        } catch (\Exception $e) {
            $this->em->rollback();
            throw $e;
        }
    }

    public function listChats(int $limit)
    {
        return $this->em->getRepository(Chat::class)
            ->findBy([], ['createdAt' => 'DESC'], $limit);
    }
}