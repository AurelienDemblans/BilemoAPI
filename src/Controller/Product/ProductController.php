<?php

namespace App\Controller\Product;

use App\Attribute\Api\Interface\ProductInterface;
use Exception;
use App\Entity\Product;
use OpenApi\Attributes\Tag;
use App\Controller\BilemoController;
use App\Repository\ProductRepository;
use Nelmio\ApiDocBundle\Annotation\Model;
use OpenApi\Attributes\Items;
use OpenApi\Attributes\JsonContent;
use OpenApi\Attributes\Property;
use OpenApi\Attributes\QueryParameter;
use OpenApi\Attributes\Response as AttributesResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Serializer\SerializerInterface;

class ProductController extends BilemoController
{
    #[Route('/api/products', name: 'app_products', methods: Request::METHOD_GET) ]
    #[Tag('Products'),
        AttributesResponse(
            response: Response::HTTP_OK,
            description: 'return products paginated (3 per page)',
            content:new JsonContent(
                type: 'array',
                items: new Items(
                    ref: new Model(type: ProductInterface::class)
                )
            )
        ),
        AttributesResponse(
            response: Response::HTTP_FORBIDDEN,
            description: 'when user is not connected',
            content:new JsonContent(
                properties: [
                    new Property(property: 'status', type: 'integer', example:Response::HTTP_FORBIDDEN, nullable:false),
                    new Property(property: 'message', example:'Access Denied.')
                ]
            )
        ),
        QueryParameter(
            name:'page',
            description:'page you want to get, default = 1'
        ),
        QueryParameter(
            name:'limit',
            description:'number of product by page, default = 3'
        )
    ]
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
    #[Tag('Products'),
        AttributesResponse(
            response: Response::HTTP_OK,
            description: 'return one specific product',
            content:new JsonContent(
                ref: new Model(type: ProductInterface::class)
            )
        ),
        AttributesResponse(
            response: Response::HTTP_FORBIDDEN,
            description: 'when user is not connected',
            content:new JsonContent(
                properties: [
                    new Property(property: 'status', type: 'integer', example:Response::HTTP_FORBIDDEN, nullable:false),
                    new Property(property: 'message', example:'Access Denied.')
                ]
            )
        ),
        AttributesResponse(
            response: Response::HTTP_NOT_FOUND,
            description: 'When products is not found.',
            content:new JsonContent(
                properties: [
                    new Property(property: 'status', type: 'integer', example:Response::HTTP_NOT_FOUND, nullable:false),
                    new Property(property: 'message', example:'Product not found.')
                ]
            )
        ),
    ]
    public function getDetailProduct(?Product $product): JsonResponse
    {
        if ($product === null) {
            throw new Exception('Product not found.', Response::HTTP_NOT_FOUND);
        }

        return $this->json($product, Response::HTTP_OK, ['accept' => 'json']);
    }
}
