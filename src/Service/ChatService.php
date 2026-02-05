<?php

namespace App\Service;

use App\Entity\Chat;
use App\Entity\User;
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

    public function createChat(int $userId): Chat
    {
        $this->em->beginTransaction();

        try {
            $chat = new Chat();
            $chat->setUserId($userId);
            $chat->setOperatorId(null);
            $this->em->persist($chat);
            $this->em->flush();
            $this->em->commit();

            return $chat;
        } catch (\Exception $e) {
            $this->em->rollback();
            throw $e;
        }
    }

    public function closeChat(int $id, int $userId)
    {
        $this->em->beginTransaction();

        try {
            $chat = $this->em->getRepository(Chat::class)->find($id);
            $user = $this->em->getRepository(User::class)->find($userId);

            if (!$chat || !$user) {
                throw new \Exception('Chat or User not found');
            }

            if ($user->getRole() !== User::ROLE_OPERATOR || $chat->getOperatorId() !== $userId) {
                throw new \Exception('Only assigned operators can close chats');
            }

            $this->em->lock($chat, LockMode::PESSIMISTIC_WRITE);
            $this->em->refresh($chat);

            if ($chat->isClosed()) {
                return;
            }

            $chat->setStatus(Chat::STATUS_CLOSED);
            $chat->setClosedAt(new \DateTime());
            $chat->setClosedByUserId($userId);
            $this->em->persist($chat);
            $this->em->flush();
            $this->em->commit();
        } catch (\Exception $e) {
            $this->em->rollback();
            throw $e;
        }
    }

    public function sendMessage(int $chatId, int $userId, string $messageText, string $clientMsgId)
    {
        $this->em->beginTransaction();

        try {
            $chat = $this->em->getRepository(Chat::class)->find($chatId);
            $user = $this->em->getRepository(User::class)->find($userId);

            if (!$chat || !$user) {
                throw new \Exception('Chat or User not found');
            }

            if ($user->getRole() === User::ROLE_CLIENT && $chat->getUserId() !== $userId) {
                throw new \Exception('Clients can only send messages to their own chats');
            }
            if ($user->getRole() === User::ROLE_OPERATOR && $chat->getOperatorId() !== $userId) {
                throw new \Exception('Operators can only send messages to assigned chats');
            }

            if ($chat->isClosed()) {
                throw new \Exception('Cannot send message to closed chat');
            }

            $this->em->lock($chat, LockMode::PESSIMISTIC_WRITE);
            $this->em->refresh($chat);

            if ($clientMsgId) {
                $existingMessage = $this->em->getRepository(Message::class)->findOneBy([
                    'chat' => $chat,
                    'clientMsgId' => $clientMsgId
                ]);

                if ($existingMessage) {
                    $this->em->rollback();
                    return $existingMessage; 
                }
            }

            $message = new Message();
            $message->setChat($chat);
            $message->setUserId($userId);
            $message->setText($messageText);
            $message->setDirection($user->getRole() === User::ROLE_CLIENT ? Message::DIRECTION_IN : Message::DIRECTION_OUT);
            $message->setClientMsgId($clientMsgId);

            $this->em->persist($message);

            $chat->setLastMessageAt(new \DateTime());

            $this->em->persist($chat);
            $this->em->flush();
            $this->em->commit();

            return $message;
        } catch (\Exception $e) {
            $this->em->rollback();
            throw $e;
        }
    }

    public function listChats(int $userId, int $limit)
    {
        $user = $this->em->getRepository(User::class)->find($userId);
        if (!$user) {
            return [];
        }

        if ($user->getRole() === User::ROLE_OPERATOR) {
            $qb = $this->em->createQueryBuilder()
                ->select('c')
                ->from(Chat::class, 'c')
                ->where('(c.operatorId = :operatorId OR c.operatorId IS NULL)')
                ->setParameter('operatorId', $userId)
                ->orderBy('c.lastMessageAt', 'DESC')
                ->setMaxResults($limit);

            return $qb->getQuery()->getResult();
        } else {
            return $this->em->getRepository(Chat::class)
                ->findBy(['status' => Chat::STATUS_OPEN, 'userId' => $userId], ['lastMessageAt' => 'DESC'], $limit);
        }
    }

    public function getChatById(int $id)
    {
        $chat = $this->em->getRepository(Chat::class)->find($id);

        if (!$chat) {
            throw new \Exception('Chat not found');
        }
        return $chat;
    }

    public function getMessages(int $chatId, int $userId, int $limit = 50, ?int $beforeId = null, ?int $afterId = null)
    {
        $user = $this->em->getRepository(User::class)->find($userId);

        if ($user === null) {
            throw new \Exception('User not found');
        }

        if ($user->getRole() === User::ROLE_CLIENT) {
            $chat = $this->em->getRepository(Chat::class)->findOneBy(['id' => $chatId, 'userId' => $userId]);
        } else {
            $chat = $this->em->getRepository(Chat::class)->find($chatId);
        }

        if (!$chat) {
            throw new \Exception('Chat not found or access denied');
        }

        $qb = $this->em->getRepository(Message::class)->createQueryBuilder('m');
        $qb->where('m.chat = :chatId')
           ->setParameter('chatId', $chat->getId())
           ->orderBy('m.id', 'ASC')
           ->setMaxResults($limit);

        if ($beforeId) {
            $qb->andWhere('m.id < :beforeId')
               ->setParameter('beforeId', $beforeId);
        }

        if ($afterId) {
            $qb->andWhere('m.id > :afterId')
               ->setParameter('afterId', $afterId);
        }

        return $qb->getQuery()->getResult();
    }

    public function assignOperator(int $chatId, int $operatorId)
    {
        $this->em->beginTransaction();

        try {
            $chat = $this->em->getRepository(Chat::class)->find($chatId);
            $operator = $this->em->getRepository(User::class)->find($operatorId);

            if (!$chat || !$operator) {
                throw new \Exception('Chat or Operator not found');
            }

            if ($operator->getRole() !== User::ROLE_OPERATOR) {
                throw new \Exception('User is not an operator');
            }

            $this->em->lock($chat, LockMode::PESSIMISTIC_WRITE);
            $this->em->refresh($chat);

            if ($chat->isClosed()) {
                throw new \Exception('Cannot assign operator to closed chat');
            }

            if ($chat->getOperatorId() !== null) {
                throw new \Exception('Chat already has an assigned operator');
            }

            $chat->setOperatorId($operatorId);
            $this->em->persist($chat);
            $this->em->flush();
            $this->em->commit();
        } catch (\Exception $e) {
            $this->em->rollback();
            throw $e;
        }
    }
}