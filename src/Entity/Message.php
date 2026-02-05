<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Table(
 *     name="messages",
 *     indexes={
 *         @ORM\Index(name="idx_message_chat_id", columns={"chat_id", "id"}),
 *         @ORM\Index(name="idx_message_status", columns={"status"}),
 *         @ORM\Index(name="idx_message_created_at", columns={"created_at"})
 *     },
 *     uniqueConstraints={
 *         @ORM\UniqueConstraint(name="uniq_client_msg_user", columns={"client_msg_id", "user_id"})
 *     }
 * )
 * @ORM\Entity(repositoryClass="App\Repository\MessageRepository")
 */
class Message
{
    const DIRECTION_IN = 'in';
    const DIRECTION_OUT = 'out';

    const TYPE_TEXT = 'text';
    const TYPE_IMAGE = 'image';

    const STATUS_NEW = 'new';
    const STATUS_READ = 'read';


    /**
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity="Chat", inversedBy="messages")
     * @ORM\JoinColumn(name="chat_id", referencedColumnName="id", onDelete="CASCADE", nullable=false)
     */
    private $chat;

    /**
     * @ORM\Column(type="text")
     */
    private $text;

    /**
     * @ORM\Column(type="string", length=10)
     */
    private $direction;

    /**
     * @ORM\Column(type="string", length=20)
     */
    private $type = self::TYPE_TEXT;

    /**
     * @ORM\Column(type="string", length=20)
     */
    private $status = self::STATUS_NEW;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $userId;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $clientMsgId;

    /**
     * @ORM\Column(type="datetime")
     */
    private $createdAt;

    public function __construct()
    {
        $this->createdAt = new \DateTime();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getChat(): ?Chat
    {
        return $this->chat;
    }

    public function setChat(Chat $chat): self
    {
        $this->chat = $chat;

        return $this;
    }

    public function getText(): ?string
    {
        return $this->text;
    }

    public function setText(string $text): self
    {
        $this->text = $text;

        return $this;
    }

    public function getDirection(): ?string
    {
        return $this->direction;
    }

    public function setDirection(string $direction): self
    {
        $this->direction = $direction;

        return $this;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(string $type): self
    {
        $this->type = $type;

        return $this;
    }

    public function getUserId(): ?int
    {
        return $this->userId;
    }

    public function setUserId(?int $userId): self
    {
        $this->userId = $userId;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeInterface
    {
        return $this->createdAt;
    }

    public function getClientMsgId(): ?string
    {
        return $this->clientMsgId;
    }

    public function setClientMsgId(?string $clientMsgId): self
    {
        $this->clientMsgId = $clientMsgId;

        return $this;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus(string $status): self
    {
        $this->status = $status;

        return $this;
    }
}