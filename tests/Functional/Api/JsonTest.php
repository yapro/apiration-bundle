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
        //$client = self::createClient();
        $this->get('/app');
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
        self::assertEquals(
            '[{"varString":"string","varInteger":123,"varBoolean":true,"varFloat":0,"varNull":null}]',
            $this->getResponseObject()->getContent()
        );
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
        self::assertEquals(
            '{"wife":{"name":"Barbie"},"kids":[{"name":"Todd"},{"name":"Stacie"}],"surname":"","city":"","name":"Ken"}',
            $this->getResponseObject()->getContent()
        );
    }
}
