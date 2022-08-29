<?php

declare(strict_types=1);

namespace YaPro\ApiRationBundle\Request;

use Laminas\Code\Reflection\ClassReflection;
use Laminas\Code\Reflection\DocBlock\Tag\ParamTag;
use Laminas\Code\Reflection\DocBlock\Tag\TagInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Controller\ArgumentValueResolverInterface;
use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadata;
use Symfony\Component\Serializer\Exception\ExceptionInterface;
use Symfony\Component\Serializer\Exception\NotNormalizableValueException;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Polyfill\Php80\PhpToken;
use YaPro\ApiRationBundle\Exception\BadRequestException;
use YaPro\ApiRationBundle\Marker\ApiRationObjectInterface;
use YaPro\Helper\FileHelper;
use YaPro\Helper\Validation\ScalarValidator;

/**
 * Если в Controller::action() в качестве параметра указан ApiRationObjectInterface-объект, то запрос будет преобразован в
 * данный объект (с проверкой валидации).
 *
 * @see https://symfony.com/doc/current/controller/argument_value_resolver.html
 * @see http://fkn.ktu10.com/?q=node/10167
 *
 * Валидация на будущее:
 * @see https://qna.habr.com/q/548586#answer_1249224
 *
 * @final final убрал для юнит тестов
 */
class ControllerActionArgumentResolver implements ArgumentValueResolverInterface
{
    private SerializerInterface $serializer;
    private ScalarValidator $scalarValidator;
    private ValidatorInterface $validator;
    private FileHelper $fileHelper;

    public function __construct(
        SerializerInterface $serializer,
        ScalarValidator $scalarValidator,
        ValidatorInterface $validator,
        FileHelper $fileHelper
    ) {
        $this->serializer = $serializer;
        $this->scalarValidator = $scalarValidator;
        $this->validator = $validator;
        $this->fileHelper = $fileHelper;
    }

    /**
     * {@inheritdoc}
     */
    public function supports(Request $request, ArgumentMetadata $argument): bool
    {
        if ($argument->getType() === 'array') {
            // если аргумент объявлен как коллекция ApiRationObjectInterface-объектов
            return true;
        }

        return $this->isApiRationObjectInterface($argument->getType());
    }

    /**
     * @internal private
     *
     * @param string $controllerActionFunction
     * @param string $argumentName
     *
     * @return string
     */
    public function getClassNameWithNamespace(string $controllerActionFunction, string $argumentName): string
    {
        [$controllerClassName, $methodName] = explode('::', $controllerActionFunction);
        $reflector = new ClassReflection($controllerClassName);
        $docBlock = $reflector->getMethod($methodName)->getDocBlock();
        $className = $this->findShortClassName($argumentName, $docBlock->getTags());
        if ($className === '') {
            return $className;
        }
        // если имя класса записано от корня, например: \App\Model\MyClass
        if (mb_substr($className, 0, 1) === '\\') {
            return $className;
        }
        // вероятно имя класса записано не от корня, например: Model\MyClass или MyClass попробуем найти полный путь:
        $useList = $this->getUseList($reflector->getName());
        foreach ($useList as $alias => $classNameNamespace) {
            if ($alias === $className) {
                return $classNameNamespace;
            }
        }

        // что смогли, то сделали, на всякий случай возвращаем то, что удалось найти:
        return $className;
    }

    // https://gist.github.com/Zeronights/7b7d90fcf8d4daf9db0c
    //
    public function getUseList(string $classNamespace): array
    {
        $result = [];
        $tokens = PhpToken::tokenize($this->fileHelper->getFileContent($classNamespace));
        $thisIsUseString = false;
        $classNamespace = '';
        $classNamespaceAlias = '';
        foreach ($tokens as $token) {
            // $a .= "Line {$token->line}: {$token->getTokenName()} ('{$token->text}')" . PHP_EOL;
            if ($token->getTokenName() === 'T_CLASS') {
                break;
            }
            if ($token->getTokenName() === 'T_USE') {
                $thisIsUseString = true;
                $classNamespace = '';
                $classNamespaceAlias = '';
                continue;
            }
            if ($thisIsUseString) {
                if ($token->getTokenName() === ';') {
                    $thisIsUseString = false;
                    $result[$classNamespaceAlias] = $classNamespace;
                } else if ($token->getTokenName() === 'T_NAME_QUALIFIED') {
                    $classNamespace = $classNamespaceAlias = $token->text;
                } else if ($token->getTokenName() === 'T_STRING') {
                    $classNamespaceAlias = $token->text;
                }
            }
        }

        return $result;
    }

    /**
     * @internal private
     *
     * @param string         $argumentName
     * @param TagInterface[] $docBlockTags
     *
     * @return string
     */
    public function findShortClassName(string $argumentName, array $docBlockTags): string
    {
        foreach ($docBlockTags as $tag) {
            if ($tag instanceof ParamTag && $tag->getVariableName() === '$' . $argumentName) {
                $classNameArray = $tag->getTypes()[0]; // возвращает ExampleModel[]

                return str_replace('[]', '', $classNameArray);
            }
        }

        return '';
    }

    /**
     * @internal private
     *
     * @param string   $className
     * @param string[] $classNamespace
     *
     * @return string
     */
    public function findFullClassNamespace(string $className, array $classNamespace): string
    {
        if ($classNamespace['as'] === $className) {
            return $classNamespace['use'];
        }
        $parts = explode('\\', $classNamespace['use']);
        $classNameShort = end($parts);
        if ($classNameShort === $className) {
            return $classNamespace['use'];
        }

        return '';
    }

    public function resolve(Request $request, ArgumentMetadata $argument): \Generator
    {
        $controllerActionFunction = $request->attributes->get('_controller');
        if ($argument->getType() === 'array'
            && is_string($controllerActionFunction)
            && $classNameWithNamespace = $this->getClassNameWithNamespace($controllerActionFunction, $argument->getName())
        ) {
            yield $this->apply($request, $this->getClassNameWithNamespaceForApply($classNameWithNamespace));

            return;
        }
        if ($this->isApiRationObjectInterface($argument->getType())) {
            yield $this->apply($request, $argument->getType());

            return;
        }
        yield null;
    }

    /**
     * Метод используется только для вызова внутри resolve, чтобы избежать ошибок от infection
     *
     * @param string $classNameWithNamespace
     *
     * @return string
     */
    public function getClassNameWithNamespaceForApply(string $classNameWithNamespace): string
    {
        return $classNameWithNamespace . '[]';
    }

    /**
     * @throws BadRequestException
     */
    public function validate($object): void
    {
        $constraintViolationList = $this->validator->validate($object);
        if (!$constraintViolationList->count()) {
            return;
        }

        $errors = [];

        /* @var ConstraintViolation $error */
        foreach ($constraintViolationList as $error) {
            $fieldName = $error->getPropertyPath();
            $errors[$fieldName][] = $error->getMessage();
        }
        throw new BadRequestException('Validation errors', $errors);
    }

    /**
     * @internal private
     *
     * @param Request $request
     * @param string  $classNameWithNamespace
     *
     * @return mixed
     *
     * @throws BadRequestException
     */
    public function apply(Request $request, string $classNameWithNamespace)
    {
        if ($request->getMethod() === 'GET') {
            try {
                $array = $this->fixScalarData($request->query->all());
                $objectOrObjectCollection = $this->serializer->denormalize($array, $classNameWithNamespace);
            } catch (ExceptionInterface $e) {
                throw new BadRequestException('', [], $e);
            }
        } else {
            $objectOrObjectCollection = $this->getObjectOrObjectCollectionFromRequestBody(
                $request,
                $classNameWithNamespace
            );
        }
        $this->validate($objectOrObjectCollection);

        return $objectOrObjectCollection;
    }

    /**
     * @param Request $request
     * @param string  $classNameWithNamespace
     *
     * @return mixed
     *
     * @throws BadRequestException
     *
     * @internal private
     *
     * @see https://symfony.ru/doc/current/components/serializer.html#component-serializer-handling-circular-references-ru
     */
    public function getObjectOrObjectCollectionFromRequestBody(Request $request, string $classNameWithNamespace)
    {
        try {
            $requestContentType = str_ends_with($request->getContentType(), 'xml') || $request->getContentType() === 'text/html' ? 'xml' : 'json';

            return $this->serializer->deserialize($request->getContent(), $classNameWithNamespace, $requestContentType);
        } catch (NotNormalizableValueException $e) {
            throw new BadRequestException('Deserialization problem', ['check the API contract' => $e->getMessage()], $e);
        }
    }

    /**
     * @internal private
     *
     * @param $classNameWithNamespace
     *
     * @return bool
     */
    public function isApiRationObjectInterface($classNameWithNamespace): bool
    {
        if (
            // когда в Controller::action() не передается параметр, то $classNameWithNamespace === null
            !is_string($classNameWithNamespace)
            // когда в Controller::action() передается скалярный тип данных:
            || in_array(strtolower($classNameWithNamespace), ['string', 'float', 'int', 'integer', 'bool', 'boolean'], true)
        ) {
            return false;
        }
        if (false === class_exists($classNameWithNamespace)) {
            return false;
        }
        $implements = class_implements($classNameWithNamespace, true);

        return in_array(ApiRationObjectInterface::class, $implements, true);
    }

    /**
     * @internal private
     *
     * @param array $params
     *
     * @return array
     */
    public function fixScalarData(array $params): array
    {
        foreach ($params as $name => $value) {
            $valueFiltered = $this->filterValue($value);
            if ($valueFiltered !== null) {
                $params[$name] = $valueFiltered;
            }
        }

        return $params;
    }

    /**
     * @internal private
     *
     * @param $value
     *
     * @return bool|float|int|null
     */
    public function filterValue($value)
    {
        // приоритет определения типа очень важен:
        $valueFiltered = $this->scalarValidator->filterInteger($value);
        if ($valueFiltered !== null) {
            return $valueFiltered;
        }
        $valueFiltered = $this->scalarValidator->filterFloat($value);
        if ($valueFiltered !== null) {
            return $valueFiltered;
        }
        $valueFiltered = $this->scalarValidator->filterBoolean($value);
        if ($valueFiltered !== null) {
            return $valueFiltered;
        }

        return null;
    }
}
