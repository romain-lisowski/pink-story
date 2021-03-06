<?php

declare(strict_types=1);

namespace App\User\Command;

use App\Command\AbstractCommandHandler;
use App\Exception\InvalidSSLKeyException;
use App\User\Repository\Entity\UserRepositoryInterface;
use App\Validator\ValidatorManagerInterface;
use DateTime;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Firebase\JWT\JWT;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Symfony\Component\Security\Core\Exception\BadCredentialsException;
use Symfony\Component\Uid\Uuid;

final class UserGenerateAuthTokenCommandHandler extends AbstractCommandHandler
{
    private ParameterBagInterface $params;
    private UserPasswordEncoderInterface $passwordEncoder;
    private UserRepositoryInterface $userRepository;
    private ValidatorManagerInterface $validatorManager;

    public function __construct(ParameterBagInterface $params, UserPasswordEncoderInterface $passwordEncoder, UserRepositoryInterface $userRepository, ValidatorManagerInterface $validatorManager)
    {
        $this->params = $params;
        $this->passwordEncoder = $passwordEncoder;
        $this->userRepository = $userRepository;
        $this->validatorManager = $validatorManager;
    }

    public function handle(): string
    {
        try {
            $this->validatorManager->validate($this->command);

            $user = $this->userRepository->findOneByEmail($this->command->email);

            if (false === $this->passwordEncoder->isPasswordValid($user, $this->command->password)) {
                throw new BadCredentialsException('user.exception.bad_credentials_exception');
            }

            $payload = [
                'user_id' => $user->getId(),
                'user_secret' => $user->getSecret(),
                'app_secret' => $this->params->get('app_secret'),
                'iss' => $this->params->get('project_name'),
                'sub' => $user->getId(),
                'aud' => $this->params->get('project_name'),
                'exp' => (new DateTime())->modify('+1 month')->getTimestamp(),
                'nbf' => (new DateTime())->getTimestamp(),
                'iat' => (new DateTime())->getTimestamp(),
                'jti' => Uuid::v4()->toRfc4122(),
                'mercure' => [
                    'subscribe' => [
                        $this->params->get('project_front_web_dsn').'/users/'.$user->getId(), // mercure "target"
                    ],
                ],
            ];

            $privateKey = openssl_pkey_get_private(file_get_contents($this->params->get('jwt_private_key')), $this->params->get('jwt_pass_phrase'));

            if (false === $privateKey) {
                throw new InvalidSSLKeyException();
            }

            return JWT::encode($payload, $privateKey, $this->params->get('jwt_algorithm'));
        } catch (NonUniqueResultException | NoResultException $e) {
            throw new BadCredentialsException('user.exception.bad_credentials_exception');
        }
    }
}
