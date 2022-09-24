<?php

declare(strict_types=1);

namespace YaPro\ApiRationBundle\Tests\Unit\Request;

use stdClass;
use YaPro\ApiRationBundle\Marker\ApiRationJsonRequestInterface;

class ExampleApiRationJsonRequest implements ApiRationJsonRequestInterface
{
    public function getObject(): stdClass
    {
        // TODO: Implement getObject() method.
    }

    public function getArray(): array
    {
        // TODO: Implement getArray() method.
    }
}
