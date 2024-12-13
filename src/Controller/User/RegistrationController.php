<?php

namespace App\Controller\User;

use App\Entity\User;
use App\Repository\ClientRepository;
use App\Request\RegisterRequest;
use App\Service\Factory\UserFactory;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;

class RegistrationController extends AbstractController
{
    #[Route('/api/user', name: 'api_user_add', methods: ['POST'])]
    public function registerUser(
        UserPasswordHasherInterface $passwordHasher,
        RegisterRequest $request,
        ClientRepository $clientRepository,
        EntityManagerInterface $em,
        UserFactory $userFactory
    ): Response {
        try {
            $request->isValid();
        } catch (Exception $error) {
            return new JsonResponse('Le format de la requête n\'est pas valide : '. $error->getMessage());
        }

        try {
            $user = $userFactory->createUser($request);
        } catch (Exception $error) {
            return new JsonResponse('Une erreur est survenue lors de la création de l\'utilisateur '. $error->getMessage());
        }

        $em->persist($user);
        $em->flush();

        return new JsonResponse($user);
    }
}