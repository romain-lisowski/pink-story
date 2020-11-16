<?php

declare(strict_types=1);

namespace App\Story\Repository;

use App\Story\Entity\StoryTheme;

interface StoryThemeRepositoryInterface
{
    public function findOne(string $id): StoryTheme;
}
