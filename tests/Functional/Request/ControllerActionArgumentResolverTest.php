<?php

declare(strict_types=1);

namespace YaPro\ApiRationBundle\Tests\Functional\Request;

use Generator;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadata;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use YaPro\ApiRationBundle\Exception\BadRequestException;
use YaPro\ApiRationBundle\Request\ControllerActionArgumentResolver;
use YaPro\ApiRationBundle\Tests\FunctionalExt\App\JsonConvertModel\DollModel;
use YaPro\ApiRationBundle\Tests\FunctionalExt\App\JsonConvertModel\KenModel;
use YaPro\Helper\FileHelper;
use YaPro\Helper\JsonHelper;
use YaPro\Helper\Validation\ScalarValidator;

class ControllerActionArgumentResolverTest extends KernelTestCase
{
    protected static SerializerInterface $serializer;
    protected static ScalarValidator $scalarValidator;
    protected static ValidatorInterface $validator;
    protected static FileHelper $fileHelper;
    protected static ControllerActionArgumentResolver $argumentResolver;

    public function setUp()
    {
        parent::setUp();
        self::bootKernel();
        self::$serializer = self::getContainer()->get(SerializerInterface::class);
        self::$scalarValidator = self::getContainer()->get(ScalarValidator::class);
        self::$validator = self::getContainer()->get(ValidatorInterface::class);
        self::$fileHelper = self::getContainer()->get(FileHelper::class);
        $jsonHelper = self::getContainer()->get(JsonHelper::class);
        $requestStack = new RequestStack();
        $requestStack->push(new Request());
        self::$argumentResolver = new ControllerActionArgumentResolver(
            self::$serializer,
            self::$scalarValidator,
            self::$validator,
            self::$fileHelper,
            $jsonHelper,
            $requestStack
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
            'query' => ['name' => 'value will be ignored', 'surname' => 'value will be ignored too'],
            'request' => [],
            'attributes' => ['city' => 'and the value will be ignored'],
            'content' => ['wife' => $wife, 'kids' => $kids, 'name' => $name, 'surname' => $surname, 'city' => $city],
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

        // we have to invoke method "supports" before method "resolve". The method "resolve" depends on support through its state.
        self::$argumentResolver->supports($request, $argumentMetadata);

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
