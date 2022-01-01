<?php

declare(strict_types=1);

namespace YaPro\ApiRationBundle\Marker;

use YaPro\MarkerInterfaces\Base\ValueObjectInterface;

/**
 * Объекты классов имплементирующих данный интерфейс могут быть автоматически преобразованы из/в json при HTTP-запросе.
 */
interface ApiRationObjectInterface extends ValueObjectInterface
{
}
