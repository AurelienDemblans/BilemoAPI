<?php

namespace App\Service\Factory;

use App\Entity\User;
use App\Repository\ClientRepository;
use App\Repository\UserRepository;
use App\Request\RegisterRequest;
use DateTimeImmutable;
use Exception;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class UserFactory
{
    public function __construct(
        private readonly UserPasswordHasherInterface $passwordHasher,
        private readonly ClientRepository $clientRepository,
        private readonly UserRepository $userRepository
    ) {
    }

    /**
     * createUser
     *
     * @param  RegisterRequest $request
     *
     * @return User
     */
    public function createUser(RegisterRequest $request): User
    {
        $user = new User();
        $role = ["ROLE_USER"];
        $client = $this->clientRepository->findOneByName(['name' => $request->getClient()]);
        $existingUser = $this->userRepository->findOneByEmail(['email' => $request->getEmail()]);

        if (!empty($existingUser)) {
            throw new Exception('Cette email est déjà utilisé');
        }
        if (!empty($client)) {
            throw new Exception('Ce client est inconnu');
        }

        $user->setLastname($request->getLastName());

        $user->setFirstname($request->getFirstName());
        $user->setClient($client);
        $user->setCreatedAt(new DateTimeImmutable());
        $user->setEmail($request->getEmail());
        $user->setRoles($role);

        $plaintextPassword = $request->getPassword();
        // hash the password (based on the security.yaml config for the $user class)
        $hashedPassword = $this->passwordHasher->hashPassword(
            $user,
            $plaintextPassword
        );
        $user->setPassword($hashedPassword);

        return $user;
    }
}
