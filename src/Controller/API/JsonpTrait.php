<?php

declare(strict_types=1);

namespace App\Controller\API;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

/**
 * The JSON content of the public API responses, with JSONP support.
 */
trait JsonpTrait
{
    /**
     * The JSON content of an API response, wrapped in the JSONP callback when one is given. The
     * callback is validated by JsonResponse::setCallback() (a JavaScript identifier, with dots and
     * brackets, no reserved word) and the script prefixed with a comment, against content sniffing.
     * An empty callback is ignored.
     */
    private function setJsonContent(Response $response, string $json, string $callback): void
    {
        if ('' === $callback) {
            $response->headers->set('Content-Type', 'application/json');
            $response->setContent($json);

            return;
        }

        $jsonp = new JsonResponse();
        $jsonp->setJson($json);
        try {
            $jsonp->setCallback($callback);
        } catch (\InvalidArgumentException $invalidArgumentException) {
            throw new BadRequestHttpException('Invalid JSONP callback.', $invalidArgumentException);
        }

        $response->headers->set('Content-Type', 'application/javascript');
        $response->setContent((string) $jsonp->getContent());
    }
}
