<?php

declare(strict_types=1);

namespace App\Exception;

use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;

/**
 * The user has reached their maximum number of decks (see User::getMaxNbDecks()).
 */
class TooManyDecksException extends UnprocessableEntityHttpException
{
    public function __construct()
    {
        parent::__construct('You have reached the maximum number of decks allowed. Delete some decks or increase your reputation.');
    }
}
