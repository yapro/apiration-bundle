<?php

declare(strict_types=1);

namespace YaPro\ApiRationBundle\Request;

use stdClass;
use Symfony\Component\HttpFoundation\Request;
use YaPro\ApiRationBundle\Marker\ApiRationJsonRequestInterface;
use YaPro\Helper\JsonHelper;

class JsonRequest implements ApiRationJsonRequestInterface
{
    private Request $request;
    private JsonHelper $jsonHelper;

    public function __construct(Request $request, JsonHelper $jsonHelper)
    {
        $this->request = $request;
        $this->jsonHelper = $jsonHelper;
    }

    public function getRequest(): Request
    {
        return $this->request;
    }

    public function getObject(): stdClass
    {
        return $this->jsonHelper->jsonDecode($this->request->getContent());
    }

    public function getArray(): array
    {
        return $this->jsonHelper->jsonDecode($this->request->getContent(), true);
    }
}
