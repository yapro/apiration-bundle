<?php

declare(strict_types=1);

namespace YaPro\ApiRationBundle\Marker;

use stdClass;
use Symfony\Component\HttpFoundation\Request;
use YaPro\MarkerInterfaces\Base\ImmutableDataTransferObjectInterface;

interface ApiRationJsonRequestInterface extends ImmutableDataTransferObjectInterface
{
    public function getRequest(): Request;

    public function getObject(): stdClass;

    public function getArray(): array;
}
