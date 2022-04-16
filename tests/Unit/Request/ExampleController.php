<?php

declare(strict_types=1);

namespace YaPro\ApiRationBundle\Tests\Unit\Request;

use stdClass;

class ExampleController
{
    /**
     * @param stdClass               $a
     * @param ExampleApiRationObject $existentArgumentName
     * @param stdClass               $b
     */
    public function exampleAction(stdClass $a, ExampleApiRationObject $existentArgumentName, stdClass $b)
    {
    }
}
