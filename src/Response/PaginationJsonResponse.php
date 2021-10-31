<?php

declare(strict_types=1);

namespace YaPro\ApiRation\Response;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;

class PaginationJsonResponse extends JsonResponse
{
	public const ITEMS = 'items';
	public const TOTAL_ITEMS = 'totalItems';

    public function __construct(array $items = [], int $totalItems = 0, int $status = Response::HTTP_OK)
	{
		$input = [
			'result' => [
				self::ITEMS => $items,
				self::TOTAL_ITEMS => $totalItems,
			],
		];
		parent::__construct($input, $status);
	}
}
