<?php

declare(strict_types=1);

namespace YaPro\ApiRation\Request;

use YaPro\ApiRation\Marker\ApiRationObjectInterface;
use YaPro\Helper\Validation\ScalarValidator;
use function class_implements;
use function explode;
use function in_array;
use function is_string;
use Laminas\Code\Reflection\ClassReflection;
use Laminas\Code\Reflection\DocBlock\Tag\ParamTag;
use Laminas\Code\Reflection\DocBlock\Tag\TagInterface;
use YaPro\ApiRation\Exception\BadRequestException;
use function str_replace;
use function strtolower;
use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Controller\ArgumentValueResolverInterface;
use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadata;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\UnsupportedMediaTypeHttpException;
use Symfony\Component\Serializer\Encoder\JsonDecode;
use Symfony\Component\Serializer\Exception\ExceptionInterface as SymfonySerializerException;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\Validator\ValidatorInterface;

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

    public function __construct(
        SerializerInterface $serializer,
        ScalarValidator $scalarValidator,
        ValidatorInterface $validator
    ) {
        $this->serializer = $serializer;
        $this->scalarValidator = $scalarValidator;
        $this->validator = $validator;
    }

    /**
     * {@inheritdoc}
     */
    public function supports(Request $request, ArgumentMetadata $argument)
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
        foreach ($reflector->getDeclaringFile()->getUses() as $classNamespace) {
            $fullClassNamespace = $this->findFullClassNamespace($className, $classNamespace);
            if ($fullClassNamespace !== '') {
                return $fullClassNamespace;
            }
        }

        return $className;
    }

    /**
     * @internal private
     *
     * @param string $argumentName
     * @param TagInterface[] $docBlockTags
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
        }
        if ($this->isApiRationObjectInterface($argument->getType())) {
            yield $this->apply($request, $argument->getType());
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
     * @param string $classNameWithNamespace
     * @return mixed
     * @throws BadRequestException
     */
    public function apply(Request $request, string $classNameWithNamespace)
    {
        try {
            if ($request->getMethod() === 'GET') {
                $array = $this->fixScalarData($request->query->all());
                $object = $this->serializer->denormalize($array, $classNameWithNamespace);
            } else {
                $parameters = $this->getParametersFromRequest($request);
                $object = $this->serializer->denormalize($parameters, $classNameWithNamespace);
            }
            //} catch (UnsupportedFormatException $e) {
            //    throw new UnsupportedMediaTypeHttpException($e->getMessage(), $e);
            //} catch (JMSSerializerException $e) {
            //    throw new BadRequestHttpException($e->getMessage(), $e);
        } catch (SymfonySerializerException $e) {
            throw new BadRequestHttpException($e->getMessage(), $e);
        }
        $this->validate($object);

        return $object;
    }

    /**
     * @internal private
     *
     * @param Request $request
     * @return array
     */
    public function getParametersFromRequest(Request $request): array
    {
        $requestContentType = $request->getContentType() ?? 'json';

        $query = $request->query;
        $requestParameters = $request->request;
        $attributes = $this->getPublicPathAttributes($request->attributes);
        $content = $request->getContent();
        // При POST запросе с `content-type: application/x-www-form-urlencoded` параметры, описанные в теле запроса,
        // автоматически устанавливаются в магический $_POST, поэтому нам не следует десериализовывать теже самые
        // параметры из тела запроса повторно. Плюс сериалайзер может не уметь дессериализовывать из этого content-type.
        if (!empty($content) && 0 === $requestParameters->count()) {
            $content = $this->serializer->decode($content, $requestContentType, [JsonDecode::ASSOCIATIVE => true]);
        }
        $content = is_array($content) ? $content : [];

        return array_merge(
            iterator_to_array($query),
            $attributes,
            iterator_to_array($requestParameters),
            $content
        );
    }

    /**
     * Получить только те атрибуты, которые не имеют префикса "_", т.е. только публичные.
     * По факту это будут значения slug из route'а.
     * @internal private
     */
    public function getPublicPathAttributes(ParameterBag $attributes): array
    {
        return array_filter(
            iterator_to_array($attributes),
            function ($key) {
                return $key[0] !== '_';
            },
            ARRAY_FILTER_USE_KEY
        );
    }

    /**
     * @internal private
     * @param $classNameWithNamespace
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
        $implements = class_implements($classNameWithNamespace, true);

        return in_array(ApiRationObjectInterface::class, $implements, true);
    }

    /**
     * @internal private
     * @param array $params
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
