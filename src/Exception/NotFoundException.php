<?php

declare(strict_types=1);

namespace YaPro\ApiRation\Exception;

/**
 * Пока что не обрабатывается, но планы описаны ниже.
 * Используется, когда входные данные синтаксически и логически корректны, но результат не может быть возвращен в силу
 * того, что данные например не найдены в базе данных.
 */
class NotFoundException extends \Exception
{
}
