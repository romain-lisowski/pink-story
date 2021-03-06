<?php

declare(strict_types=1);

namespace App\File\Exception;

use Exception;

final class ImageUploadException extends Exception
{
    protected $message = 'file.exception.image_upload';
}
