<?php

namespace App\Controller;

use App\Entity\Product;
use App\Repository\ProductRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Contracts\Cache\TagAwareCacheInterface;

class ProductController extends AbstractController
{
    //TODO
    #[Route('/api/products', name: 'app_products', methods: Request::METHOD_GET) ]
    public function index(
        ProductRepository $productRepository,
        SerializerInterface $serializer,
        Request $request,
        TagAwareCacheInterface $cachePool
    ): JsonResponse {
        $page = $request->get('page', 1);
        $limit = $request->get('limit', 3);

        //! renvoyer une erreur si productlist est vide et preciser le nombre de page maximum selon la limit choisis par l'utilisateur
        $productList =  $productRepository->findAllWithPagination($page, $limit);

        $jsonProductList = $serializer->serialize($productList, 'json');

        return new JsonResponse($jsonProductList, Response::HTTP_OK, [], true);
    }

    #[Route('/api/products/{id}', name: 'detail_product', methods: ['GET'])]
    public function getDetailProduct(Product $product): JsonResponse
    {
        //! créer un event listener dans le cas ou l'ID de product fournis n'existe pas
        return $this->json($product, Response::HTTP_OK, ['accept' => 'json']);
    }
}
