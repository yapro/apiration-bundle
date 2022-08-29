<?php

declare(strict_types=1);

namespace YaPro\ApiRationBundle\Tests\Functional\Api;

use YaPro\SymfonyHttpClientExt\HttpClientExtTrait;
use YaPro\SymfonyHttpTestExt\BaseTestCase;

class JsonTest extends BaseTestCase
{
    use HttpClientExtTrait;

    public function testInt(): void
    {
        $this->get('/api-json-test/123');
        $this->assertJsonResponse('{"id":123}');
    }

    public function testSimpleModel(): void
    {
        $this->get('/api-json-test/simple-model', [
            'varString' => 'string',
            'varInteger' => 123,
            'varBoolean' => 'true', // делаем строкой, иначе будет строковая 1
            'varFloat' => 0.123,
        ]);
        $this->assertJsonResponse('{"varString":"string","varInteger":123,"varBoolean":true,"varFloat":0.123,"varNull":null}');
    }

    public function testSimpleModels(): void
    {
        $this->post('/api-json-test/simple-models', [
            [
                'varString' => 'string',
                'varInteger' => 123,
                'varBoolean' => true,
            ],
        ]);
        $this->assertJsonResponse('[{"varString":"string","varInteger":123,"varBoolean":true,"varFloat":0.0,"varNull":null}]');
    }

    public function testFamilyModel(): void
    {
        $this->put('/api-json-test/family', [
            'name' => 'Ken',
            'wife' => [
                'name' => 'Barbie',
            ],
            'kids' => [
                // boy
                [
                    'name' => 'Todd',
                ],
                // girl
                [
                    'name' => 'Stacie',
                ],
            ],
        ]);
        $this->assertJsonResponse('{"wife":{"name":"Barbie"},"kids":[{"name":"Todd"},{"name":"Stacie"}],"surname":"","city":"","name":"Ken"}');
    }

    public function testErrorsApiContract(): void
    {
        $this->put('/api-json-test/family', [
            'name' => 'Ken',
            'wife' => [
                'name' => 'Barbie',
            ],
            'kids' => [
                // boy
                [
                    'name' => 123, // wrong type
                ],
                // girl
                [
                    'name' => 'Stacie',
                ],
            ],
        ]);
        $this->assertJsonResponse(trim('
            {
                "message": "Deserialization problem",
                "errors": [
                    {
                        "fieldName": "check the API contract",
                        "messages": [
                            "The type of the \"name\" attribute for class \"YaPro\\\ApiRationBundle\\\Tests\\\FunctionalExt\\\App\\\JsonConvertModel\\\DollModel\" must be one of \"string\" (\"int\" given)."
                        ]
                    }
                ]
            }
        '));
    }
}
