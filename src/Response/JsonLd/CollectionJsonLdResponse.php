<?php

declare(strict_types=1);

namespace YaPro\ApiRationBundle\Response\JsonLd;

use ArrayObject;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use UnexpectedValueException;

class CollectionJsonLdResponse extends JsonResponse
{
    public const ITEMS = 'hydra:member';
    public const TOTAL_ITEMS = 'hydra:totalItems';
    public const DEFAULT_PAGE = 0;
    public const ITEMS_PER_PAGE = 10;
    const PAGE_FIELD = 'page';
    const ITEMS_PER_PAGE_FIELD = 'itemsPerPage';

    private Request $request;
    private int $page = 0;
    private int $offset = 0;
    private int $limit = 0;

    public function __construct(Request $request, int $page = null, int $itemsPerPage = null)
    {
        parent::__construct(null, Response::HTTP_NOT_FOUND);
        $this->request = $request;
        $this->page = $this->findPage($page);
        $this->limit = $this->findLimit($itemsPerPage);
        $this->offset = $this->page * $this->limit;
    }

    public function findPage(int $page = null): int
    {
        if ($page !== null) {
            return $page;
        }
        $result = filter_var($this->request->query->get(self::PAGE_FIELD, 0), FILTER_VALIDATE_INT);

        // client может указать номер страницы, но значение может быть запредельным:
        return $result > 0 ? $result - 1 : 0;
    }

    public function findLimit(int $itemsPerPage = null): int
    {
        if ($itemsPerPage !== null) {
            return $itemsPerPage;
        }
        $result = $this->request->query->get(self::ITEMS_PER_PAGE_FIELD, self::ITEMS_PER_PAGE);
        $result = filter_var($result, FILTER_VALIDATE_INT);
        // client может сам указать кол-во возвращаемых строк, но значение может быть запредельным:
        if ($result < 0 || $result > 100) {
            return 100;
        }

        return $result;
    }

    public function initData(array $items = [], int $totalItems = 0): self
    {
        if ($this->getContent() !== '{}') {
            throw new UnexpectedValueException('Data has already been specified');
        }
        $this->setData($this->getDataArrayObject($items, $totalItems));
        $this->setStatusCode(Response::HTTP_OK);

        return $this;
    }

    public function getRequestPathWithoutPage(): string
    {
        $getVariables = $this->request->query->all();
        unset($getVariables[self::PAGE_FIELD]);
        /*
         * Функция http_build_query удаляет параметры со значениями null и преобразует true/false в 0 и 1
         * Для формирования корректной подписи, требуется рекурсивное преобразование элементов
         * Про null - https://secure.php.net/manual/ru/function.http-build-query.php#60523
         */
        return $this->request->getPathInfo() . '?' . http_build_query($getVariables) . '&';
    }

    public function getDataArrayObject(array $items = [], int $totalItems = 0): ArrayObject
    {
        $result = new ArrayObject([
            "@type" => "hydra:Collection",
            self::ITEMS => $items,
            self::TOTAL_ITEMS => $totalItems,
        ]);
        if (empty($items)) {
            return $result;
        }
        $urlPath = $this->getRequestPathWithoutPage();
        $view = [
            '@type' => 'hydra:PartialCollectionView',
            '@id' => $urlPath . self::PAGE_FIELD . '=' . $this->page, // текущая
            'hydra:first' => $urlPath . self::PAGE_FIELD . '=' . self::DEFAULT_PAGE,
            'hydra:last' => $urlPath . self::PAGE_FIELD . '=' . round($totalItems / $this->limit),
            'hydra:next' => $urlPath . self::PAGE_FIELD . '=' . ($this->page + 1),
        ];
        $result->offsetSet('hydra:view', $view);

        return $result;
    }

    public function getPage(): int
    {
        return $this->page;
    }

    public function getOffset(): int
    {
        return $this->offset;
    }

    public function getLimit(): int
    {
        return $this->limit;
    }
}
