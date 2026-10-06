<?php

namespace App\Tests;

use Symfony\Component\HttpFoundation\Response;

trait LocationTrait {
    /**
     * The Location header of a redirect.
     */
    private static function location(Response $response): string {
        $location = $response->headers->get('Location');
        self::assertNotNull($location, 'no Location header');

        return $location;
    }
}
