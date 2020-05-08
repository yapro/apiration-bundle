<?php

declare(strict_types=1);

namespace YaPro\ApiRation\Tests\Unit\Exception;

use YaPro\ApiRation\Exception\BadRequestException;
use PHPUnit\Framework\TestCase;

class BadRequestExceptionTest extends TestCase
{
    /**
     * @return array[]
     */
    public function providerJsonSerialize(): array
    {
        return [
            [
                'input' => [
                    'message' => 'Unexpected error',
                    'errors' => [
                        'field[0].subfield[2].name' => 'Bad name Anton',
                    ],
                ],
                'output' => [
                    'message' => 'Unexpected error',
                    'errors' => [
                        [
                            'fieldName' => 'field[0].subfield[2].name',
                            'messages' => ['Bad name Anton'],
                        ],
                    ],
                ],
            ],
            [
                'input' => [
                    'message' => 'Unexpected error',
                    'errors' => [
                        'field[0].subfield[2].name' => 'Bad name Anton',
                        'field[0].subfield[3].name' => 'Bad name Irina',
                        'field[1].subfield[0].name' => 'Bad name Michael',
                        'field[1].subfield[0].lastname' => 'Bad lastname Fedorov',
                    ],
                ],
                'output' => [
                    'message' => 'Unexpected error',
                    'errors' => [
                        [
                            'fieldName' => 'field[0].subfield[2].name',
                            'messages' => ['Bad name Anton'],
                        ],
                        [
                            'fieldName' => 'field[0].subfield[3].name',
                            'messages' => ['Bad name Irina'],
                        ],
                        [
                            'fieldName' => 'field[1].subfield[0].name',
                            'messages' => ['Bad name Michael'],
                        ],
                        [
                            'fieldName' => 'field[1].subfield[0].lastname',
                            'messages' => ['Bad lastname Fedorov'],
                        ],
                    ],
                ],
            ],
            [
                'input' => [
                    'message' => 'Unexpected error',
                    'errors' => [
                        'field.subfield[2].name' => [
                            'Bad name Anton',
                            'Name is bad',
                            'Lorem ipsum',
                        ],
                        'field.subfield[3].name' => 'Bad name Irina',
                        'field.subfield[0].name' => [
                            'Bad name Michael',
                            'Michael Bad name ',
                            'name Michael is Bad ',
                        ],
                        'field.subfield[0].lastname' => 'Bad lastname Fedorov',
                    ],
                ],
                'expected' => [
                    'message' => 'Unexpected error',
                    'errors' => [
                        [
                            'fieldName' => 'field.subfield[2].name',
                            'messages' => [
                                'Bad name Anton',
                                'Name is bad',
                                'Lorem ipsum',
                            ],
                        ],
                        [
                            'fieldName' => 'field.subfield[3].name',
                            'messages' => ['Bad name Irina'],
                        ],
                        [
                            'fieldName' => 'field.subfield[0].name',
                            'messages' => [
                                'Bad name Michael',
                                'Michael Bad name ',
                                'name Michael is Bad ',
                            ],
                        ],
                        [
                            'fieldName' => 'field.subfield[0].lastname',
                            'messages' => ['Bad lastname Fedorov'],
                        ],
                    ],
                ],
            ],
        ];
    }

    /**
     * @dataProvider providerJsonSerialize
     */
    public function testJsonSerialize(array $input, array $expected): void
    {
        $exception = new BadRequestException($input['message'], $input['errors']);
        self::assertEquals($expected, $exception->jsonSerialize());
    }
}
