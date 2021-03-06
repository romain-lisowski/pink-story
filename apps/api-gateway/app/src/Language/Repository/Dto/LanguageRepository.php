<?php

declare(strict_types=1);

namespace App\Language\Repository\Dto;

use App\Language\Model\Dto\CurrentLanguage;
use App\Language\Model\Dto\LanguageFull;
use App\Language\Model\Dto\LanguageMedium;
use App\Language\Query\LanguageSearchQuery;
use App\Repository\Dto\AbstractRepository;
use App\User\Model\Dto\User;
use App\User\Model\Dto\UserReadingLanguageableInterface;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Query\QueryBuilder;
use Doctrine\ORM\NoResultException;
use InvalidArgumentException;

final class LanguageRepository extends AbstractRepository implements LanguageRepositoryInterface
{
    public function getCurrentByLocale(string $locale): CurrentLanguage
    {
        $qb = $this->getEntityManager()->getConnection()->createQueryBuilder();

        $this->createBaseQueryBuilder($qb);

        $qb->andWhere($qb->expr()->eq('locale', ':locale'))
            ->setParameter('locale', $locale)
        ;

        $data = $qb->execute()->fetch();

        if (false === $data) {
            throw new NoResultException();
        }

        return new CurrentLanguage(strval($data['id']), strval($data['title']), strval($data['locale']));
    }

    public function getBySearch(LanguageSearchQuery $query): Collection
    {
        $qb = $this->getEntityManager()->getConnection()->createQueryBuilder();

        $this->createBaseQueryBuilder($qb);

        $languageDatas = $qb->execute()->fetchAll();

        $languages = new ArrayCollection();

        foreach ($languageDatas as $languageData) {
            $language = new LanguageFull(strval($languageData['id']), strval($languageData['title']), strval($languageData['locale']));
            $languages->add($language);
        }

        return $languages;
    }

    public function populateUserReadingLanguages(User $user): void
    {
        if (!$user instanceof UserReadingLanguageableInterface) {
            throw new InvalidArgumentException();
        }

        $qb = $this->getEntityManager()->getConnection()->createQueryBuilder();

        $qb->select('language.id as language_id')
            ->from('lng_language', 'language')
            ->join('language', 'usr_user_has_reading_language', 'userHasReadingLanguage', $qb->expr()->andX(
                $qb->expr()->eq('userHasReadingLanguage.language_id', 'language.id'),
                $qb->expr()->eq('userHasReadingLanguage.user_id', ':user_id')
            ))
            ->setParameter('user_id', $user->getId())
        ;

        $datas = $qb->execute()->fetchAll();

        foreach ($datas as $data) {
            $readingLanguage = new LanguageMedium(strval($data['language_id']));
            $user->addReadingLanguage($readingLanguage);
        }
    }

    private function createBaseQueryBuilder(QueryBuilder $qb): void
    {
        $qb->select('id', 'title', 'locale')
            ->from('lng_language')
        ;
    }
}
