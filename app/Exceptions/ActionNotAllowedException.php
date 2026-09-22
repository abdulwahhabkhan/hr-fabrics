<?php

namespace App\Exceptions;

use Exception;

class ActionNotAllowedException extends Exception
{
    public static function actionNotAllowed(string $message): static
    {
        return new static($message);
    }
}
