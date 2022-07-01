<?php

declare(strict_types=1);

namespace YaPro\ApiRationBundle\Tests\Unit\Exception;

use Doctrine\DBAL\Driver\PDOException;
use Doctrine\DBAL\Exception\ForeignKeyConstraintViolationException;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\ORM\ORMInvalidArgumentException;
use Exception;
use Generator;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Throwable;
use YaPro\ApiRationBundle\Exception\BadRequestException;
use YaPro\ApiRationBundle\Exception\ExceptionResolver;
use YaPro\ApiRationBundle\Exception\NotFoundException;
use YaPro\Helper\JsonHelper;
use YaPro\Helper\LiberatorTrait;

class ExceptionResolverTest extends TestCase
{
    use LiberatorTrait;

    public function providerOnKernelException(): Generator
    {
        $exceptionMsg = 'some exception message';
        yield [
            'event' => new ExceptionEvent(
                $this->createMock(HttpKernelInterface::class),
                new Request(),
                HttpKernelInterface::MAIN_REQUEST,
                new Exception($exceptionMsg)
            ),
            'expectedResponse' => null,
        ];

        $exception = new BadRequestException(
            $exceptionMsg,
            ['some_field_name' => 'some error about some_field_name']
        );

        yield [
            'event' => new ExceptionEvent(
                $this->createMock(HttpKernelInterface::class),
                new Request(),
                HttpKernelInterface::MAIN_REQUEST,
                $exception
            ),
            'expectedResponse' => $this->createJsonResponse(
                ExceptionResolver::DEFAULT_HEADERS,
                Response::HTTP_BAD_REQUEST,
                $exceptionMsg,
                $exception->jsonSerialize()['errors']
            ),
        ];

        yield [
            'event' => new ExceptionEvent(
                $this->createMock(HttpKernelInterface::class),
                new Request(),
                HttpKernelInterface::MAIN_REQUEST,
                new NotFoundException($exceptionMsg)
            ),
            'expectedResponse' => $this->createJsonResponse(
                ExceptionResolver::DEFAULT_HEADERS,
                Response::HTTP_OK,
                $exceptionMsg,
                ExceptionResolver::DEFAULT_ERRORS
            ),
        ];

        $pdoException = new \PDOException('SQLSTATE[' . ExceptionResolver::EXCEPTION_CODE_DUPLICATE_ENTRY . ']');
        $this->setClassPropertyValue($pdoException, 'code', ExceptionResolver::EXCEPTION_CODE_DUPLICATE_ENTRY);
        yield [
            'event' => new ExceptionEvent(
                $this->createMock(HttpKernelInterface::class),
                new Request(),
                HttpKernelInterface::MAIN_REQUEST,
                new UniqueConstraintViolationException($exceptionMsg, (new PDOException($pdoException)))
            ),
            'expectedResponse' => $this->createJsonResponse(
                ExceptionResolver::DEFAULT_HEADERS,
                Response::HTTP_CONFLICT,
                ExceptionResolver::MSG_ON_DUPLICATE_ROWS,
                ExceptionResolver::DEFAULT_ERRORS
            ),
        ];

        yield [
            'event' => new ExceptionEvent(
                $this->createMock(HttpKernelInterface::class),
                new Request(),
                HttpKernelInterface::MAIN_REQUEST,
                new ORMInvalidArgumentException(ExceptionResolver::ORM_INVALID_ARGUMENT_EXCEPTION_MESSAGE)
            ),
            'expectedResponse' => $this->createJsonResponse(
                ExceptionResolver::DEFAULT_HEADERS,
                Response::HTTP_UNPROCESSABLE_ENTITY,
                ExceptionResolver::MSG_ON_ORM_INVALID_ARGUMENT,
                ExceptionResolver::DEFAULT_ERRORS
            ),
        ];

        yield [
            'event' => new ExceptionEvent(
                $this->createMock(HttpKernelInterface::class),
                new Request(),
                HttpKernelInterface::MAIN_REQUEST,
                new ForeignKeyConstraintViolationException($exceptionMsg, (new PDOException($pdoException)))
            ),
            'expectedResponse' => $this->createJsonResponse(
                ExceptionResolver::DEFAULT_HEADERS,
                Response::HTTP_UNPROCESSABLE_ENTITY,
                sprintf(ExceptionResolver::MSG_ON_FOREIGN_CONSTRAINT_VIOLATION, ' - error occurred'),
                ExceptionResolver::DEFAULT_ERRORS
            ),
        ];

        yield [
            'event' => new ExceptionEvent(
                $this->createMock(HttpKernelInterface::class),
                new Request(),
                HttpKernelInterface::MAIN_REQUEST,
                new ForeignKeyConstraintViolationException(
                    $exceptionMsg . ExceptionResolver::SEPARATOR_IN_MSG_ON_FOREIGN_CONSTRAINT_VIOLATION . ' some_field_name',
                    (new PDOException($pdoException))
                )
            ),
            'expectedResponse' => $this->createJsonResponse(
                ExceptionResolver::DEFAULT_HEADERS,
                Response::HTTP_UNPROCESSABLE_ENTITY,
                sprintf(ExceptionResolver::MSG_ON_FOREIGN_CONSTRAINT_VIOLATION, ' some_field_name'),
                ExceptionResolver::DEFAULT_ERRORS
            ),
        ];

        $additionalHeaders = ['x-some_header' => 'some_header_value'];
        $statusCode = array_rand(Response::$statusTexts);
        $httpExceptionMock = $this->createConfiguredMock(HttpExceptionInterface::class, [
            'getStatusCode' => $statusCode,
            'getHeaders' => $additionalHeaders,
        ]);
        yield [
            'event' => new ExceptionEvent(
                $this->createMock(HttpKernelInterface::class),
                new Request(),
                HttpKernelInterface::MAIN_REQUEST,
                $httpExceptionMock
            ),
            'expectedResponse' => $this->createJsonResponse(
                ExceptionResolver::DEFAULT_HEADERS + $additionalHeaders,
                $statusCode,
                Response::$statusTexts[$statusCode],
                ExceptionResolver::DEFAULT_ERRORS
            ),
        ];
    }

    /**
     * @param ExceptionEvent    $event
     * @param JsonResponse|null $expectedResponse
     * @dataProvider providerOnKernelException
     */
    public function testOnKernelException(ExceptionEvent $event, $expectedResponse): void
    {
        /** @see TranslatorInterface::trans() */
        $translator = $this->createConfiguredMock(
            TranslatorInterface::class,
            ['trans' => $event->getThrowable()->getMessage()]
        );
        $jsonHelper = $this->createMock(JsonHelper::class);
        $jsonHelper->method('jsonEncode')->willReturn($expectedResponse ? $expectedResponse->getContent() : null);

        $exceptionResolver = new ExceptionResolver($translator, $jsonHelper);
        $exceptionResolver->onKernelException($event);

        $response = $event->getResponse();
        if ($response !== null) {
            // подменяем динамично созданную дату времени образования респонса
            $response->setDate($expectedResponse->getDate());
        }
        self::assertEquals($expectedResponse, $response);
    }

    public function providerIsDuplicateRowInDatabase(): Generator
    {
        $notPdoException = $this->getMockForAbstractClass(\Doctrine\DBAL\Driver\DriverException::class);
        $this->setClassPropertyValue($notPdoException, 'code', ExceptionResolver::EXCEPTION_CODE_DUPLICATE_ENTRY);
        yield [
            new UniqueConstraintViolationException('message', $notPdoException),
            false,
        ];
        $pdoException = new \PDOException('SQLSTATE[' . ExceptionResolver::EXCEPTION_CODE_DUPLICATE_ENTRY . ']');
        $this->setClassPropertyValue($pdoException, 'code', 123);
        yield [
            new UniqueConstraintViolationException('message', new PDOException($pdoException)),
            false,
        ];
    }

    /**
     * @param Throwable $exception
     * @param bool      $expect
     * @dataProvider providerIsDuplicateRowInDatabase
     */
    public function testIsDuplicateRowInDatabase(Throwable $exception, bool $expect): void
    {
        $exceptionResolver = new ExceptionResolver($this->createMock(TranslatorInterface::class), $this->createMock(JsonHelper::class));
        $actual = $this->callClassMethod($exceptionResolver, 'isDuplicateRowInDatabase', [$exception]);
        self::assertSame($expect, $actual);
    }

    public function providerIsORMInvalidArgumentException(): Generator
    {
        yield [
            new Exception(ExceptionResolver::ORM_INVALID_ARGUMENT_EXCEPTION_MESSAGE),
            false,
        ];
        yield [
            new ORMInvalidArgumentException('some message'),
            false,
        ];
        yield [
            new ORMInvalidArgumentException(ExceptionResolver::ORM_INVALID_ARGUMENT_EXCEPTION_MESSAGE . 'some additional string'),
            true,
        ];
    }

    /**
     * @param Throwable $exception
     * @param bool      $expect
     * @dataProvider providerIsORMInvalidArgumentException
     */
    public function testIsORMInvalidArgumentException(Throwable $exception, bool $expect): void
    {
        $exceptionResolver = new ExceptionResolver($this->createMock(TranslatorInterface::class), $this->createMock(JsonHelper::class));
        $actual = $this->callClassMethod($exceptionResolver, 'isORMInvalidArgumentException', [$exception]);
        self::assertSame($expect, $actual);
    }

    private function createJsonResponse(
        array $headers,
        int $httpStatus,
        string $exceptionMsg,
        array $errors
    ): JsonResponse {
        $jsonResponse = new JsonResponse();
        $jsonResponse->headers->replace($headers);
        $jsonResponse->setStatusCode($httpStatus);
        $jsonResponse->setContent((new JsonHelper())->jsonEncode(
                [
                    'message' => $exceptionMsg,
                    'errors' => $errors,
                ]
            )
        );

        return $jsonResponse;
    }
}
