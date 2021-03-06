<?php

declare(strict_types=1);

namespace App\Story\Model\Entity;

use App\Language\Model\Entity\TranslatableInterface;
use App\Language\Model\Entity\TranslatableTrait;
use App\Model\EditableInterface;
use App\Model\EditableTrait;
use App\Model\Entity\AbstractEntity;
use App\Model\Entity\DepthableInterface;
use App\Model\Entity\DepthableTrait;
use App\Model\Entity\PositionableInterface;
use App\Model\Entity\PositionableTrait;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Table(name="sty_story_theme")
 * @ORM\Entity(repositoryClass="App\Story\Repository\Entity\StoryThemeRepository")
 */
class StoryTheme extends AbstractEntity implements DepthableInterface, PositionableInterface, TranslatableInterface, EditableInterface
{
    use DepthableTrait;
    use PositionableTrait;
    use TranslatableTrait;
    use EditableTrait;

    /**
     * @ORM\ManyToOne(targetEntity="App\Story\Model\Entity\StoryTheme", inversedBy="children")
     * @ORM\JoinColumn(name="parent_id", referencedColumnName="id")
     */
    private ?DepthableInterface $parent;

    /**
     * @ORM\OneToMany(targetEntity="App\Story\Model\Entity\StoryTheme", mappedBy="parent", cascade={"remove"})
     * @ORM\OrderBy({"position" = "ASC"})
     */
    private Collection $children;

    /**
     * @ORM\OneToMany(targetEntity="App\Story\Model\Entity\StoryThemeTranslation", mappedBy="storyTheme", cascade={"persist", "remove"}, orphanRemoval=true)
     */
    private Collection $storyThemeTranslations;

    /**
     * @ORM\OneToMany(targetEntity="App\Story\Model\Entity\StoryHasStoryTheme", mappedBy="storyTheme", cascade={"remove"}, orphanRemoval=true)
     */
    private Collection $storyHasStoryThemes;

    /**
     * @ORM\OneToMany(targetEntity="App\Story\Model\Entity\StoryImageHasStoryTheme", mappedBy="storyTheme", cascade={"remove"}, orphanRemoval=true)
     */
    private Collection $storyImageHasStoryThemes;

    public function __construct(string $reference = '', ?StoryTheme $parent = null, ?int $position = null)
    {
        parent::__construct();

        // init zero values
        $this->reference = '';
        $this->parent = null;
        $this->position = $position;
        $this->children = new ArrayCollection();
        $this->storyThemeTranslations = new ArrayCollection();
        $this->storyHasStoryThemes = new ArrayCollection();
        $this->storyImageHasStoryThemes = new ArrayCollection();

        // init values
        $this->setReference($reference)
            ->setParent($parent)
        ;
    }

    public function getStoryThemeTranslations(): Collection
    {
        return $this->storyThemeTranslations;
    }

    public function addStoryThemeTranslation(StoryThemeTranslation $storyThemeTranslation): self
    {
        $this->storyThemeTranslations[] = $storyThemeTranslation;

        return $this;
    }

    public function removeStoryThemeTranslation(StoryThemeTranslation $storyThemeTranslation): self
    {
        $this->storyThemeTranslations->removeElement($storyThemeTranslation);

        return $this;
    }

    public function getTranslations(): Collection
    {
        return $this->getStoryThemeTranslations();
    }

    public function getStoryHasStoryThemes(): Collection
    {
        return $this->storyHasStoryThemes;
    }

    public function addStoryHasStoryTheme(StoryHasStoryTheme $storyHasStoryTheme): self
    {
        $this->storyHasStoryThemes[] = $storyHasStoryTheme;

        return $this;
    }

    public function removeStoryHasStoryTheme(StoryHasStoryTheme $storyHasStoryTheme): self
    {
        $this->storyHasStoryThemes->removeElement($storyHasStoryTheme);

        return $this;
    }

    public function getStoryImageHasStoryThemes(): Collection
    {
        return $this->storyImageHasStoryThemes;
    }

    public function addStoryImageHasStoryTheme(StoryImageHasStoryTheme $storyImageHasStoryTheme): self
    {
        $this->storyImageHasStoryThemes[] = $storyImageHasStoryTheme;

        return $this;
    }

    public function removeStoryImageHasStoryTheme(StoryImageHasStoryTheme $storyImageHasStoryTheme): self
    {
        $this->storyImageHasStoryThemes->removeElement($storyImageHasStoryTheme);

        return $this;
    }
}
