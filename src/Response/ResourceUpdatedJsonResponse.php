<?php

declare(strict_types=1);

namespace YaPro\ApiRation\Response;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;

class ResourceUpdatedJsonResponse extends JsonResponse
{
	public function __construct(int $resourceId)
	{
		parent::__construct(['id' => $resourceId], Response::HTTP_OK);
	}
}
