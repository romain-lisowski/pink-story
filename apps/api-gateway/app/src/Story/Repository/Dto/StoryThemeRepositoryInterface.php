<?php

declare(strict_types=1);

namespace App\Story\Repository\Dto;

use App\Language\Model\LanguageInterface;
use App\Story\Query\StoryThemeSearchQuery;
use Doctrine\Common\Collections\Collection;

interface StoryThemeRepositoryInterface
{
    public function search(StoryThemeSearchQuery $query): Collection;

    public function populateStoryImages(Collection $storyImages, LanguageInterface $language): void;
}
