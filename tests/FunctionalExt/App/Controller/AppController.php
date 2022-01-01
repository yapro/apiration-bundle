<?php
declare(strict_types=1);

namespace YaPro\ApiRationBundle\Tests\FunctionalExt\App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

class AppController extends AbstractController
{
    /**
     * @Route("/first")
     */
    public function firstAction(): JsonResponse
    {
        return $this->json([mt_rand() => mt_rand()]);
    }
}
