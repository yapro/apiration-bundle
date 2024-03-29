<?php

declare(strict_types=1);

namespace YaPro\ApiRationBundle\Tests\Unit\Request;

use Laminas\Code\Reflection\DocBlock\Tag\ParamTag;
use Laminas\Code\Reflection\DocBlock\Tag\ReturnTag;
use Laminas\Code\Reflection\DocBlock\Tag\TagInterface;
use PHPUnit\Framework\TestCase;
use function rand;
use stdClass;
use Symfony\Component\HttpFoundation\InputBag;
use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadata;
use Symfony\Component\Serializer\Exception\ExceptionInterface;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Component\Validator\ConstraintViolationListInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use YaPro\ApiRationBundle\Exception\BadRequestException;
use YaPro\ApiRationBundle\Marker\ApiRationJsonRequestInterface;
use YaPro\ApiRationBundle\Marker\ApiRationObjectInterface;
use YaPro\ApiRationBundle\Request\ControllerActionArgumentResolver;
use YaPro\ApiRationBundle\Request\JsonRequest;
use YaPro\Helper\FileHelper;
use YaPro\Helper\JsonHelper;
use YaPro\Helper\LiberatorTrait;
use YaPro\Helper\Validation\ScalarValidator;

class ControllerActionArgumentResolverTest extends TestCase
{
    use LiberatorTrait;

    private static ControllerActionArgumentResolver $argumentResolver;

    public function providerSupports(): array
    {
        return [
            [
                'argumentType' => null,
                'expected' => false,
            ],
            [
                'argumentType' => 'string',
                'expected' => false,
            ],
            [
                'argumentType' => 'array',
                'expected' => true,
            ],
            [
                'argumentType' => ExampleApiRationObject::class,
                'expected' => true,
            ],
            [
                'argumentType' => JsonRequest::class,
                'expected' => true,
            ],
        ];
    }

    /**
     * @dataProvider providerSupports
     *
     * @param ?string $argumentType
     * @param bool    $expected
     */
    public function testSupports(?string $argumentType, bool $expected): void
    {
        $argumentResolverMock = $this->getMockBuilder(ControllerActionArgumentResolver::class)
            ->disableOriginalConstructor()
            ->setMethodsExcept(['supports'])
            ->getMock();

        $argumentResolverMock->method('getClassNameWithNamespace')->willReturn(ExampleApiRationObject::class);

        $request = Request::createFromGlobals();
        $request->initialize([], [], ['_controller' => 'any string']);

        $argumentMock = $this->createMock(ArgumentMetadata::class);
        $argumentMock->method('getType')->willReturn($argumentType);
        $argumentMock->method('getName')->willReturn('any string');

        $this->assertSame($expected, $argumentResolverMock->supports($request, $argumentMock));
    }

    public function providerFixScalarData(): array
    {
        $filterValue = 12345;

        return [
            [
                'filterValue' => $filterValue,
                'params' => [
                    'first' => rand(),
                    'second' => rand(),
                ],
                'expected' => [
                    'first' => $filterValue,
                    'second' => $filterValue,
                ],
            ],
        ];
    }

    /**
     * @dataProvider providerFixScalarData
     *
     * @param int   $filterValue
     * @param array $params
     * @param array $expected
     */
    public function testFixScalarData(int $filterValue, array $params, array $expected): void
    {
        $resolver = $this->getMockBuilder(ControllerActionArgumentResolver::class)
            ->disableOriginalConstructor()
            ->setMethodsExcept(['fixScalarData'])
            ->getMock();
        $resolver->method('filterValue')->willReturn($filterValue);

        $actual = $resolver->fixScalarData($params);
        $this->assertEquals($expected, $actual);
    }

    public function providerValidate(): array
    {
        $object = new stdClass();
        $constraintViolationListNonValidMock = $this->createMock(ConstraintViolationListInterface::class);
        $constraintViolationListNonValidMock->method('count')->willReturn(0);

        return [
            'Void' => [
                'object' => $object,
                'constraintViolationList' => $constraintViolationListNonValidMock,
            ],
        ];
    }

    /**
     * @dataProvider providerValidate
     *
     * @param $object
     * @param ConstraintViolationListInterface $constraintViolationList
     *
     * @throws BadRequestException
     */
    public function testValidate(
        $object,
        ConstraintViolationListInterface $constraintViolationList
    ): void {
        $validatorMock = $this->createMock(ValidatorInterface::class);
        $validatorMock->method('validate')->willReturn($constraintViolationList);

        $requestStack = $this->createMock(RequestStack::class);
        $requestStack->method('getMainRequest')->willReturn(new Request());

        $argumentResolver = new ControllerActionArgumentResolver(
            $this->createMock(SerializerInterface::class),
            $this->createMock(ScalarValidator::class),
            $validatorMock,
            $this->createMock(FileHelper::class),
            $this->createMock(JsonHelper::class),
            $requestStack
        );

        $this->expectNotToPerformAssertions();
        $argumentResolver->validate($object);
    }

    public function providerValidateException(): array
    {
        $object = new stdClass();

        $data = [
            new ConstraintViolation('message', null, [], null, null, null),
            new ConstraintViolation('message_1', null, [], null, null, null),
        ];
        $constraintViolationListValidMock = new ConstraintViolationList($data);
        $errors = [];
        foreach ($constraintViolationListValidMock as $error) {
            $fieldName = $error->getPropertyPath();
            $errors[$fieldName][] = $error->getMessage();
        }
        $exception = new BadRequestException('Validation errors', $errors);

        return [
            'Exception' => [
                'object' => $object,
                'constraintViolationList' => $constraintViolationListValidMock,
                'exception' => $exception,
            ],
        ];
    }

    /**
     * @dataProvider providerValidateException
     *
     * @param $object
     * @param ConstraintViolationListInterface $constraintViolationList
     * @param BadRequestException              $exception
     */
    public function testValidateThrowException(
        $object,
        ConstraintViolationListInterface $constraintViolationList,
        BadRequestException $exception
    ): void {
        $validatorMock = $this->createMock(ValidatorInterface::class);
        $validatorMock->method('validate')->willReturn($constraintViolationList);

        $requestStack = $this->createMock(RequestStack::class);
        $requestStack->method('getMainRequest')->willReturn(new Request());

        $argumentResolver = new ControllerActionArgumentResolver(
            $this->createMock(SerializerInterface::class),
            $this->createMock(ScalarValidator::class),
            $validatorMock,
            $this->createMock(FileHelper::class),
            $this->createMock(JsonHelper::class),
            $requestStack
        );

        // BadRequestException имеет массив ошибок (errors) и их нужно проверить, иначе infection не выдает ошибку
        try {
            $argumentResolver->validate($object);
        } catch (BadRequestException $exceptionFromMethod) {
            $this->assertEquals($exception->jsonSerialize(), $exceptionFromMethod->jsonSerialize());
        }
        // проверка на выбрасывание самого исключения
        $this->expectExceptionObject($exception);
        $argumentResolver->validate($object);
    }

    public function providerApply(): iterable
    {
        $requestMock = $this->createConfiguredMock(Request::class, [
            'getContentType' => null,
            'getMethod' => 'GET',
        ]);
        $requestMock->query = new InputBag();
        // Serializer потому что метода denormalize нет в SerializerInterface
        yield [$requestMock, Serializer::class, 'denormalize'];
        // не SerializerForMock потому что метода denormalize нет в SerializerForMock

        $requestMock = $this->createConfiguredMock(Request::class, [
            'getContentType' => 'string',
            'getContent' => 'content',
            'getMethod' => 'POST',
        ]);
        // SerializerForMock потому что Serializer::deserialize() помечен как final
        yield [$requestMock, $this->getSerializerClassName(), 'deserialize'];
    }

    private function getSerializerClassName(): string
    {
        return PHP_MAJOR_VERSION === 7 ? SerializerForMockSymfony5::class : SerializerForMockSymfony6::class;
    }

    /**
     * @dataProvider providerApply
     *
     * @param Request $requestMock
     * @param string  $serializerClassName
     * @param string  $serializerMethod
     *
     * @throws BadRequestException
     */
    public function testApply(Request $requestMock, string $serializerClassName, string $serializerMethod): void
    {
        $expected = new stdClass();
        $expected->property = 'value';

        $requestStack = $this->createMock(RequestStack::class);
        $requestStack->method('getMainRequest')->willReturn(new Request());

        $resolver = $this->getMockBuilder(ControllerActionArgumentResolver::class)
            ->setConstructorArgs([
                $this->createConfiguredMock($serializerClassName, [
                    $serializerMethod => $expected,
                ]),
                $this->createMock(ScalarValidator::class),
                $this->createMock(ValidatorInterface::class),
                $this->createMock(FileHelper::class),
                $this->createMock(JsonHelper::class),
                $requestStack,
            ])
            ->setMethodsExcept(['apply'])
            ->getMock();

        $resolver->expects($this->once())->method('validate')->with($expected);
        $resolver->method('getObjectOrObjectCollectionFromRequestBody')->willReturn($expected);

        $actual = $resolver->apply($requestMock, 'AnyClassNameWithNamespace');
        $this->assertSame($expected, $actual);
    }

    public function testApplyThrowException(): void
    {
        $serializerMock = $this->createMock(Serializer::class);
        $serializerMock->method('denormalize')->willThrowException($this->createMock(ExceptionInterface::class));

        $requestStack = $this->createMock(RequestStack::class);
        $requestStack->method('getMainRequest')->willReturn(new Request());

        $resolver = $this->getMockBuilder(ControllerActionArgumentResolver::class)
            ->setConstructorArgs([
                $serializerMock,
                $this->createMock(ScalarValidator::class),
                $this->createMock(ValidatorInterface::class),
                $this->createMock(FileHelper::class),
                $this->createMock(JsonHelper::class),
                $requestStack,
            ])
            ->setMethodsExcept(['apply'])
            ->getMock();

        $resolver->expects($this->never())->method('validate');
        $this->expectException(BadRequestException::class);

        $request = $this->createMock(Request::class);
        $request->method('getMethod')->willReturn('GET');
        // динамично создаем объект, т.к. InputBag помечен как final
        $request->query = new class() {
            public function all()
            {
                return [];
            }
        };

        $resolver->apply($request, 'AnyClassNameWithNamespace');
    }

    public function testGetObjectOrObjectCollectionFromRequestBody()
    {
        $expected = mt_rand();
        $serializer = $this->createMock($this->getSerializerClassName());
        $serializer->method('deserialize')->willReturn($expected);

        $requestStack = $this->createMock(RequestStack::class);
        $requestStack->method('getMainRequest')->willReturn(new Request());

        $resolver = $this->getMockBuilder(ControllerActionArgumentResolver::class)
            ->setConstructorArgs([
                $serializer,
                $this->createMock(ScalarValidator::class),
                $this->createMock(ValidatorInterface::class),
                $this->createMock(FileHelper::class),
                $this->createMock(JsonHelper::class),
                $requestStack,
            ])
            ->setMethodsExcept(['getObjectOrObjectCollectionFromRequestBody'])
            ->getMock();

        $request = $this->createMock(Request::class);
        $request->method('getContentType')->willReturn('');

        $actual = $resolver->getObjectOrObjectCollectionFromRequestBody($request, 'AnyClassNameWithNamespace');
        $this->assertSame($expected, $actual);
    }

    public function providerGetClassNameWithNamespace(): array
    {
        return [
            [
                'controllerActionFunction' => ExampleController::class . '::exampleAction',
                'argumentName' => '',
                'findShortClassName' => '',
                'expected' => '',
            ],
            [
                'controllerActionFunction' => ExampleController::class . '::exampleAction',
                'argumentName' => 'nonExistentArgumentName',
                'findShortClassName' => '',
                'expected' => '',
            ],
            [
                'controllerActionFunction' => ExampleController::class . '::exampleAction',
                'argumentName' => 'existentArgumentName',
                'findShortClassName' => 'ExampleApiRationObject',
                'expected' => ExampleApiRationObject::class,
            ],
        ];
    }

    /**
     * @dataProvider providerGetClassNameWithNamespace
     *
     * @param string $controllerActionFunction
     * @param string $argumentName
     * @param string $findShortClassName
     * @param string $expected
     */
    public function testGetClassNameWithNamespace(
        string $controllerActionFunction,
        string $argumentName,
        string $findShortClassName,
        string $expected
    ): void {
        $resolver = $this->getMockBuilder(ControllerActionArgumentResolver::class)
            ->disableOriginalConstructor()
            ->setMethodsExcept(['getClassNameWithNamespace'])
            ->getMock();
        $resolver->method('findShortClassName')->willReturn($findShortClassName);
        $resolver->method('findFullClassNamespace')->willReturn($expected);
        $resolver->method('getUseList')->willReturn([$findShortClassName => $expected]);

        $actual = $resolver->getClassNameWithNamespace($controllerActionFunction, $argumentName);
        $this->assertSame($expected, $actual);
    }

    public function providerResolve(): array
    {
        $request = $this->createMock(Request::class);
        $request->attributes = new ParameterBag(['_controller' => 'string']);

        return [
            'if' => [
                'request' => $request,
                'argument' => $this->createConfiguredMock(ArgumentMetadata::class, [
                    'getType' => 'array',
                ]),
                'currentArgumentFqn' => 'any string',
                'expected' => 'expected value 1',
            ],
            'elseif' => [
                'request' => $request,
                'argument' => $this->createConfiguredMock(ArgumentMetadata::class, [
                    'getType' => 'not array',
                ]),
                'currentArgumentFqn' => ApiRationObjectInterface::class,
                'expected' => 'expected value 2',
            ],
            'else' => [
                'request' => $request,
                'argument' => $this->createConfiguredMock(ArgumentMetadata::class, [
                    'getType' => 'not array',
                ]),
                'currentArgumentFqn' => ApiRationJsonRequestInterface::class,
                'expected' => new JsonRequest($request, $this->createMock(JsonHelper::class)),
            ],
        ];
    }

    /**
     * @dataProvider providerResolve
     *
     * @param Request            $request
     * @param ArgumentMetadata   $argument
     * @param string             $currentArgumentFqn
     * @param string|JsonRequest $expected
     */
    public function testResolve(
        Request $request,
        ArgumentMetadata $argument,
        string $currentArgumentFqn,
        $expected
    ): void {
        $resolver = $this->getMockBuilder(ControllerActionArgumentResolver::class)
            ->disableOriginalConstructor()
            ->setMethodsExcept(['resolve'])
            ->getMock();
        $this->setClassPropertyValue($resolver, 'currentArgumentFqn', $currentArgumentFqn);
        $this->setClassPropertyValue($resolver, 'jsonHelper', $this->createMock(JsonHelper::class));

        $resolver->method('getClassNameWithNamespaceForApply')->willReturn('anyClassNameWithNamespaceForApply');
        $resolver->method('apply')->willReturn($expected);

        $this->assertEquals($expected, $resolver->resolve($request, $argument)->current());
    }

    public function providerFilterValue(): array
    {
        return [
            [
                'value' => '1',
                'expected' => 1,
                'filterInteger' => 1,
                'filterFloat' => null,
                'filterBoolean' => null,
            ],
            [
                'value' => '1.123',
                'expected' => 1.123,
                'filterInteger' => null,
                'filterFloat' => 1.123,
                'filterBoolean' => null,
            ],
            [
                'value' => 'true',
                'expected' => true,
                'filterInteger' => null,
                'filterFloat' => null,
                'filterBoolean' => true,
            ],
            [
                'value' => 'qwerty',
                'expected' => null,
                'filterInteger' => null,
                'filterFloat' => null,
                'filterBoolean' => null,
            ],
        ];
    }

    /**
     * @dataProvider providerFilterValue
     *
     * @param $value
     * @param $expected
     * @param int|null   $filterInteger
     * @param float|null $filterFloat
     * @param bool|null  $filterBoolean
     */
    public function testFilterValue(
        $value,
        $expected,
        ?int $filterInteger,
        ?float $filterFloat,
        ?bool $filterBoolean
    ): void {
        $scalarValidatorMock = $this->createConfiguredMock(ScalarValidator::class, [
            'filterInteger' => $filterInteger,
            'filterFloat' => $filterFloat,
            'filterBoolean' => $filterBoolean,
        ]);

        $requestStack = $this->createMock(RequestStack::class);
        $requestStack->method('getMainRequest')->willReturn(new Request());

        $resolver = $this->getMockBuilder(ControllerActionArgumentResolver::class)
            ->setConstructorArgs([
                $this->createMock(SerializerInterface::class),
                $scalarValidatorMock,
                $this->createMock(ValidatorInterface::class),
                $this->createMock(FileHelper::class),
                $this->createMock(JsonHelper::class),
                $requestStack,
            ])
            ->setMethodsExcept(['filterValue'])
            ->getMock();

        $actual = $resolver->filterValue($value);
        $this->assertSame($expected, $actual);
    }

    public function providerFindShortClassName(): array
    {
        $stringParamTag = new ParamTag();
        $stringParamTag->initialize('string $argument');

        $modelParamTag = new ParamTag();
        $modelParamTag->initialize('Model[] $model');

        $validTags = [
            $stringParamTag,
            $modelParamTag,
        ];

        $returnParam = new ReturnTag();
        $returnParam->initialize('string');

        $invalidTags = [
            $stringParamTag,
            $returnParam,
        ];

        return [
            'validTags' => [
                'argumentName' => 'model',
                'docBlockTags' => $validTags,
                'expected' => 'Model',
            ],
            'invalidTags' => [
                'argumentName' => 'invalidArgument',
                'docBlockTags' => $invalidTags,
                'expected' => '',
            ],
            'emptyTags' => [
                'argumentName' => '',
                'docBlockTags' => [],
                'expected' => '',
            ],
        ];
    }

    /**
     * @dataProvider providerFindShortClassName
     *
     * @param TagInterface[] $docBlockTags
     * @param string         $argumentName
     * @param string         $expected
     */
    public function testFindShortClassName(
        string $argumentName,
        array $docBlockTags,
        string $expected
    ): void {
        $resolver = $this->getMockBuilder(ControllerActionArgumentResolver::class)
            ->disableOriginalConstructor()
            ->setMethodsExcept(['findShortClassName'])
            ->getMock();

        $actual = $resolver->findShortClassName($argumentName, $docBlockTags);
        $this->assertSame($expected, $actual);
    }

    public function providerFindFullClassNamespace(): array
    {
        $className = 'MyClass';
        $classNamespace = 'MyNamespace\MyClass';
        // namespace невалидный потому что окончание не содержит MyClass
        $invalidClassNamespace = 'MyNamespace\WrongClass';

        return [
            [
                'className' => $className,
                'classNameSpace' => [
                    'as' => '',
                    'use' => $classNamespace,
                ],
                'expected' => $classNamespace,
            ],
            [
                'className' => $className,
                'classNameSpace' => [
                    'as' => $className,
                    'use' => $classNamespace,
                ],
                'expected' => $classNamespace,
            ],
            [
                'className' => $className,
                'classNameSpace' => [
                    'as' => '',
                    'use' => $invalidClassNamespace,
                ],
                'expected' => '',
            ],
        ];
    }

    /**
     * @dataProvider providerFindFullClassNamespace
     *
     * @param string   $className
     * @param string[] $classNameSpace
     * @param string   $expected
     */
    public function testFindFullClassNamespace(
        string $className,
        array $classNameSpace,
        string $expected
    ): void {
        $resolver = $this->getMockBuilder(ControllerActionArgumentResolver::class)
            ->disableOriginalConstructor()
            ->setMethodsExcept(['findFullClassNamespace'])
            ->getMock();

        $actual = $resolver->findFullClassNamespace($className, $classNameSpace);
        $this->assertSame($expected, $actual);
    }

    public function testGetClassNameWithNameSpaceForApply(): void
    {
        $classNameWithNamespace = 'AnyString';
        $resolver = $this->getMockBuilder(ControllerActionArgumentResolver::class)
            ->disableOriginalConstructor()
            ->setMethodsExcept(['getClassNameWithNamespaceForApply'])
            ->getMock();
        $actual = $resolver->getClassNameWithNamespaceForApply($classNameWithNamespace);
        self::assertSame($classNameWithNamespace . '[]', $actual);
    }
}
