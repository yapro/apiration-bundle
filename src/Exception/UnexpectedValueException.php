<?php

declare(strict_types=1);

namespace YaPro\ApiRationBundle\Exception;

use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

/**
 * Преобразование в респонс выполняет Symfony-хендлер
 */
class UnexpectedValueException extends BadRequestHttpException
{
}
