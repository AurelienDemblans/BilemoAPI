<?php

namespace App\Service\Factory;

use App\Entity\User;
use App\Request\AddUserRequest;
use DateTimeImmutable;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class UserFactory
{
    public function __construct(
        private readonly UserPasswordHasherInterface $passwordHasher,
        private readonly ParameterBagInterface $parameterBagInterface,
    ) {
    }

    /**
     * createUser
     *
     * @param  AddUserRequest $request
     *
     * @return User
     */
    public function createUser(AddUserRequest $request): User
    {
        $user = new User();

        $user->setClient($request->getClient());
        $user->setEmail($request->getEmail());
        $user->setFirstname($request->getFirstName());
        $user->setLastname($request->getLastName());
        $user->setRoles(['ROLE_USER']);

        $hashedPassword = $this->passwordHasher->hashPassword(
            $user,
            $request->getPassword()
        );

        $rolesString = $this->parameterBagInterface->get('roles');


        $user->setPassword($hashedPassword);
        $user->setCreatedAt(new DateTimeImmutable());

        return $user;
    }
}
