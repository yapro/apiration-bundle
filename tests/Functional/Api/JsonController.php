<?php

declare(strict_types=1);

namespace YaPro\ApiRationBundle\Tests\Functional\Api;

use YaPro\ApiRationBundle\Tests\Functional\Api\JsonConvertModel\KenModel;
use YaPro\ApiRationBundle\Tests\Functional\Api\JsonConvertModel\SimpleModel;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;

class JsonController extends AbstractController
{
    private SerializerInterface $serializer;

    /**
     * @Route("/api-json-test/{id<\d+>}")
     */
    public function discountOrder(int $id): JsonResponse
    {
        return $this->json(['id' => $id]);
    }

    /**
     * @Route("/api-json-test/simple-model")
     *
     * @param SimpleModel $model
     *
     * @return SimpleModel
     */
    public function getSimpleModel(SimpleModel $model): SimpleModel
    {
        return $model;
    }

    /**
     * @Route("/api-json-test/simple-models", methods={"POST"})
     *
     * @param SimpleModel[] $models
     *
     * @return SimpleModel[]
     */
    public function getSimpleModels(array $models): array
    {
        return $models;
    }

    /**
     * @Route("/api-json-test/family", methods={"PUT"})
     *
     * @param KenModel $model
     *
     * @return KenModel
     */
    public function getFamily(KenModel $model): KenModel
    {
        return $model;
    }

    /*
     * @Route("/api-json-test/array-of-objects", methods={"POST"})
    public function arrayOfObjects(ArrayOfObjectsModel $model): ArrayOfObjectsModel
    {
        return $model;
    }
     */

    /*
     * @Route("/api-json-test/nested-objects", methods={"PUT"})
    public function nestedObjects(): ApiResourceUpdatedHttpResponse
    {
        return new ApiResourceUpdatedHttpResponse($object->getId());
    }
     */
}
