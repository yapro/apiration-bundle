<?php

declare(strict_types=1);

namespace YaPro\ApiRationBundle\Tests\Unit\Request;

use Symfony\Component\Serializer\SerializerInterface;

class SerializerForMockSymfony5 implements SerializerInterface
{
    /**
     * @param mixed $data
     *
     * @return string
     */
    public function serialize(mixed $data, string $format, array $context = [])
    {
        return '';
    }

    /**
     * @param mixed $data
     *
     * @return string
     */
    public function deserialize(mixed $data, string $type, string $format, array $context = [])
    {
        return '';
    }

    public function decode()
    {
    }
}
