<?php

declare(strict_types=1);

namespace App\User\Command;

use App\Command\CommandInterface;
use App\User\Validator\Constraints as AppUserAssert;
use Symfony\Component\Security\Core\Validator\Constraints as SecurityAssert;
use Symfony\Component\Validator\Constraints as Assert;

final class UserUpdatePasswordCommand implements CommandInterface
{
    /**
     * @Assert\NotBlank
     */
    public string $id;

    /**
     * @Assert\NotBlank
     * @SecurityAssert\UserPassword
     */
    public string $oldPassword;

    /**
     * @Assert\NotBlank
     * @AppUserAssert\PasswordStrenght
     */
    public string $password;
}
