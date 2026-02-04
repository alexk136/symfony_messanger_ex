<?php

namespace App\Service;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;

class UserService
{
    private $em;

    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
    }

    public function login(array $data): User
    {
        $user = $this->em->getRepository(User::class)->findOneBy(['email' => $data['email']]);
        if (!$user) {
            $this->em->beginTransaction();
            try {
                $user = new User();
                $user->setEmail($data['email']);
                $user->setCreatedAt(new \DateTime());

                $this->em->persist($user);
                $this->em->flush();
                $this->em->commit();
            } catch (\Exception $e) {
                $this->em->rollback();
                throw $e;
            }
        }
        return $user;
    }
}