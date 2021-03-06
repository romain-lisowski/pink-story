<?php

declare(strict_types=1);

namespace App\User\Repository\Dto;

use App\Language\Model\Dto\CurrentLanguage;
use App\Language\Model\Dto\LanguageMedium;
use App\Language\Repository\Dto\LanguageRepositoryInterface;
use App\Repository\Dto\AbstractRepository;
use App\User\Model\Dto\CurrentUser;
use App\User\Model\Dto\UserForUpdate;
use App\User\Model\Dto\UserFull;
use App\User\Model\UserStatus;
use App\User\Query\UserGetForUpdateQuery;
use App\User\Query\UserGetQuery;
use DateTime;
use Doctrine\DBAL\Query\QueryBuilder;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\NoResultException;

final class UserRepository extends AbstractRepository implements UserRepositoryInterface
{
    private LanguageRepositoryInterface $languageRepository;

    public function __construct(EntityManagerInterface $entityManager, LanguageRepositoryInterface $languageRepository)
    {
        parent::__construct($entityManager);

        $this->languageRepository = $languageRepository;
    }

    public function getCurrent(string $id): CurrentUser
    {
        $qb = $this->getEntityManager()->getConnection()->createQueryBuilder();

        $this->createBaseQueryBuilder($qb);

        $qb->addSelect('u.secret as user_secret', 'u.role as user_role')
            ->addSelect('language.title as language_title', 'language.locale as language_locale')
            ->andWhere($qb->expr()->eq('u.id', ':user_id'))
            ->setParameter('user_id', $id)
        ;

        $data = $qb->execute()->fetch();

        if (false === $data) {
            throw new NoResultException();
        }

        $currentLanguage = new CurrentLanguage(strval($data['language_id']), strval($data['language_title']), strval($data['language_locale']));

        $user = new CurrentUser(strval($data['user_id']), boolval($data['user_image_defined']), strval($data['user_name']), strval($data['user_name_slug']), strval($data['user_gender']), strval($data['user_secret']), strval($data['user_role']), new DateTime(strval($data['user_created_at'])), $currentLanguage);

        $this->languageRepository->populateUserReadingLanguages($user);

        return $user;
    }

    public function getOne(UserGetQuery $query): UserFull
    {
        $qb = $this->getEntityManager()->getConnection()->createQueryBuilder();

        $this->createBaseQueryBuilder($qb);

        $qb->andWhere($qb->expr()->eq('u.id', ':user_id'))
            ->setParameter('user_id', $query->id)
        ;

        $data = $qb->execute()->fetch();

        if (false === $data) {
            throw new NoResultException();
        }

        $language = new LanguageMedium(strval($data['language_id']));

        return new UserFull(strval($data['user_id']), boolval($data['user_image_defined']), strval($data['user_name']), strval($data['user_name_slug']), strval($data['user_gender']), new DateTime(strval($data['user_created_at'])), $language);
    }

    public function getOneForUpdate(UserGetForUpdateQuery $query): UserForUpdate
    {
        $qb = $this->getEntityManager()->getConnection()->createQueryBuilder();

        $this->createBaseQueryBuilder($qb);

        $qb->addSelect('u.email as user_email')
            ->andWhere($qb->expr()->eq('u.id', ':user_id'))
            ->setParameter('user_id', $query->id)
        ;

        $data = $qb->execute()->fetch();

        if (false === $data) {
            throw new NoResultException();
        }

        $language = new LanguageMedium(strval($data['language_id']));

        $user = new UserForUpdate(strval($data['user_id']), boolval($data['user_image_defined']), strval($data['user_name']), strval($data['user_name_slug']), strval($data['user_gender']), strval($data['user_email']), new DateTime(strval($data['user_created_at'])), $language);

        $this->languageRepository->populateUserReadingLanguages($user);

        return $user;
    }

    private function createBaseQueryBuilder(QueryBuilder $qb): void
    {
        $qb->select('u.id as user_id', 'u.image_defined as user_image_defined', 'u.name as user_name', 'u.name_slug as user_name_slug', 'u.gender as user_gender', 'u.created_at as user_created_at')
            ->from('usr_user', 'u')
            ->addSelect('language.id as language_id')
            ->join('u', 'lng_language', 'language', $qb->expr()->eq('language.id', 'u.language_id'))
            ->where($qb->expr()->eq('u.status', ':user_status'))
            ->setParameter('user_status', UserStatus::ACTIVATED)
        ;
    }
}
