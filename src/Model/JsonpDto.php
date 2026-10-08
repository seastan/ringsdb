<?php

declare(strict_types=1);

namespace App\Model;

/**
 * The query string of the public API endpoints: the optional JSONP callback.
 */
final readonly class JsonpDto
{
    public function __construct(
        public string $jsonp = '',
    ) {
    }
}
