<?php

declare(strict_types=1);

namespace App\Language\Repository\Dto;

use App\Language\Model\Dto\CurrentLanguage;
use App\Language\Model\Dto\LanguageFull;
use App\Language\Query\LanguageSearchQuery;
use App\Repository\Dto\AbstractRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\NoResultException;

final class LanguageRepository extends AbstractRepository implements LanguageRepositoryInterface
{
    public function findCurrentByLocale(string $locale): CurrentLanguage
    {
        $qb = $this->getEntityManager()->getConnection()->createQueryBuilder();

        $qb->select('id', 'title', 'locale')
            ->from('lng_language', 'language')
            ->where($qb->expr()->eq('activated', ':activated'))
            ->setParameter('activated', true)
        ;

        $qb->where($qb->expr()->eq('locale', ':locale'))
            ->setParameter('locale', $locale)
        ;

        $data = $qb->execute()->fetch();

        if (false === $data) {
            throw new NoResultException();
        }

        return new CurrentLanguage(strval($data['id']), strval($data['title']), strval($data['locale']));
    }

    public function search(LanguageSearchQuery $query): Collection
    {
        $qb = $this->getEntityManager()->getConnection()->createQueryBuilder();

        $qb->select('id', 'title', 'locale')
            ->from('lng_language')
            ->where($qb->expr()->eq('activated', ':activated'))
            ->setParameter('activated', true)
        ;

        $languageDatas = $qb->execute()->fetchAll();

        $languages = new ArrayCollection();

        foreach ($languageDatas as $languageData) {
            $language = new LanguageFull(strval($languageData['id']), strval($languageData['title']), strval($languageData['locale']));
            $languages->add($language);
        }

        return $languages;
    }

    public function findIds(): array
    {
        $qb = $this->getEntityManager()->getConnection()->createQueryBuilder();

        $qb->select('id')
            ->from('lng_language')
            ->where($qb->expr()->eq('activated', ':activated'))
            ->setParameter('activated', true)
        ;

        $datas = $qb->execute()->fetchAll();

        return array_map(function ($data) {
            return $data['id'];
        }, $datas);
    }
}
