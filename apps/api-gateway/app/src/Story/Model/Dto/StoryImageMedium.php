<?php

declare(strict_types=1);

namespace App\Story\Model\Dto;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Symfony\Component\Serializer\Annotation as Serializer;

final class StoryImageMedium extends StoryImage
{
    /**
     * @Serializer\Groups({"serializer"})
     */
    private string $title;

    /**
     * @Serializer\Groups({"serializer"})
     */
    private string $titleSlug;

    /**
     * @Serializer\Groups({"serializer"})
     */
    private Collection $storyThemes;

    public function __construct(string $id = '', string $title = '', string $titleSlug = '')
    {
        parent::__construct($id);

        $this->title = $title;
        $this->titleSlug = $titleSlug;
        $this->storyThemes = new ArrayCollection();
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function getTitleSlug(): string
    {
        return $this->titleSlug;
    }

    public function getStoryThemes(): Collection
    {
        return $this->storyThemes;
    }
}
