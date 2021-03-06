<?php

declare(strict_types=1);

namespace App\User\Message;

use App\Message\AbstractEntityMessage;
use App\Message\AsyncMessageInterface;

final class UserUpdateImageMessage extends AbstractEntityMessage implements AsyncMessageInterface
{
}
