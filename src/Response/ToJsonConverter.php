<?php

declare(strict_types=1);

namespace YaPro\ApiRationBundle\Response;

use YaPro\ApiRationBundle\Marker\ApiRationObjectInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ViewEvent;
use Symfony\Component\Serializer\SerializerInterface;

use function is_array;

/**
 * Возвращаемый Controller::action()-ом объект преобразует в Json-структуру.
 */
class ToJsonConverter
{
    const JSON = 'application/json';
    const LD_JSON = 'application/ld+json';
    const CONTENT_TYPE = 'Content-Type';
    const UNSUPPORTED = 'unsupported';
    private SerializerInterface $serializer;

    public function __construct(
        SerializerInterface $serializer
    ) {
        $this->serializer = $serializer;
    }

    private function getContentType($request): string
    {
        $accept = $request->headers->get('Accept');
        $contentType = $request->headers->get(self::CONTENT_TYPE);
        // Заголовок Accept используется HTTP-клиентом, чтобы сообщить серверу, какой тип контента клиент может принять
        // Затем сервер отправит клиенту Content-Type о том какой тип содержимого на самом деле возвращается
        // Клиент может отправить Content-Type когда отправляет POST/PUT чтобы сказать серверу о типе содержимого
        // Итог: клиент обязан послать Accept + при необходимости Content-Type, а сервер обязан возвратить Content-Type
        if ($accept === null && $contentType === null) {
            return self::LD_JSON;
        }
        $accept = mb_strtolower($accept);
        $contentType = mb_strtolower($contentType);
        if (
            strpos($accept, self::JSON) === 0 ||
            strpos($contentType, self::JSON) === 0) {
            return self::JSON;
        }
        if (
            strpos($accept, '*/*') === 0 ||
            strpos($accept, 'application/*') === 0 ||
            strpos($accept, self::LD_JSON) === 0 ||
            strpos($contentType, self::LD_JSON) === 0) {
            return self::LD_JSON;
        }

        return self::UNSUPPORTED;
    }

    public function onKernelView(ViewEvent $event): void
    {
        $result = $event->getControllerResult();
        if (is_array($result)) {
            foreach ($result as $item) {
                if (!$item instanceof ApiRationObjectInterface) {
                    return;
                }
            }
        } elseif (!$result instanceof ApiRationObjectInterface) {
            return;
        }
        $contentType = $this->getContentType($event->getRequest());
        if ($contentType === self::UNSUPPORTED) {
            return;
        }
        if ($contentType === self::LD_JSON) {
            // todo
        }
        $json = $this->serializer->serialize($result, 'json');
        $response = new Response($json);
        $response->headers->set(self::CONTENT_TYPE, $contentType); // 'text/json'
        $event->setResponse($response);
    }
}
