<?php

declare(strict_types=1);

namespace YaPro\ApiRation\Tests\Functional\Api\JsonConvertModel;

use YaPro\ApiRation\Marker\ApiRationObjectInterface;

class SimpleModel implements ApiRationObjectInterface
{
    private int $varInteger;
    private bool $varBoolean;
    private string $varString;
    private float $varFloat;
    private ?int $varNull;

    public function __construct(
        string $varString,
        int $varInteger,
        bool $varBoolean,
        float $varFloat = 0.0,
        int $varNull = null
    ) {
        $this->varString = $varString;
        $this->varInteger = $varInteger;
        $this->varBoolean = $varBoolean;
        $this->varFloat = $varFloat;
        $this->varNull = $varNull;
    }

    public function getVarString(): string
    {
        return $this->varString;
    }

    public function getVarInteger(): int
    {
        return $this->varInteger;
    }

    public function isVarBoolean(): bool
    {
        return $this->varBoolean;
    }

    public function getVarFloat(): float
    {
        return $this->varFloat;
    }

    public function getVarNull(): ?int
    {
        return $this->varNull;
    }
}
