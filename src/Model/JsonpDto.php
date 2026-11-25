<?php

declare(strict_types=1);

namespace App\Model;

use OpenApi\Attributes as OA;

/**
 * The query string of the public API endpoints: the optional JSONP callback.
 */
final readonly class JsonpDto
{
    public function __construct(
        #[OA\Property(description: 'JSONP callback')]
        public string $jsonp = '',
    ) {
    }
}
