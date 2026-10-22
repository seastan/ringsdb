<?php

declare(strict_types=1);

namespace App\Search;

class SearchTypes
{
    /**
     * @var array<string, string>
     */
    public static $searchTypes = [
        '' => 'string',
        'f' => 'string',
        'i' => 'string',
        'k' => 'string',
        'x' => 'string',
        'e' => 'code',
        's' => 'code',
        't' => 'code',
        'c' => 'code',
        'a' => 'integer',
        'b' => 'integer',
        'd' => 'integer',
        'h' => 'integer',
        'o' => 'integer',
        'w' => 'integer',
        'y' => 'integer',
        'u' => 'boolean',
        'z' => 'boolean',
    ];
}
