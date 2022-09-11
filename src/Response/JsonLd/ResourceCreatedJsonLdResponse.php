<?php

declare(strict_types=1);

namespace YaPro\ApiRationBundle\Response\JsonLd;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class ResourceCreatedJsonLdResponse extends JsonResponse
{
    public function __construct($resourceId, array $data = [])
    {
        parent::__construct(['id' => $resourceId] + $data, Response::HTTP_CREATED);
    }
}
