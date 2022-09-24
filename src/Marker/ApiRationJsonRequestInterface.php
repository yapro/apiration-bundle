<?php

declare(strict_types=1);

namespace YaPro\ApiRationBundle\Marker;

use stdClass;
use YaPro\MarkerInterfaces\Base\ImmutableDataTransferObjectInterface;

interface ApiRationJsonRequestInterface extends ImmutableDataTransferObjectInterface
{
    public function getObject(): stdClass;
    public function getArray(): array;
}
