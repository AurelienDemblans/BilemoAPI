<?php

namespace App\Controller\User;

use App\Attribute\Api\Interface\AddUserBodyInterface;
use App\Attribute\Api\Interface\RemoveUserBodyInterface;
use App\Attribute\Api\Interface\UserInterface;
use App\Controller\BilemoController;
use Exception;
use App\Entity\User;
use App\Repository\UserRepository;
use App\Request\RemoveUserRequest;
use App\Repository\ClientRepository;
use App\Request\AddUserRequest;
use App\Service\Factory\UserFactory;
use Doctrine\ORM\EntityManagerInterface;
use JMS\Serializer\Context;
use JMS\Serializer\SerializationContext;
use JMS\Serializer\SerializerInterface;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes\Items;
use OpenApi\Attributes\JsonContent;
use OpenApi\Attributes\Property;
use OpenApi\Attributes\QueryParameter;
use OpenApi\Attributes\RequestBody;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Contracts\Cache\TagAwareCacheInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use OpenApi\Attributes\Response as AttributesResponse;
use OpenApi\Attributes\Tag;

class UserController extends BilemoController
{
    private ?Context $context = null;

    public function __construct(
        private readonly ClientRepository $clientRepository,
        private readonly SerializerInterface $serializer,
        private readonly TagAwareCacheInterface $cachePool,
        private readonly UserPasswordHasherInterface $passwordHasher,
        private readonly EntityManagerInterface $em,
    ) {
        $this->context = SerializationContext::create()->setGroups(['getUser']);
    }

    #[Route('/api/users', name: 'client_users', methods: Request::METHOD_GET) ]
    #[Tag('Users'),
        AttributesResponse(
            response: Response::HTTP_OK,
            description: 'Return all user of a specific client (3 per page)',
            content:new JsonContent(
                type: 'array',
                items: new Items(
                    ref: new Model(type: UserInterface::class)
                )
            )
        ),
        AttributesResponse(
            response: Response::HTTP_FORBIDDEN,
            description: 'when user is not allowed',
            content:new JsonContent(
                properties: [
                    new Property(property: 'status', type: 'integer', example:Response::HTTP_FORBIDDEN, nullable:false),
                    new Property(property: 'message', example:'You are not allowed to perform this request')
                ]
            )
        ),
        AttributesResponse(
            response: Response::HTTP_NOT_FOUND,
            description: "an HTTP 400 is throw when : 
            <ul>
                <li> no user is found </li>
                <li> No user available to display according to your role </li>
                <li> if the pagination doest not suit the number of users </li>
            </ul>",
            content:new JsonContent(
                properties: [
                    new Property(property: 'status', type: 'integer', example:Response::HTTP_NOT_FOUND, nullable:false),
                    new Property(property: 'message', example:'Users not found.')
                ]
            )
        ),
        QueryParameter(
            name:'page',
            description:'page you want to get, default = 1'
        ),
        QueryParameter(
            name:'limit',
            description:'number of users by page, default = 3'
        ),
        QueryParameter(
            name:'clientId',
            description:'for SUPER_ADMIN only'
        )
    ]
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
        if ((int) $user->getClient()->getId() !== (int) $clientId && !$this->isGranted('ROLE_SUPER_ADMIN')) {
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
                throw new Exception('No user available to display according to your role', Response::HTTP_NOT_FOUND);
            }

            $userList = $userRepository->findAllWithPagination($page, $limit, $clientId);
            $cleanedUserList = $this->cleanUserListByRole($userList);

            if ($cleanedUserList === null || empty($cleanedUserList)) {
                $numberOfUser = count($cleanedTotalNumber);
                $maximumUserNumberAvailable = intval(ceil($numberOfUser / $limit));

                throw new Exception('This page contains no users. Maximum number of page with '.$limit.' user(s) by page is : '.$maximumUserNumberAvailable, Response::HTTP_NOT_FOUND);
            }

            return $serializer->serialize($cleanedUserList, 'json', $this->context);
        });

        return new JsonResponse($jsonUserList, Response::HTTP_OK, [], true);
    }

    #[Route('/api/users/{userId<\d+>}', name: 'user_detail', methods: Request::METHOD_GET)]
    #[Tag('Users'),
        AttributesResponse(
            response: Response::HTTP_OK,
            description: 'Return one user details',
            content:new JsonContent(
                ref: new Model(type: UserInterface::class)
            )
        ),
        AttributesResponse(
            response: Response::HTTP_FORBIDDEN,
            description: 'when user is not allowed',
            content:new JsonContent(
                properties: [
                    new Property(property: 'status', type: 'integer', example:Response::HTTP_FORBIDDEN, nullable:false),
                    new Property(property: 'message', example:'You are not allowed to perform this request')
                ]
            )
        ),
        AttributesResponse(
            response: Response::HTTP_NOT_FOUND,
            description: "when user is not found",
            content:new JsonContent(
                properties: [
                    new Property(property: 'status', type: 'integer', example:Response::HTTP_NOT_FOUND, nullable:false),
                    new Property(property: 'message', example:'Users not found.')
                ]
            )
        ),
    ]
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

            return $serializer->serialize($userToReturn, 'json', $this->context);
        });

        return new JsonResponse($jsonUser, Response::HTTP_OK, ['accept' => 'json'], true);
    }

    #[Route('/api/users', name: 'remove_user', methods: Request::METHOD_DELETE)]
    #[IsGranted('ROLE_ADMIN')]
    #[Tag('Users'),
        AttributesResponse(
            response: Response::HTTP_NO_CONTENT,
            description: 'Remove one user',
        ),
        AttributesResponse(
            response: Response::HTTP_FORBIDDEN,
            description: 'when user is not allowed',
            content:new JsonContent(
                properties: [
                    new Property(property: 'status', type: 'integer', example:Response::HTTP_FORBIDDEN, nullable:false),
                    new Property(property: 'message', example:'You are not allowed to perform this request')
                ]
            )
        ),
        AttributesResponse(
            response: Response::HTTP_NOT_FOUND,
            description: "when user is not found",
            content:new JsonContent(
                properties: [
                    new Property(property: 'status', type: 'integer', example:Response::HTTP_NOT_FOUND, nullable:false),
                    new Property(property: 'message', example:'Users not found.')
                ]
            )
        ),
        AttributesResponse(
            response: Response::HTTP_BAD_REQUEST,
            description: "an HTTP 400 is throw when : 
            <ul>
                <li> user is missing in the body </li>
                <li> if you try to remove yourself </li>
                <li> invalid user</li>
            </ul>",
            content:new JsonContent(
                properties: [
                    new Property(property: 'status', type: 'integer', example:Response::HTTP_BAD_REQUEST, nullable:false),
                    new Property(property: 'message', example:'Users not found.')
                ]
            )
        ),
        RequestBody(description: 'user to remove', content:new JsonContent(
            ref: new Model(type: RemoveUserBodyInterface::class)
        ))
    ]
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
    #[Tag('Users'),
        AttributesResponse(
            response: Response::HTTP_CREATED,
            description: 'Create a new user',
            content:new JsonContent(
                ref: new Model(type: UserInterface::class)
            )
        ),
        AttributesResponse(
            response: Response::HTTP_FORBIDDEN,
            description: 'when user is not allowed',
            content:new JsonContent(
                properties: [
                    new Property(property: 'status', type: 'integer', example:Response::HTTP_FORBIDDEN, nullable:false),
                    new Property(property: 'message', example:'You are not allowed to perform this request')
                ]
            )
        ),
        AttributesResponse(
            response: Response::HTTP_NOT_FOUND,
            description: "when client is not found",
            content:new JsonContent(
                properties: [
                    new Property(property: 'status', type: 'integer', example:Response::HTTP_NOT_FOUND, nullable:false),
                    new Property(property: 'message', example:'Client not found.')
                ]
            )
        ),
        AttributesResponse(
            response: Response::HTTP_BAD_REQUEST,
            description: "an HTTP 400 is throw when : 
        <ul>
            <li> invalid or missing parameter in body </li>
            <li> email already used by another user </li>
        </ul>",
            content:new JsonContent(
                properties: [
                    new Property(property: 'status', type: 'integer', example:Response::HTTP_BAD_REQUEST, nullable:false),
                    new Property(property: 'message', example:'Email is missing in the body.')
                ]
            )
        ),
        RequestBody(description: 'user to add', content:new JsonContent(
            ref: new Model(type: AddUserBodyInterface::class)
        ))
    ]
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

        $jsonUser = $this->serializer->serialize($user, 'json', $this->context);

        return new JsonResponse($jsonUser, Response::HTTP_CREATED, [], true);
    }


}
