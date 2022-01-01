<?php

declare(strict_types=1);

namespace YaPro\ApiRationBundle\Tests\Functional\Request;

use Generator;
use YaPro\ApiRationBundle\Exception\BadRequestException;
use YaPro\ApiRationBundle\Request\ControllerActionArgumentResolver;
use YaPro\ApiRationBundle\Tests\Functional\Request\ApiRationObject\DollModel;
use YaPro\ApiRationBundle\Tests\Functional\Request\ApiRationObject\KenModel;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadata;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use YaPro\Helper\Validation\ScalarValidator;

class ControllerActionArgumentResolverTest extends KernelTestCase
{
    protected static SerializerInterface $serializer;
    protected static ScalarValidator $scalarValidator;
    protected static ValidatorInterface $validator;
    protected static ControllerActionArgumentResolver $argumentResolver;

    public static function setUpBeforeClass()
    {
        parent::setUpBeforeClass();
        self::bootKernel();
        self::$serializer = self::$container->get(SerializerInterface::class);
        self::$scalarValidator = self::$container->get(ScalarValidator::class);
        self::$validator = self::$container->get(ValidatorInterface::class);
        self::$argumentResolver = new ControllerActionArgumentResolver(
            self::$serializer,
            self::$scalarValidator,
            self::$validator
        );
    }

    /**
     * @return array[]
     */
    public function supportsProvider(): array
    {
        return [
            [
                'argumentType' => 'int',
                'expected' => false,
            ],
            [
                'argumentType' => KenModel::class,
                'expected' => true,
            ],
       ];
    }

    /**
     * @dataProvider supportsProvider
     */
    public function testSupports(string $argumentType, bool $expected): void
    {
        $argumentMetadata = new ArgumentMetadata('testVar', $argumentType, false, false, null);
        $request = Request::createFromGlobals();
        self::assertEquals(
            $expected,
            self::$argumentResolver->supports($request, $argumentMetadata),
            "Аргумент типа $argumentType " . $expected ? 'Должен' : 'НЕ Должен' . ' подлежать обработке'
        );
    }

    public function providerResolve(): Generator
    {
        $kids = [
            new DollModel('Todd'),
            new DollModel('Stacie'),
        ];
        $wife = new DollModel('Barbie');
        $name = 'Ken';
        $surname = 'Doe';
        $city = 'Moscow';
        $expectedKenModel = new KenModel($name, $wife, $kids, $surname, $city);

        yield [
            'query' => ['name' => $name, 'surname' => $surname],
            'request' => [],
            'attributes' => ['city' => $city],
            'content' => ['wife' => $wife, 'kids' => $kids],
            'expectedKenModel' => $expectedKenModel,
        ];
    }

    /**
     * @dataProvider providerResolve
     */
    public function testResolve($query, $requestParams, $attributes, $content, $expectedKenModel): void
    {
        $argumentMetadata = new ArgumentMetadata('testVar', KenModel::class, false, false, null);

        $request = Request::createFromGlobals();
        // Request должен содержать корректный json сериализированые данные KenModel
        $request->initialize(
            $query, $requestParams, $attributes, [], [],
            [
                'HTTP_CONTENT_TYPE' => 'application/json',
                'REQUEST_METHOD' => 'POST',
            ],
            self::$serializer->serialize($content, 'json')
        );
        $resolveArguments = iterator_to_array(
            self::$argumentResolver->resolve($request, $argumentMetadata)
        );
        self::assertCount(1, $resolveArguments, 'Должен вернуть 1 аргумент');
        self::assertEquals(
            $expectedKenModel,
            $resolveArguments[0],
            'Полученный объект KenModel не соответствует образцу',
        );
    }

    public function testValidate(): void
    {
        $validKenModel = new KenModel(
            'Ken',
            new DollModel('Barbie'),
            [
                new DollModel('Todd'),
                new DollModel('Stacie'),
            ]
        );

        self::$argumentResolver->validate($validKenModel);

        $errors = [];
        $invalidKenModel = new KenModel(
            'Ken',
            new DollModel('Barbie'),
            [
                new DollModel('T'),
                new DollModel('S'),
            ]
        );
        try {
            self::$argumentResolver->validate($invalidKenModel);
        } catch (BadRequestException $badRequestException) {
            $errors = $badRequestException->jsonSerialize();
        }
        $expectedErrors = [
            'message' => 'Validation errors',
            'errors' => [
                [
                    'fieldName' => 'kids[0].name',
                    'messages' => [
                        'Name must be at least 2 characters long',
                    ],
                ],
                [
                    'fieldName' => 'kids[1].name',
                    'messages' => [
                        'Name must be at least 2 characters long',
                    ],
                ],
            ],
        ];
        self::assertEquals($expectedErrors, $errors, 'Нет ожидаемых ошибок валидации');
    }
}
