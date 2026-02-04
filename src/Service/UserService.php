<?php

namespace App\Service;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class UserService
{
    private $em;
    private $passwordHasher;

    public function __construct(EntityManagerInterface $em, UserPasswordHasherInterface $passwordHasher)
    {
        $this->em = $em;
        $this->passwordHasher = $passwordHasher;
    }

    public function register(array $data): User
    {
        $this->em->beginTransaction();

        try {
            $existingUser = $this->em->getRepository(User::class)->findOneBy(['email' => $data['email']]);
            if ($existingUser) {
                throw new \Exception('User with this email already exists');
            }

            $user = new User();
            $user->setEmail($data['email']);
            $user->setUsername($data['username']);
            $user->setCreatedAt(new \DateTime());

            // $hashedPassword = $this->passwordHasher->hashPassword($user, $data['password']);
            // $user->setPassword($hashedPassword);

            $this->em->persist($user);
            $this->em->flush();
            $this->em->commit();

            return $user;
        } catch (\Exception $e) {
            $this->em->rollback();
            throw $e;
        }
    }
}