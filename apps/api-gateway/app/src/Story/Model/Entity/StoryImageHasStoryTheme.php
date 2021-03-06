<?php

declare(strict_types=1);

namespace App\Story\Model\Entity;

use App\Model\EditableInterface;
use App\Model\EditableTrait;
use App\Model\Entity\AbstractEntity;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

/**
 * @ORM\Table(name="sty_story_image_has_story_theme", uniqueConstraints={@ORM\UniqueConstraint(name="UNIQ_STORY_IMAGE_HAS_STORY_THEME", columns={"story_image_id", "story_theme_id"})})
 * @ORM\Entity(repositoryClass="App\Story\Repository\Entity\StoryImageHasStoryThemeRepository")
 * @UniqueEntity(
 *      fields = {"storyImage", "storyTheme"}
 * )
 */
class StoryImageHasStoryTheme extends AbstractEntity implements EditableInterface
{
    use EditableTrait;

    /**
     * @ORM\ManyToOne(targetEntity="App\Story\Model\Entity\StoryImage", inversedBy="storyImageHasStoryThemes")
     * @ORM\JoinColumn(name="story_image_id", referencedColumnName="id", nullable=false)
     */
    private StoryImage $storyImage;

    /**
     * @ORM\ManyToOne(targetEntity="App\Story\Model\Entity\StoryTheme", inversedBy="storyImageHasStoryThemes")
     * @ORM\JoinColumn(name="story_theme_id", referencedColumnName="id", nullable=false)
     */
    private StoryTheme $storyTheme;

    public function __construct(StoryImage $storyImage, StoryTheme $storyTheme)
    {
        parent::__construct();

        // init values
        $this->setStoryImage($storyImage)
            ->setStoryTheme($storyTheme)
        ;
    }

    public function getStoryImage(): StoryImage
    {
        return $this->storyImage;
    }

    public function setStoryImage(StoryImage $storyImage): self
    {
        $this->storyImage = $storyImage;
        $storyImage->addStoryImageHasStoryTheme($this);

        return $this;
    }

    public function getStoryTheme(): StoryTheme
    {
        return $this->storyTheme;
    }

    public function setStoryTheme(StoryTheme $storyTheme): self
    {
        $this->storyTheme = $storyTheme;
        $storyTheme->addStoryImageHasStoryTheme($this);

        return $this;
    }
}
