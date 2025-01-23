<?php

namespace App\Service\EventListener;

use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;

#[AsEventListener(event: 'kernel.exception', priority: 100, method: 'OnException')]
class ExceptionListener
{
    public function OnException(ExceptionEvent $event): void
    {
        $e = $event->getThrowable();

        if ($e->getCode() === Response::HTTP_FORBIDDEN) {
            $errorMessage = 'You are not allowed to perform this request.';
        } else {
            $errorMessage = $e->getMessage();
        }

        $response = new JsonResponse(
            ['message' => $errorMessage],
            $e->getCode() != 0 ? $e->getCode() : Response::HTTP_BAD_REQUEST
        );

        $event->setResponse($response);
    }
}
