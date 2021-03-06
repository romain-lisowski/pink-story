<?php

declare(strict_types=1);

namespace App\User\Action;

use App\Action\AbstractAction;
use App\Responder\ResponderInterface;
use App\User\Command\UserRegenerateEmailValidationCodeCommand;
use App\User\Command\UserRegenerateEmailValidationCodeCommandHandler;
use App\User\Security\UserSecurityManagerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @IsGranted("ROLE_USER")
 * @Route("/account/regenerate-email-validation-code", name="account_regenerate_email_validation_code", methods={"GET"})
 */
final class AccountRegenerateEmailValidationCodeAction extends AbstractAction
{
    private ResponderInterface $responder;
    private UserRegenerateEmailValidationCodeCommandHandler $handler;
    private UserSecurityManagerInterface $userSecurityManager;

    public function __construct(ResponderInterface $responder, UserRegenerateEmailValidationCodeCommandHandler $handler, UserSecurityManagerInterface $userSecurityManager)
    {
        $this->responder = $responder;
        $this->handler = $handler;
        $this->userSecurityManager = $userSecurityManager;
    }

    public function run(Request $request): Response
    {
        $command = new UserRegenerateEmailValidationCodeCommand();
        $command->id = $this->userSecurityManager->getCurrentUser()->getId();

        $this->handler->setCommand($command)->handle();

        return $this->responder->render();
    }
}
