<?php

namespace App\Controller\User;

use App\Entity\Product;
use App\Entity\User;
use App\Repository\ProductRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Contracts\Cache\TagAwareCacheInterface;

class UserController extends AbstractController
{
    //TODO
    #[Route('/api/users', name: 'app_users', methods: Request::METHOD_GET) ]
    public function index(
        UserRepository $userRepository,
        SerializerInterface $serializer,
        Request $request,
        TagAwareCacheInterface $cachePool
    ): JsonResponse {
        $page = $request->get('page', 1);
        $limit = $request->get('limit', 3);

        $idCache = "getAllUsers-" . $page . "-" . $limit;

        $jsonUserList = $cachePool->get($idCache, function (ItemInterface $item) use (
            $userRepository,
            $page,
            $limit,
            $serializer
        ) {
            $item->tag("UsersCache");
            $item->expiresAfter(3600);
            $userList = $userRepository->findAllWithPagination($page, $limit);
            return $serializer->serialize($userList, 'json', ['groups' => 'getUser']);
        });


        return new JsonResponse($jsonUserList, Response::HTTP_OK, [], true);
    }

    #[Route('/api/users/{id}', name: 'detail_user', methods: Request::METHOD_GET)]
    public function getDetailUser(User $user, SerializerInterface $serializer): JsonResponse
    {
        $jsonUser = $serializer->serialize($user, 'json', ['groups' => 'getUser']);
        return new JsonResponse($jsonUser, Response::HTTP_OK, ['accept' => 'json'], true);
    }

    #[Route('/api/users/remove/{id}', name: 'remove_user', methods: Request::METHOD_GET)]
    #[IsGranted('ROLE_ADMIN')]
    public function removeUser(User $user, EntityManagerInterface $em, TagAwareCacheInterface $cachePool): JsonResponse
    {
        $cachePool->invalidateTags(["UsersCache"]);

        $em->remove($user);
        $em->flush();

        return $this->json(['message' => 'User removed.'], Response::HTTP_OK);
    }

    #[Route('/api/users/add', name: 'add_user', methods: Request::METHOD_POST)]
    #[IsGranted('ROLE_ADMIN')]
    public function addUser(Request $request, EntityManagerInterface $em, TagAwareCacheInterface $cachePool): JsonResponse
    {
        $cachePool->invalidateTags(["UsersCache"]);

        // $em->remove($user);
        // $em->flush();

        return $this->json(['message' => 'User added with success.'], Response::HTTP_OK);
    }
}
