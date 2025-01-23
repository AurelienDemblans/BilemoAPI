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
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class UserController extends AbstractController
{
    public function __construct(
        private readonly ClientRepository $clientRepository,
        private readonly SerializerInterface $serializer,
        private readonly TagAwareCacheInterface $cachePool,
        private readonly UserPasswordHasherInterface $passwordHasher,
    ) {
    }

    #[Route('/api/users', name: 'client_users', methods: Request::METHOD_GET) ]
    public function index(
        UserRepository $userRepository,
        SerializerInterface $serializer,
        Request $request,
    ): JsonResponse {
        //* init variables
        $page = $request->get('page', 1);
        $limit = $request->get('limit', 3);
        /** @var User $user */
        $user = $this->getUser();
        $clientId = $request->get('client', $user->getClient()->getId());

        //*check queryParams are valid
        $queryParams = ['page' => $page, 'limit' => $limit, 'client' => $clientId];
        foreach ($queryParams as $queryParamName => $value) {
            $this->checkQueryParameter($queryParamName, $value);
        }

        $client = $this->clientRepository->find($clientId);
        if (!$client) {
            throw new Exception('Client not found', Response::HTTP_NOT_FOUND);
        }

        if ($user->getClient() !== $client && !$this->isGranted('ROLE_SUPER_ADMIN')) {
            throw new Exception('You are not allowed to perform this request', Response::HTTP_FORBIDDEN);
        }

        $idCache = "getAllUsers-" . $page . "-" . $limit. "-".$clientId;

        $jsonUserList = $this->cachePool->get($idCache, function (ItemInterface $item) use (
            $userRepository,
            $page,
            $limit,
            $clientId,
            $serializer,
        ) {
            $item->tag("UsersCache");
            $item->expiresAfter(1);

            $totalUser = $userRepository->findBy(['client' => $clientId]);
            $cleanedTotalNumber = $this->cleanUserListByRole($totalUser);
            if (null === $cleanedTotalNumber || empty($cleanedTotalNumber)) {
                throw new Exception('No user available for this client', Response::HTTP_NOT_FOUND);
            }

            $userList = $userRepository->findAllWithPagination($page, $limit, $clientId);
            $cleanedUserList = $this->cleanUserListByRole($userList);

            if ($cleanedUserList === null || empty($cleanedUserList)) {
                $numberOfUser = count($cleanedTotalNumber);
                $maximumUserNumberAvailable = intval(ceil($numberOfUser / $limit));

                throw new Exception('This page contains no users. Maximum number of page with '.$limit.' user(s) by page is : '.$maximumUserNumberAvailable, Response::HTTP_NOT_FOUND);
            }

            return $serializer->serialize($cleanedUserList, 'json', ['groups' => 'getUser']);
        });

        return new JsonResponse($jsonUserList, Response::HTTP_OK, [], true);
    }

    #[Route('/api/users/{userId<\d+>}', name: 'client_users_detail', methods: Request::METHOD_GET)]
    public function getDetailUser(
        UserRepository $userRepository,
        SerializerInterface $serializer,
        int $userId,
    ): JsonResponse {
        /** @var User $user */
        $user = $this->getUser();
        $client = $user->getClient();

        if ($user->getClient() !== $client && !$this->isGranted('ROLE_SUPER_ADMIN')) {
            throw new Exception('You are not allowed to perform this request', Response::HTTP_FORBIDDEN);
        }

        $idCache = "getDetailUsers-". $userId;

        $jsonUser = $this->cachePool->get($idCache, function (ItemInterface $item) use (
            $userRepository,
            $serializer,
            $userId,
        ) {
            $item->tag("UsersCache");
            $item->expiresAfter(1);
            /** @var User $userToReturn */
            $userToReturn = $userRepository->find($userId);
            if ($userToReturn === null) {
                throw new Exception('User not found', Response::HTTP_NOT_FOUND);
            }

            if (!$this->validateRoleAuthorization($userToReturn)) {
                throw new Exception('You are not allowed to perform this request.', Response::HTTP_FORBIDDEN);
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

        return new JsonResponse(['message' => 'User removed.'], Response::HTTP_NO_CONTENT);
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

        $hashedPassword = $this->passwordHasher->hashPassword(
            $user,
            $request->getPassword()
        );

        $user->setPassword($hashedPassword);
        $user->setCreatedAt(new DateTimeImmutable());

        $em->persist($user);
        $em->flush();

        return $this->json(['message' => 'User added with success.'], Response::HTTP_CREATED);
    }

    /**
     * cleanUserListByRole
     *
     * @param  User[] $userList
     * @return array
     */
    private function cleanUserListByRole(array $userList): array
    {
        return array_filter($userList, function (User $user) {
            return $this->validateRoleAuthorization($user);
        });
    }

    private function checkQueryParameter(int|string $queryParamName, int|string $value)
    {
        if (!is_numeric($value) || $value <= 0 || !ctype_digit((string)$value)) {
            throw new Exception($queryParamName.' parameter must be positive integer only, '.$queryParamName.' was : '.$value, Response::HTTP_BAD_REQUEST);
        }
    }

    private function validateRoleAuthorization(User $user): bool
    {
        foreach ($user->getRoles() as $role) {
            if (!$this->isGranted($role)) {
                return false;
            }
        }
        return true;
    }
}
