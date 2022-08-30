<?php

declare(strict_types=1);

namespace YaPro\ApiRationBundle\Exception;

use Symfony\Component\HttpFoundation\Response;

/**
 * Содержимое эксепшена не должно содержать секретных данных, потому что ошибки эксепшена будут отправлены http-клиенту.
 */
class BadRequestException extends \Exception implements \JsonSerializable
{
    /**
     * Список ошибок.
     */
    private array $errors;

    /**
     * @param string          $message
     * @param array           $errors   в виде [
     *                                  field => 'error description',
     *                                  field2 => [ 'first error', 'second error', ...]
     *                                  ]
     * @param \Throwable|null $previous
     */
    public function __construct(string $message = 'Unexpected error', array $errors = [], \Throwable $previous = null)
    {
        parent::__construct(
            $message,
            Response::HTTP_BAD_REQUEST,
            $previous
        );
        $this->errors = $errors;
    }

    #[\ReturnTypeWillChange]
    /**
     * @return array
     */
    public function jsonSerialize()
    {
        $errors = [];
        foreach ($this->errors as $fieldName => $error) {
            $messages = [];
            if (is_string($error)) {
                $messages = [$error];
            } elseif (is_array($error)) {
                $messages = array_values($error);
            }
            $errors[] = [
                'fieldName' => $fieldName,
                'messages' => $messages,
            ];
        }

        return [
            'message' => $this->getMessage(),
            'errors' => $errors,
        ];
    }
}
