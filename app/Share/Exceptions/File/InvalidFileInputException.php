<?php

declare(strict_types=1);

namespace App\Share\Exceptions\File;

class InvalidFileInputException extends FileException
{
    public function __construct(string $message = 'Invalid file input')
    {
        parent::__construct($message);
    }
}
