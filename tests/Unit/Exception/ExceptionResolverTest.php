<?php

declare(strict_types=1);

namespace YaPro\ApiRation\Tests\Unit\Exception;

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
use YaPro\ApiRation\Exception\BadRequestException;
use YaPro\ApiRation\Exception\ExceptionResolver;
use YaPro\ApiRation\Exception\NotFoundException;
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
                HttpKernelInterface::MASTER_REQUEST,
                new Exception($exceptionMsg)
            ),
            'expectedRequest' => null,
        ];

        $exception = new BadRequestException(
            $exceptionMsg,
            ['some_field_name' => 'some error about some_field_name']
        );

        yield [
            'event' => new ExceptionEvent(
                $this->createMock(HttpKernelInterface::class),
                new Request(),
                HttpKernelInterface::MASTER_REQUEST,
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
                HttpKernelInterface::MASTER_REQUEST,
                new NotFoundException($exceptionMsg)
            ),
            'expectedRequest' => $this->createJsonResponse(
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
                HttpKernelInterface::MASTER_REQUEST,
                new UniqueConstraintViolationException($exceptionMsg, (new PDOException($pdoException)))
            ),
            'expectedRequest' => $this->createJsonResponse(
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
                HttpKernelInterface::MASTER_REQUEST,
                new ORMInvalidArgumentException(ExceptionResolver::ORM_INVALID_ARGUMENT_EXCEPTION_MESSAGE)
            ),
            'expectedRequest' => $this->createJsonResponse(
                ExceptionResolver::DEFAULT_HEADERS,
                Response::HTTP_OK,
                ExceptionResolver::MSG_ON_ORM_INVALID_ARGUMENT,
                ExceptionResolver::DEFAULT_ERRORS
            ),
        ];

        yield [
            'event' => new ExceptionEvent(
                $this->createMock(HttpKernelInterface::class),
                new Request(),
                HttpKernelInterface::MASTER_REQUEST,
                new ForeignKeyConstraintViolationException($exceptionMsg, (new PDOException($pdoException)))
            ),
            'expectedRequest' => $this->createJsonResponse(
                ExceptionResolver::DEFAULT_HEADERS,
                Response::HTTP_OK,
                sprintf(ExceptionResolver::MSG_ON_FOREIGN_CONSTRAINT_VIOLATION, ' - error occurred'),
                ExceptionResolver::DEFAULT_ERRORS
            ),
        ];

        yield [
            'event' => new ExceptionEvent(
                $this->createMock(HttpKernelInterface::class),
                new Request(),
                HttpKernelInterface::MASTER_REQUEST,
                new ForeignKeyConstraintViolationException(
                    $exceptionMsg . ExceptionResolver::SEPARATOR_IN_MSG_ON_FOREIGN_CONSTRAINT_VIOLATION . ' some_field_name',
                    (new PDOException($pdoException))
                )
            ),
            'expectedRequest' => $this->createJsonResponse(
                ExceptionResolver::DEFAULT_HEADERS,
                Response::HTTP_OK,
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
                HttpKernelInterface::MASTER_REQUEST,
                $httpExceptionMock
            ),
            'expectedRequest' => $this->createJsonResponse(
                ExceptionResolver::DEFAULT_HEADERS + $additionalHeaders,
                $statusCode,
                Response::$statusTexts[$statusCode],
                ExceptionResolver::DEFAULT_ERRORS
            ),
        ];
    }

    /**
     * @param ExceptionEvent $event
     * @param $expectedResponse
     * @dataProvider providerOnKernelException
     */
    public function testOnKernelException(ExceptionEvent $event, $expectedResponse): void
    {
        /** @see TranslatorInterface::trans() */
        $translator = $this->createConfiguredMock(
            TranslatorInterface::class,
            ['trans' => $event->getThrowable()->getMessage()]
        );
        $exceptionResolver = new ExceptionResolver($translator);

        $exceptionResolver->onKernelException($event);

        $request = $event->getResponse();
        if (null !== $expectedResponse) {
            $request->setDate($expectedResponse->getDate());
        }
        self::assertEquals($expectedResponse, $request);
    }

    public function providerJsonEncodeResponseContent(): Generator
    {
        $message = 'some message';
        $errors = ['€', 'http://example.com/some/cool/page'];
        yield [
            'message' => $message,
            'errors' => $errors,
            'expect' => json_encode(
                ['message' => $message, 'errors' => $errors],
                JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR
            ),
        ];
    }

    /**
     * @param string $message
     * @param array  $errors
     * @param string $expect
     * @dataProvider providerJsonEncodeResponseContent
     */
    public function testJsonEncodeResponseContent(string $message, array $errors, string $expect): void
    {
        $exceptionResolver = new ExceptionResolver($this->createMock(TranslatorInterface::class));
        $actual = $this->callClassMethod($exceptionResolver, 'jsonEncodeResponseContent', [$message, $errors]);
        self::assertSame($expect, $actual);
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
        $exceptionResolver = new ExceptionResolver($this->createMock(TranslatorInterface::class));
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
        $exceptionResolver = new ExceptionResolver($this->createMock(TranslatorInterface::class));
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
        $jsonResponse->setContent(
            json_encode(
                [
                    'message' => $exceptionMsg,
                    'errors' => $errors,
                ],
                JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR
            )
        );

        return $jsonResponse;
    }
}
