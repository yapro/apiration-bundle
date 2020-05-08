<?php

declare(strict_types=1);

namespace YaPro\ApiRation\Cors;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * @see https://www.upbeatproductions.com/blog/cors-pre-flight-requests-and-headers-symfony-httpkernel-component
 */
class CorsResolver implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            // приоритет должен быть больше чем у листенера, который проверяет возможные типы запроса у экшенов -
            // \Symfony\Component\HttpKernel\EventListener\RouterListener::getSubscribedEvents
            KernelEvents::RESPONSE => ['onKernelResponse', 0],
        ];
    }

    private function getOrigin(Request $request): string
    {
        $headerOrigin = $request->headers->get('origin', '');
        if ($headerOrigin === '') {
            return '';
        }
        $scheme = parse_url($headerOrigin, PHP_URL_SCHEME);
        $host = parse_url($headerOrigin, PHP_URL_HOST);
        $port = parse_url($headerOrigin, PHP_URL_PORT);
        if ($host === null) {
            throw new BadRequestHttpException('Header Option must have host name');
        }

        return ($scheme ? $scheme . '://' . $host : $host) . ($port ? ':' . $port : '');
    }

    public const ACCESS_CONTROL_ALLOW_CREDENTIALS = 'true';
    public const ACCESS_CONTROL_ALLOW_HEADERS = 'Origin, X-Requested-With, Content-Type, Accept, ' . // Authorization
        'Accept-Language, cache-control, pragma'; // <- Angular отправляет данные хедеры
    // User-Agent,Referer,Origin,Host,Connection,Access-Control-Request-Method,Access-Control-Request-Headers,Cache-Control,Origin,X-Requested-With,Content-Type,Accept,Accept-Encoding,Accept-Language
    public const ACCESS_CONTROL_ALLOW_METHODS = 'POST, GET, OPTIONS, PUT, PATCH, DELETE, HEAD';

    public function onKernelResponse(ResponseEvent $event): void
    {
        $origin = $this->getOrigin($event->getRequest());
        if ($origin === '') {
            return;
        }
        $event->getResponse()->headers->set('Access-Control-Allow-Origin', $origin);
        $event->getResponse()->headers->set('Access-Control-Allow-Headers', self::ACCESS_CONTROL_ALLOW_HEADERS);
        $event->getResponse()->headers->set('Access-Control-Allow-Methods', self::ACCESS_CONTROL_ALLOW_METHODS);
        $event->getResponse()->headers->set('Access-Control-Allow-Credentials', self::ACCESS_CONTROL_ALLOW_CREDENTIALS);
    }
}
