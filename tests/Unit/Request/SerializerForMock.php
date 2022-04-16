<?php

declare(strict_types=1);

namespace YaPro\ApiRationBundle\Tests\Unit\Request;

use Symfony\Component\Serializer\SerializerInterface;

class SerializerForMock implements SerializerInterface
{
    /**
     * @param mixed $data
     *
     * @return string
     */
    public function serialize($data, string $format, array $context = [])
    {
    }

    /**
     * @param mixed $data
     *
     * @return string
     */
    public function deserialize($data, string $type, string $format, array $context = [])
    {
    }

    public function decode()
    {
    }
}
