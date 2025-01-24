<?php

namespace App\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\KernelEvents;

class ExceptionSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::EXCEPTION => 'onKernelException',
        ];
    }
    public function onKernelException(ExceptionEvent $event): void
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
