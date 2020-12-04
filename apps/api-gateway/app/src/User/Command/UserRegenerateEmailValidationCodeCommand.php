<?php

declare(strict_types=1);

namespace App\User\Command;

use App\Command\CommandInterface;
use App\Form\FormableInterface;
use App\Form\FormableTrait;
use Symfony\Component\Validator\Constraints as Assert;

final class UserRegenerateEmailValidationCodeCommand implements CommandInterface, FormableInterface
{
    use FormableTrait;

    /**
     * @Assert\NotBlank
     */
    public string $id;
}
