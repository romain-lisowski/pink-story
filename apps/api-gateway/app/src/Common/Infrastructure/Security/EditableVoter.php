<?php

declare(strict_types=1);

namespace App\Common\Infrastructure\Security;

use App\Common\Domain\Model\EditableInterface;
use App\User\Domain\Model\UserRole;
use App\User\Infrastructure\Security\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

final class EditableVoter extends Voter
{
    private AuthorizationCheckerInterface $authorizationChecker;

    public function __construct(AuthorizationCheckerInterface $authorizationChecker)
    {
        $this->authorizationChecker = $authorizationChecker;
    }

    protected function supports(string $attribute, $subject): bool
    {
        // if the attribute isn't one we support, return false
        if (!in_array($attribute, [EditableInterface::UPDATE, EditableInterface::DELETE])) {
            return false;
        }

        // only vote on editable
        if (!$subject instanceof EditableInterface) {
            return false;
        }

        return true;
    }

    protected function voteOnAttribute(string $attribute, $subject, TokenInterface $token): bool
    {
        if (!$token->getUser() instanceof User) {
            // the user must be logged in; if not, deny access
            return false;
        }

        return $this->authorizationChecker->isGranted(User::ROLE_PREFIX.UserRole::MODERATOR);

        throw new \LogicException('This code should not be reached!');
    }
}
