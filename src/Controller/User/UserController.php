<?php

namespace App\Controller\User;

use Exception;
use App\Entity\User;
use App\Repository\UserRepository;
use App\Request\RemoveUserRequest;
use App\Repository\ClientRepository;
use App\Request\AddUserRequest;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Contracts\Cache\TagAwareCacheInterface;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class UserController extends AbstractController
{
    public function __construct(
        private readonly ClientRepository $clientRepository,
        private readonly SerializerInterface $serializer,
        private readonly TagAwareCacheInterface $cachePool,
    ) {
    }

    #[Route('/api/users/{clientId<\d+>}', name: 'client_users', methods: Request::METHOD_GET) ]
    #[IsGranted('ROLE_ADMIN')]
    public function index(
        UserRepository $userRepository,
        SerializerInterface $serializer,
        int $clientId,
        Request $request,
    ): JsonResponse {
        /** @var User $user */
        $user = $this->getUser();
        $client = $this->clientRepository->find($clientId);

        if ($user->getClient() !== $client) {
            throw new Exception('You are not allowed to perform this request', Response::HTTP_FORBIDDEN);
        }

        $page = $request->get('page', 1);
        $limit = $request->get('limit', 3);

        $idCache = "getAllUsers-" . $page . "-" . $limit;

        //! renvoyer une erreur si userList est vide et preciser le nombre de page maximum
        //! selon la limit choisis par l'utilisateur
        $jsonUserList = $this->cachePool->get($idCache, function (ItemInterface $item) use (
            $userRepository,
            $page,
            $limit,
            $clientId,
            $serializer,
        ) {
            $item->tag("UsersCache");
            $item->expiresAfter(3600);
            $userList = $userRepository->findAllWithPagination($page, $limit, $clientId);
            return $serializer->serialize($userList, 'json', ['groups' => 'getUser']);
        });

        return new JsonResponse($jsonUserList, Response::HTTP_OK, [], true);
    }

    #[Route('/api/clients/{clientId<\d+>}/users/{userId<\d+>}', name: 'client_users_detail', methods: Request::METHOD_GET)]
    public function getDetailUser(
        UserRepository $userRepository,
        SerializerInterface $serializer,
        int $clientId,
        int $userId,
    ): JsonResponse {
        /** @var User $user */
        $user = $this->getUser();
        $client = $this->clientRepository->find($clientId);

        if ($user->getClient() !== $client) {
            throw new Exception('You are not allowed to perform this request', Response::HTTP_FORBIDDEN);
        }

        $idCache = "getDetailUsers-" . $clientId . "-" . $userId;

        //! renvoyer une erreur si luser nexiste pas
        $jsonUser = $this->cachePool->get($idCache, function (ItemInterface $item) use (
            $userRepository,
            $serializer,
            $clientId,
            $userId
        ) {
            $item->tag("UsersCache");
            $item->expiresAfter(3600);
            $userToReturn = $userRepository->findOneBy(['id' => $userId, 'client' => $clientId]);
            if ($userToReturn === null) {
                throw new Exception('User not found', Response::HTTP_NOT_FOUND);
            }
            return $serializer->serialize($userToReturn, 'json', ['groups' => 'getUser']);
        });

        return new JsonResponse($jsonUser, Response::HTTP_OK, ['accept' => 'json'], true);
    }

    #[Route('/api/users/remove', name: 'remove_user', methods: Request::METHOD_DELETE)]
    #[IsGranted('ROLE_ADMIN')]
    public function removeUser(
        RemoveUserRequest $request,
        EntityManagerInterface $em,
        TagAwareCacheInterface $cachePool
    ): JsonResponse {
        $cachePool->invalidateTags(["UsersCache"]);

        $request->isValid();
        $request->isAllowed();

        $em->remove($request->getUserToRemove());
        $em->flush();

        return new JsonResponse(['message' => 'User removed.'], Response::HTTP_OK);
    }

    #[Route('/api/users/add', name: 'add_user', methods: Request::METHOD_POST)]
    #[IsGranted('ROLE_ADMIN')]
    public function addUser(
        AddUserRequest $request,
        EntityManagerInterface $em,
        TagAwareCacheInterface $cachePool
    ): JsonResponse {
        $cachePool->invalidateTags(["UsersCache"]);

        $request->isValid();
        $request->isAllowed();

        $user = new User();

        $user->setClient($request->getClient());
        $user->setEmail($request->getEmail());
        $user->setFirstname($request->getFirstName());
        $user->setLastname($request->getLastName());
        $user->setRoles(['ROLE_USER']);
        //!hasher le password
        $user->setPassword($request->getPassword());
        $user->setCreatedAt(new DateTimeImmutable());

        $em->persist($user);
        $em->flush();

        return $this->json(['message' => 'User added with success.'], Response::HTTP_CREATED);
    }
}
