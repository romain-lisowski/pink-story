<?php

declare(strict_types=1);

namespace App\Voter;

use App\Model\EditableInterface;
use App\User\Model\UserInterface;
use LogicException;
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

    protected function supports(string $attribute, $subject)
    {
        // if the attribute isn't one we support, return false
        if (!in_array($attribute, [EditableInterface::CREATE, EditableInterface::READ, EditableInterface::UPDATE, EditableInterface::DELETE])) {
            return false;
        }

        // only vote on editable
        if (!$subject instanceof EditableInterface) {
            return false;
        }

        return true;
    }

    protected function voteOnAttribute(string $attribute, $subject, TokenInterface $token)
    {
        if (!$subject instanceof EditableInterface) {
            return false;
        }

        $currentUser = $token->getUser();

        if (!$currentUser instanceof UserInterface) {
            // the user must be logged in; if not, deny access
            return false;
        }

        $subject->setEditable($this->authorizationChecker, $currentUser);

        return $subject->getEditable();

        throw new LogicException('This code should not be reached!');
    }
}
