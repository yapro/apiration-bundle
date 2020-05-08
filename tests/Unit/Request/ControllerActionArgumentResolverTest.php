<?php

declare(strict_types=1);

namespace YaPro\ApiRation\Tests\Unit\Request;

use Generator;
use Laminas\Code\Reflection\DocBlock\Tag\ParamTag;
use Laminas\Code\Reflection\DocBlock\Tag\ReturnTag;
use Laminas\Code\Reflection\DocBlock\Tag\TagInterface;
use YaPro\ApiRation\Exception\BadRequestException;
use YaPro\ApiRation\Request\ControllerActionArgumentResolver;
use PHPUnit\Framework\TestCase;
use stdClass;
use Symfony\Component\HttpFoundation\InputBag;
use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadata;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Serializer\Exception\ExceptionInterface;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Component\Validator\ConstraintViolationListInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use YaPro\Helper\LiberatorTrait;
use YaPro\Helper\Validation\ScalarValidator;
use function rand;

class ControllerActionArgumentResolverTest extends TestCase
{
    use LiberatorTrait;

    private static ControllerActionArgumentResolver $argumentResolver;

    public function providerSupports(): array
    {
        return [
            [
                'argumentType' => 'any value',
                'expected' => true,
            ],
            [
                'argumentType' => 'array',
                'expected' => true,
            ],
        ];
    }

    /**
     * @dataProvider providerSupports
     *
     * @param string $argumentType
     * @param bool $expected
     */
    public function testSupports(string $argumentType, bool $expected): void
    {
        $argumentResolverMock = $this->getMockBuilder(ControllerActionArgumentResolver::class)
            ->disableOriginalConstructor()
            ->setMethodsExcept(['supports'])
            ->getMock();

        $argumentResolverMock->method('isApiRationObjectInterface')->willReturn($expected);

        $requestMock = $this->createMocK(Request::class);

        $argumentMock = $this->createMock(ArgumentMetadata::class);
        $argumentMock->method('getType')->willReturn('json');

        $this->assertSame($expected, $argumentResolverMock->supports($requestMock, $argumentMock));
    }

    public function providerIsApiRationObjectInterface(): array
    {
        return [
            'Null' => [
                'classNameWithNamespace' => null,
                'expected' => false,
            ],
            'String' => [
                'classNameWithNamespace' => 'STRING',
                'expected' => false,
            ],
            'Int' => [
                'classNameWithNamespace' => 'INT',
                'expected' => false,
            ],
            'SerializerInterface' => [
                'classNameWithNamespace' => SerializerInterface::class,
                'expected' => false,
            ],
            'ApiRationObjectInterface' => [
                'classNameWithNamespace' => ExampleApiRationObject::class,
                'expected' => true,
            ],
        ];
    }

    /**
     * @dataProvider providerIsApiRationObjectInterface
     *
     * @param $classNameWithNamespace
     * @param bool $expected
     */
    public function testIsApiRationObjectInterface($classNameWithNamespace, bool $expected): void
    {
        $resolver = $this->getMockBuilder(ControllerActionArgumentResolver::class)
            ->disableOriginalConstructor()
            ->setMethodsExcept(['isApiRationObjectInterface'])
            ->getMock();

        $actual = $resolver->isApiRationObjectInterface($classNameWithNamespace);
        $this->assertSame($expected, $actual);
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
     * @param int $filterValue
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

        $argumentResolver = new ControllerActionArgumentResolver(
            $this->createMock(SerializerInterface::class),
            $this->createMock(ScalarValidator::class),
            $validatorMock
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

        $argumentResolver = new ControllerActionArgumentResolver(
            $this->createMock(SerializerInterface::class),
            $this->createMock(ScalarValidator::class),
            $validatorMock
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
            'getContentType' => 'string',
            'getContent' => 'content',
            'getMethod' => 'POST',
        ]);
        yield [$requestMock];

        $requestMock = $this->createConfiguredMock(Request::class, [
            'getContentType' => null,
            'getMethod' => 'GET',
        ]);
        $requestMock->query = new InputBag();
        yield [$requestMock];
    }

    /**
     * @dataProvider providerApply
     *
     * @param Request $requestMock
     */
    public function testApply(
        Request $requestMock
    ): void {
        $expected = new stdClass();
        $expected->property = 'value';

        $resolver = $this->getMockBuilder(ControllerActionArgumentResolver::class)
            ->setConstructorArgs([
                $this->createConfiguredMock(Serializer::class, [
                    'denormalize' => $expected,
                ]),
                $this->createMock(ScalarValidator::class),
                $this->createMock(ValidatorInterface::class),
            ])
            ->setMethodsExcept(['apply'])
            ->getMock();

        $resolver
            ->expects($this->once())
            ->method('validate')
            ->with($expected);

        $actual = $resolver->apply($requestMock, 'AnyClassNameWithNamespace');
        $this->assertSame($expected, $actual);
    }

    public function testApplyThrowException(): void {

        $exception = $this->createMock(ExceptionInterface::class);
        $serializerMock = $this->createMock(Serializer::class);
        $serializerMock->method('denormalize')->willThrowException($exception);

        $resolver = $this->getMockBuilder(ControllerActionArgumentResolver::class)
            ->setConstructorArgs([
                $serializerMock,
                $this->createMock(ScalarValidator::class),
                $this->createMock(ValidatorInterface::class),
            ])
            ->setMethodsExcept(['apply'])
            ->getMock();

        $resolver
            ->expects($this->never())
            ->method('validate');
        $this->expectException(BadRequestHttpException::class);

        $requestMock = $this->createMock(Request::class);

        $resolver->apply($requestMock, 'AnyClassNameWithNamespace');
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

        $actual = $resolver->getClassNameWithNamespace($controllerActionFunction, $argumentName);
        $this->assertSame($expected, $actual);
    }

    public function providerResolve(): array
    {
        $request = $this->createMock(Request::class);
        $request->attributes = new ParameterBag(['_controller' => 'string']);

        return [
            'if-1' => [
                'request' => $request,
                'argument' => $this->createConfiguredMock(ArgumentMetadata::class, [
                    'getType' => 'array',
                    'getName' => 'any string',
                ]),
                'isApiRationObjectInterface' => false, // variable not used,
                'expected' => 'expected value',
            ],
            'if-2' => [
                'request' => $request,
                'argument' => $this->createConfiguredMock(ArgumentMetadata::class, [
                    'getType' => 'any value',
                ]),
                'isApiRationObjectInterface' => true,
                'expected' => 'expected value',
            ],
            'else' => [
                'request' => $request,
                'argument' => $this->createMock(ArgumentMetadata::class),
                'isApiRationObjectInterface' => false, // variable not used,
                'expected' => null,
            ]
        ];
    }

    /**
     * @dataProvider providerResolve
     *
     * @param Request $request
     * @param ArgumentMetadata $argument
     * @param bool $isApiRationObjectInterface
     * @param string|null $expected
     */
    public function testResolve(
        Request $request,
        ArgumentMetadata $argument,
        bool $isApiRationObjectInterface,
        ?string $expected
    ): void {
        $resolver = $this->getMockBuilder(ControllerActionArgumentResolver::class)
            ->disableOriginalConstructor()
            ->setMethodsExcept(['resolve'])
            ->getMock();

        $resolver->method('getClassNameWithNamespace')->willReturn('anyClassNameWithNamespace');
        $resolver->method('getClassNameWithNamespaceForApply')->willReturn('anyClassNameWithNamespaceForApply');
        $resolver->method('isApiRationObjectInterface')->willReturn($isApiRationObjectInterface);
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

        $resolver = $this->getMockBuilder(ControllerActionArgumentResolver::class)
            ->setConstructorArgs([
                $this->createMock(SerializerInterface::class),
                $scalarValidatorMock,
                $this->createMock(ValidatorInterface::class),
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

    public function providerGetParametersFromRequest(): Generator
    {
        $defaultContentType = 'json';
        yield [
            'request' => new Request(),
            'paramsFromRequestContent' => [],
            'expected' => [],
        ];

        $query = ['some_query_var' => 'some_query_var_value'];
        yield [
            'request' => new Request($query),
            'paramsFromRequestContent' => [],
            'expected' => $query,
        ];

        $content = ['some_content_var' => 'some_content_var_value'];
        yield [
            'request' => new Request($query, [], [], [], [], ['CONTENT_TYPE' => $defaultContentType], json_encode($content, JSON_THROW_ON_ERROR)),
            'paramsFromRequestContent' => $content,
            'expected' => ['some_query_var' => 'some_query_var_value', 'some_content_var' => 'some_content_var_value'],
        ];

        $request = new Request($query, [], [], [], [], ['CONTENT_TYPE' => 'jsonld'], json_encode($content, JSON_THROW_ON_ERROR));
        // установим content-type отличный от значения "по умолчанию", проверяя что парсинг параметров не привязан к конкретному формату
        //$request->headers->set('content-type', 'application/ld+json');
        yield [
            'request' => $request,
            'paramsFromRequestContent' => $content,
            'expected' => ['some_query_var' => 'some_query_var_value', 'some_content_var' => 'some_content_var_value'],
        ];

        $postParams = ['some_post_var' => 'some_post_var_value'];
        yield [
            'request' => new Request($query, $postParams, [], [], [], ['CONTENT_TYPE' => $defaultContentType], json_encode($content, JSON_THROW_ON_ERROR)),
            'paramsFromRequestContent' => $content,
            'expected' => [
                'some_query_var' => 'some_query_var_value',
                'some_post_var' => 'some_post_var_value',
            ],
        ];

        // Проверим что приоритет параметров в методе не изменен, например когда параметры в
        // query будут иметь одно и тоже имя, что и в content или attributes
        $query = [
            'some_query_var' => 'query',
            'some_query_var1' => 'query',
            'some_query_var2' => 'query',
            'some_query_var3' => 'query',
        ];
        $attributes = [
            'some_attr_var' => 'attributes',
            'some_attr_var1' => 'attributes',
            'some_attr_var2' => 'attributes',
            'some_query_var1' => 'attributes',
        ];
        $postParams = [
            'some_post_var' => 'postParams',
            'some_post_var1' => 'postParams',
            'some_attr_var1' => 'postParams',
            'some_query_var2' => 'postParams',
        ];
        $content = [
            'some_content_var' => 'content',
            'some_attr_var2' => 'content',
            'some_post_var1' => 'content',
            'some_query_var3' => 'content',
        ];
        yield [
            'request' => new Request($query, $postParams, $attributes, [], [], ['CONTENT_TYPE' => $defaultContentType], json_encode($content, JSON_THROW_ON_ERROR)),
            'paramsFromRequestContent' => $content,
            'expected' => [
                'some_query_var' => 'query',
                'some_query_var1' => 'attributes',
                'some_query_var2' => 'postParams',
                'some_query_var3' => 'query',
                'some_attr_var' => 'attributes',
                'some_attr_var1' => 'postParams',
                'some_attr_var2' => 'attributes',
                'some_post_var' => 'postParams',
                'some_post_var1' => 'postParams',
            ],
        ];
    }

    /**
     * @dataProvider providerGetParametersFromRequest
     *
     * @param Request $request
     * @param array   $paramsFromRequestContent
     * @param array   $expected
     */
    public function testGetParametersFromRequest(
        Request $request,
        array $paramsFromRequestContent,
        array $expected
    ): void {
        $serializer = $this->createConfiguredMock(SerializerForMock::class, [
            'decode' => $paramsFromRequestContent,
        ]);
        $resolver = $this->getMockBuilder(ControllerActionArgumentResolver::class)
            ->disableOriginalConstructor()
            ->setMethodsExcept(['getParametersFromRequest'])
            ->getMock();

        $resolver->method('getPublicPathAttributes')->willReturn($request->attributes->all());
        $this->setClassPropertyValue($resolver, 'serializer', $serializer);

        $actual = $resolver->getParametersFromRequest($request);
        self::assertSame($expected, $actual);
    }

    public function providerGetPublicPathAttributes(): Generator
    {
        yield [
            'attributes' => new ParameterBag(),
            'expected' => [],
        ];

        yield [
            'attributes' => new ParameterBag(['_private_var' => 'some_value', 'public_var' => 'some_value2']),
            'expected' => ['public_var' => 'some_value2'],
        ];
    }

    /**
     * @dataProvider providerGetPublicPathAttributes
     *
     * @param ParameterBag $attributes
     * @param array        $expected
     */
    public function testGetPublicPathAttributes(ParameterBag $attributes, array $expected): void
    {
        $resolver = $this->getMockBuilder(ControllerActionArgumentResolver::class)
            ->disableOriginalConstructor()
            ->setMethodsExcept(['getPublicPathAttributes'])
            ->getMock();

        $result = $resolver->getPublicPathAttributes($attributes);

        self::assertSame($expected, $result);
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
