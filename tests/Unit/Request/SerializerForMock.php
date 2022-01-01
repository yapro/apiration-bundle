<?php

namespace YaPro\ApiRationBundle\Tests\Unit\Request;

use Symfony\Component\Serializer\SerializerInterface;

class SerializerForMock implements SerializerInterface
{
    public function serialize($data, string $format, array $context = []) {}

    public function deserialize($data, string $type, string $format, array $context = []) {}

    public function decode() {}
}
