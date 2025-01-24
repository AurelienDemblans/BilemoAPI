<?php

namespace App\Controller\Product;

use App\Controller\BilemoController;
use App\Entity\Product;
use App\Repository\ProductRepository;
use Exception;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\SerializerInterface;

class ProductController extends BilemoController
{
    #[Route('/api/products', name: 'app_products', methods: Request::METHOD_GET) ]
    public function index(
        ProductRepository $productRepository,
        SerializerInterface $serializer,
        Request $request,
    ): JsonResponse {
        $page = $request->get('page', 1);
        $limit = $request->get('limit', 3);
        //*check queryParams are valid
        $queryParams = ['page' => $page, 'limit' => $limit];
        foreach ($queryParams as $queryParamName => $value) {
            $this->checkQueryParameter($queryParamName, $value);
        }

        $productList =  $productRepository->findAllWithPagination($page, $limit);
        if ($productList === null || empty($productList)) {

            $numberOfProduct = count($productRepository->findAll());
            $maximumPageNumberAvailable = intval(ceil($numberOfProduct / $limit));

            throw new Exception('This page contains no products. Maximum number of page with '.$limit.' product(s) by page is : '.$maximumPageNumberAvailable, Response::HTTP_NOT_FOUND);
        }

        $jsonProductList = $serializer->serialize($productList, 'json');

        return new JsonResponse($jsonProductList, Response::HTTP_OK, [], true);
    }

    #[Route('/api/products/{id<\d+>}', name: 'detail_product', methods: ['GET'])]
    public function getDetailProduct(?Product $product): JsonResponse
    {
        if ($product === null) {
            throw new Exception('Product not found.', Response::HTTP_NOT_FOUND);
        }

        return $this->json($product, Response::HTTP_OK, ['accept' => 'json']);
    }
}
