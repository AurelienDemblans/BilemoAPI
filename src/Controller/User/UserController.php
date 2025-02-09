<?php

namespace App\Controller\User;

use App\Controller\BilemoController;
use Exception;
use App\Entity\User;
use App\Repository\UserRepository;
use App\Request\RemoveUserRequest;
use App\Repository\ClientRepository;
use App\Request\AddUserRequest;
use App\Service\Factory\UserFactory;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Contracts\Cache\TagAwareCacheInterface;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class UserController extends BilemoController
{
    public function __construct(
        private readonly ClientRepository $clientRepository,
        private readonly SerializerInterface $serializer,
        private readonly TagAwareCacheInterface $cachePool,
        private readonly UserPasswordHasherInterface $passwordHasher,
        private readonly EntityManagerInterface $em,
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

        if ($user->getClient()->getId() !== $clientId && !$this->isGranted('ROLE_SUPER_ADMIN')) {
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

            $client = $this->clientRepository->find($clientId);
            if (!$client) {
                throw new Exception('Client not found', Response::HTTP_NOT_FOUND);
            }

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
        int $userId,
    ): JsonResponse {
        /** @var User $user */
        $user = $this->getUser();
        /** @var User $userToReturn */
        $userToReturn = $userRepository->find($userId);
        if ($userToReturn === null) {
            throw new Exception('User not found', Response::HTTP_NOT_FOUND);
        }

        if ($user->getClient() !== $userToReturn->getClient() && !$this->isGranted('ROLE_SUPER_ADMIN')) {
            throw new Exception('You are not allowed to perform this request', Response::HTTP_FORBIDDEN);
        }

        $idCache = "getDetailUsers-". $userId;

        $serializer = $this->serializer;
        $jsonUser = $this->cachePool->get($idCache, function (ItemInterface $item) use (
            $serializer,
            $userToReturn,
        ) {
            $item->tag("UsersCache");
            $item->expiresAfter(1);

            if (!$this->validateRoleAuthorization($userToReturn)) {
                throw new Exception('You are not allowed to perform this request.', Response::HTTP_FORBIDDEN);
            }

            return $serializer->serialize($userToReturn, 'json', ['groups' => 'getUser']);
        });

        return new JsonResponse($jsonUser, Response::HTTP_OK, ['accept' => 'json'], true);
    }

    #[Route('/api/users', name: 'remove_user', methods: Request::METHOD_DELETE)]
    #[IsGranted('ROLE_ADMIN')]
    public function removeUser(
        RemoveUserRequest $request,
    ): JsonResponse {
        $this->cachePool->invalidateTags(["UsersCache"]);

        $request->isValid();
        $request->isAllowed();

        $this->em->remove($request->getUserToRemove());
        $this->em->flush();

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }

    #[Route('/api/users', name: 'add_user', methods: Request::METHOD_POST)]
    #[IsGranted('ROLE_ADMIN')]
    public function addUser(
        AddUserRequest $request,
        UserFactory $userFactory,
        ValidatorInterface $validator,
    ): JsonResponse {
        $this->cachePool->invalidateTags(["UsersCache"]);

        $request->isValid();
        $request->isAllowed();

        $user = $userFactory->createUser($request);

        $errors = $validator->validate($user);

        if ($errors->count() > 0) {
            return new JsonResponse($this->serializer->serialize($errors, 'json'), JsonResponse::HTTP_BAD_REQUEST, [], true);
        }

        $this->em->persist($user);
        $this->em->flush();

        $jsonUser = $this->serializer->serialize($user, 'json', ['groups' => 'getUser']);

        return new JsonResponse($jsonUser, Response::HTTP_CREATED, [], true);
    }


}
