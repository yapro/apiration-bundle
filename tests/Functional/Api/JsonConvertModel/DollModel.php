<?php

declare(strict_types=1);

namespace YaPro\ApiRation\Tests\Functional\Api\JsonConvertModel;

use Symfony\Component\Validator\Constraints as Assert;

class DollModel
{
    /**
     * @Assert\Length(
     *     min=2,
     *     max=50,
     *     minMessage="Name must be at least {{ limit }} characters long",
     *     maxMessage="Name cannot be longer than {{ limit }} characters"
     * )
     */
    protected string $name;

    public function __construct(string $name)
    {
        $this->name = $name;
    }

    public function getName(): string
    {
        return $this->name;
    }
}
