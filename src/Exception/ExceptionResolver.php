<?php

declare(strict_types=1);

namespace YaPro\ApiRationBundle\Exception;

use Doctrine\DBAL\Exception\ForeignKeyConstraintViolationException;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use YaPro\ApiRationBundle\Exception\BadRequestException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use YaPro\Helper\JsonHelper;

use function class_exists;

class ExceptionResolver
{
    public const DEFAULT_HEADERS = [
        'Content-Type' => 'application/json; charset=utf-8',
        'X-Content-Type-Options' => 'nosniff',
        'X-Frame-Options' => 'deny',
    ];
    public const DEFAULT_ERRORS = [];
    public const MSG_ON_DUPLICATE_ROWS = 'The object already exist';
    public const MSG_ON_ORM_INVALID_ARGUMENT = 'We do not allow saving entities through a given association graph';
    public const MSG_ON_FOREIGN_CONSTRAINT_VIOLATION = 'First you need to update or delete relation in %s';
    public const EXCEPTION_CODE_DUPLICATE_ENTRY = '23000';
    public const EXCEPTION_CODE_UNIQUE_CONSTRAINT = '23505';
    public const ORM_INVALID_ARGUMENT_EXCEPTION_MESSAGE = 'Multiple non-persisted new entities were found through the given association graph';
    public const SEPARATOR_IN_MSG_ON_FOREIGN_CONSTRAINT_VIOLATION = 'referenced from table';

    private TranslatorInterface $translator;
    private JsonHelper $jsonHelper;

    public function __construct(TranslatorInterface $translator, JsonHelper $jsonHelper)
    {
        $this->translator = $translator;
        $this->jsonHelper = $jsonHelper;
    }

    // @todo написать if-ы на все эксепшены \Doctrine\DBAL\Driver\AbstractPostgreSQLDriver::convertException
    public function onKernelException(ExceptionEvent $event): void
    {
        $exception = $event->getThrowable();
        $httpStatusCode = Response::HTTP_OK;
        $message = 'UndefinedMessage';
        $errors = self::DEFAULT_ERRORS;
        $headers = self::DEFAULT_HEADERS;

        if ($exception instanceof BadRequestException) {
            $httpStatusCode = Response::HTTP_BAD_REQUEST;
            $data = $exception->jsonSerialize();
            $message = $this->translator->trans($data['message']);
            $errors = $data['errors'];
        } elseif ($exception instanceof NotFoundException) {
            $message = $exception->getMessage();
        // API не умеет проверять наличие уже существующей записи в базе, поэтом если выполнить 2 запроса на создание
        // в бд уникальной записи, то возникнет эксепшен, который точно не 500-ая ошибка:
        } elseif ($this->isDuplicateRowInDatabase($exception)) {
            $httpStatusCode = Response::HTTP_CONFLICT;
            $message = self::MSG_ON_DUPLICATE_ROWS;
        // По-умолчанию Doctrine не сохраняет сущности, которые не добавлены через persist, API про это не знает и не
        // должен знать, но пытается создать сущности используя связи графа, поэтому возникает эксепшен, который по
        // факту точно не 500-ая ошибка:
        } elseif ($this->isORMInvalidArgumentException($exception)) {
            $message = self::MSG_ON_ORM_INVALID_ARGUMENT;
        } elseif ($exception instanceof ForeignKeyConstraintViolationException) {
            $tableName = explode(self::SEPARATOR_IN_MSG_ON_FOREIGN_CONSTRAINT_VIOLATION, $exception->getMessage());
            $message = sprintf(
                self::MSG_ON_FOREIGN_CONSTRAINT_VIOLATION,
                $tableName[1] ?? ' - error occurred'
            );
        } elseif ($exception instanceof HttpExceptionInterface) {
            // в дев-режиме мы должны знать все детали об ошибке - обработка ошибки будет передана Symfony:
            if ($_SERVER['APP_ENV'] === 'dev') {
                // полагаемся на симфоневый обработчик эксепшенов
                return;
            }
            // HttpExceptionInterface is a special type of exception that holds status code and header details
            $headers += $exception->getHeaders();
            $httpStatusCode = $exception->getStatusCode();
            $message = Response::$statusTexts[$httpStatusCode] ?? $message;
        } else {
            // полагаемся на симфоневый обработчик эксепшенов
            return;
        }

        $response = $event->getResponse();
        if ($response === null) {
            $response = new JsonResponse();
            $event->setResponse($response);
        }
        $response->headers->replace($headers);
        $response->setStatusCode($httpStatusCode);
        // за основу был взят RFC 7807 - https://tools.ietf.org/html/rfc7807 и немного переделан
        $response->setContent($this->jsonHelper->jsonEncode(
            [
                'message' => $message,
                'errors' => $errors,
            ]
        ));
    }

    /**
     * @param mixed $exception
     *
     * @return bool
     */
    private function isDuplicateRowInDatabase($exception): bool
    {
        return $exception instanceof UniqueConstraintViolationException &&
            $exception->getPrevious() &&
            // в третьей версии Doctrine\DBAL уже нет PDOException
            class_exists('Doctrine\DBAL\Driver\PDOException') &&
            $exception->getPrevious() instanceof \Doctrine\DBAL\Driver\PDOException &&
            (
                // Check exception: SQLSTATE[23505]: Unique violation: 7 ERROR:  duplicate key value violates unique
                // constraint ... DETAIL:  Key ... already exists.
                $exception->getPrevious()->getCode() === self::EXCEPTION_CODE_UNIQUE_CONSTRAINT //
                // SQLSTATE[23000]: Integrity constraint violation: 1062 Duplicate entry
                || $exception->getPrevious()->getCode() === self::EXCEPTION_CODE_DUPLICATE_ENTRY // попытка нарушить уникальный ключ по полю
            );
    }

    /**
     * Пример ошибки:.
     *
     * Multiple non-persisted new entities were found through the given association graph:
     * A new entity was found through the relationship 'App\Entity\SiteGroupAssociation#group' that was not
     * configured to cascade persist operations for entity: App\Entity\SiteGroup@0000000009207d3900000000034cf580.
     *
     * To solve this issue: Either explicitly call EntityManager#persist() on this unknown entity or configure
     * cascade persist this association in the mapping for example @ManyToOne(, cascade={"persist"}).
     * If you cannot find out which entity causes the problem implement 'App\Entity\SiteGroup#__toString()'
     * to get a clue.
     *
     * A new entity was found through the relationship 'App\Entity\SiteGroupAssociation#site' that was not
     * configured to cascade persist operations for entity: App\Entity\Site@0000000009207ce700000000034cf580.
     *
     * To solve this issue: Either explicitly call EntityManager#persist() on this unknown entity or configure
     * cascade persist this association in the mapping for example @ManyToOne(, cascade={"persist"}).
     * If you cannot find out which entity causes the problem implement 'App\Entity\Site#__toString()'
     * to get a clue.
     *
     * @param mixed $exception
     *
     * @return bool
     * @codeCoverageIgnore - потому что мутационные тесты ругаются на замену mb_strpos -> strpos. Что в данном
     * избыточно - константа никогда не изменится с текста на английском на текст со сложными символами unicode.
     * Но функция покрыта максимально
     */
    private function isORMInvalidArgumentException($exception): bool
    {
        return class_exists('Doctrine\ORM\ORMInvalidArgumentException')
            && $exception instanceof \Doctrine\ORM\ORMInvalidArgumentException
            && mb_strpos($exception->getMessage(), self::ORM_INVALID_ARGUMENT_EXCEPTION_MESSAGE) === 0;
    }
}
