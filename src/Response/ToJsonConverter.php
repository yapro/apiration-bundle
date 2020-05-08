<?php

declare(strict_types=1);

namespace YaPro\ApiRation\Response;

use YaPro\ApiRation\Marker\ApiRationObjectInterface;
use function is_array;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ViewEvent;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * Возвращаемый Controller::action()-ом объект преобразует в Json-структуру.
 */
class ToJsonConverter
{
    private SerializerInterface $serializer;

    public function __construct(
        SerializerInterface $serializer
    ) {
        $this->serializer = $serializer;
    }

    private function isJsonRequest($request): bool
    {
        return $request->headers->get('Content-Type') !== null && strpos($request->headers->get('Content-Type'), 'application/json') === 0;
    }

    public function onKernelView(ViewEvent $event): void
    {
        $result = $event->getControllerResult();
        if (!$result instanceof ApiRationObjectInterface && false === is_array($result)) { // !is_object($result) ||
            return;
        }
        $json = $this->serializer->serialize($result, 'json');
        $response = new Response($json);
        $response->headers->set('Content-Type', 'text/json');
        $event->setResponse($response);
    }
}
