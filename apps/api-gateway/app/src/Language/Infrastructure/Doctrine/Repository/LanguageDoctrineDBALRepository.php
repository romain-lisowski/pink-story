<?php

declare(strict_types=1);

namespace App\Language\Infrastructure\Doctrine\Repository;

use App\Common\Infrastructure\Doctrine\Repository\AbstractDoctrineDBALRepository;
use App\Language\Query\Model\Language;
use App\Language\Query\Model\LanguageCurrent;
use App\Language\Query\Model\LanguageFull;
use App\Language\Query\Model\LanguageMedium;
use App\Language\Query\Query\LanguageSearchQuery;
use App\Language\Query\Repository\LanguageRepositoryInterface;
use App\User\Query\Model\UserMedium;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\Criteria;

final class LanguageDoctrineDBALRepository extends AbstractDoctrineDBALRepository implements LanguageRepositoryInterface
{
    public function search(LanguageSearchQuery $query): Collection
    {
        $qb = $this->createQueryBuilder();

        $qb->select('id', 'title', 'locale')
            ->from('lng_language')
        ;

        $qb->orderBy('locale', Criteria::ASC);

        $datas = $qb->execute()->fetchAllAssociative();

        $languages = new ArrayCollection();

        foreach ($datas as $data) {
            $language = new LanguageFull(strval($data['id']), strval($data['title']), strval($data['locale']));
            $languages->add($language);
        }

        return $languages;
    }

    public function findOneByLocaleForCurrent(string $locale): ?LanguageCurrent
    {
        $qb = $this->createQueryBuilder();

        $qb->select('id', 'title', 'locale')
            ->from('lng_language')
        ;

        $qb->where($qb->expr()->eq('locale', ':locale'))
            ->setParameter('locale', $locale)
        ;

        $data = $qb->execute()->fetchAssociative();

        if (false === $data) {
            return null;
        }

        return new LanguageCurrent(strval($data['id']), strval($data['title']), strval($data['locale']));
    }

    public function findOneByAccessTokenForCurrent(string $accessTokenId): ?LanguageCurrent
    {
        $qb = $this->createQueryBuilder();

        $qb->select('language.id as language_id', 'language.title as language_title', 'language.locale as language_locale')
            ->from('lng_language', 'language')
            ->join('language', 'usr_user', 'u', $qb->expr()->eq('u.language_id', 'language.id'))
            ->join('u', 'usr_access_token', 'accessToken', $qb->expr()->eq('accessToken.user_id', 'u.id'))
        ;

        $qb->where($qb->expr()->eq('accessToken.id', ':access_token_id'))
            ->setParameter('access_token_id', $accessTokenId)
        ;

        $data = $qb->execute()->fetchAssociative();

        if (false === $data) {
            return null;
        }

        return new LanguageCurrent(strval($data['language_id']), strval($data['language_title']), strval($data['language_locale']));
    }

    public function populateUserReadingLanguages(UserMedium $user, string $languageClass = Language::class): void
    {
        $qb = $this->createQueryBuilder();

        $qb->select('language.id as language_id')
            ->from('lng_language', 'language')
            ->join('language', 'usr_user_has_reading_language', 'userHasReadingLanguage', $qb->expr()->and(
                $qb->expr()->eq('userHasReadingLanguage.language_id', 'language.id'),
                $qb->expr()->eq('userHasReadingLanguage.user_id', ':user_id')
            ))
            ->setParameter('user_id', $user->getId())
        ;

        if (true === in_array($languageClass, [LanguageMedium::class, LanguageFull::class, LanguageCurrent::class])) {
            $qb->addSelect('language.title as language_title', 'language.locale as language_locale');
        }

        $qb->orderBy('language.locale', Criteria::ASC);

        $datas = $qb->execute()->fetchAllAssociative();

        foreach ($datas as $data) {
            if (true === in_array($languageClass, [LanguageMedium::class, LanguageFull::class, LanguageCurrent::class])) {
                $user->addReadingLanguage(new $languageClass(strval($data['language_id']), strval($data['language_title']), strval($data['language_locale'])));
            } else {
                $user->addReadingLanguage(new $languageClass(strval($data['language_id'])));
            }
        }
    }
}
