<?php

namespace App\EventListener;

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

        if (!array_key_exists($e->getCode(), Response::$statusTexts)) {
            $errorCode = Response::HTTP_BAD_REQUEST;
        } else {
            $errorCode =  $e->getCode() ;
        }


        if ($e->getCode() === Response::HTTP_INTERNAL_SERVER_ERROR) {
            $errorMessage = $e->getMessage(). ' Please try again later or contact the dev team.';
        } else {
            $errorMessage = $e->getMessage();
        }

        $response = new JsonResponse(
            ['message' => $errorMessage,
            'status' => $errorCode],
            $errorCode
        );

        $event->setResponse($response);
    }
}
