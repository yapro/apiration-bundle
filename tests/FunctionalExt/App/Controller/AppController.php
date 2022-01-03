<?php
declare(strict_types=1);

namespace YaPro\ApiRationBundle\Tests\FunctionalExt\App\Controller;

use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Routing\Annotation\Route;
use YaPro\ApiRationBundle\Tests\FunctionalExt\App\JsonConvertModel\KenModel;
use YaPro\ApiRationBundle\Tests\FunctionalExt\App\JsonConvertModel\SimpleModel;

use function fopen;
use function strpos;

class AppController extends AbstractController
{
    /**
     * @Route("/first")
     */
    public function firstAction(): JsonResponse
    {
        return $this->json([mt_rand() => mt_rand()]);
    }

    /**
     * @Route("/api-json-test/{id<\d+>}")
     */
    public function discountOrder(int $id, ContainerInterface $container): JsonResponse
    {
        return $this->json(['id' => $id]);
    }

    /**
     * @Route("/api-json-test/simple-model")
     *
     * @param \YaPro\ApiRationBundle\Tests\FunctionalExt\App\JsonConvertModel\SimpleModel $model
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
     * @param \YaPro\ApiRationBundle\Tests\FunctionalExt\App\JsonConvertModel\SimpleModel[] $models
     *
     * @return \YaPro\ApiRationBundle\Tests\FunctionalExt\App\JsonConvertModel\SimpleModel[]
     */
    public function getSimpleModels(array $models): array
    {
        return $models;
    }

    /**
     * @Route("/api-json-test/family", methods={"PUT"})
     *
     * @param \YaPro\ApiRationBundle\Tests\FunctionalExt\App\JsonConvertModel\KenModel $model
     *
     * @return \YaPro\ApiRationBundle\Tests\FunctionalExt\App\JsonConvertModel\KenModel
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
