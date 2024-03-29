<?php

declare(strict_types=1);

namespace App\Story\Query\Model;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

final class StoryThemeFullParent extends StoryThemeFull
{
    private Collection $children;

    public function __construct()
    {
        // init values
        $this->children = new ArrayCollection();
    }

    public function getChildren(): Collection
    {
        return $this->children;
    }

    public function addChild(StoryTheme $child): self
    {
        $this->children[] = $child;

        return $this;
    }
}
