<?php

declare(strict_types=1);

namespace App\Story\Model\Entity;

use App\Model\EditableInterface;
use App\Model\EditableTrait;
use App\Language\Model\Entity\AbstractTranslation;
use App\Language\Model\Entity\Language;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation as Serializer;
use Symfony\Component\String\Slugger\AsciiSlugger;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Table(name="sty_story_image_translation")
 * @ORM\Entity(repositoryClass="App\Story\Repository\Entity\StoryImageTranslationRepository")
 */
class StoryImageTranslation extends AbstractTranslation implements EditableInterface
{
    use EditableTrait;

    /**
     * @Serializer\Groups({"serializer"})
     * @Assert\NotBlank
     * @ORM\Column(name="title", type="string", length=255)
     */
    private string $title;

    /**
     * @Serializer\Groups({"serializer"})
     * @Assert\NotBlank
     * @ORM\Column(name="title_slug", type="string", length=255)
     */
    private string $titleSlug;

    /**
     * @ORM\ManyToOne(targetEntity="App\Story\Model\Entity\StoryImage", inversedBy="storyImageTranslations")
     * @ORM\JoinColumn(name="story_image_id", referencedColumnName="id", nullable=false)
     */
    private StoryImage $storyImage;

    /**
     * @ORM\ManyToOne(targetEntity="App\Language\Model\Entity\Language", inversedBy="storyImageTranslations")
     * @ORM\JoinColumn(name="language_id", referencedColumnName="id", nullable=false)
     */
    private Language $language;

    public function __construct(string $title = '', StoryImage $storyImage, Language $language)
    {
        parent::__construct($language);

        // init zero values
        $this->title = '';
        $this->titleSlug = '';

        // init values
        $this->setTitle($title)
            ->setStoryImage($storyImage)
        ;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setTitle(string $title): self
    {
        $this->title = $title;

        $slugger = new AsciiSlugger();
        $this->titleSlug = $slugger->slug($title)->lower()->toString();

        return $this;
    }

    public function updateTitle(string $title): self
    {
        $this->setTitle($title);

        return $this;
    }

    public function getTitleSlug(): string
    {
        return $this->titleSlug;
    }

    public function getStoryImage(): StoryImage
    {
        return $this->storyImage;
    }

    public function setStoryImage(StoryImage $storyImage): self
    {
        $this->storyImage = $storyImage;
        $storyImage->addStoryImageTranslation($this);

        return $this;
    }

    public function setLanguage(Language $language): self
    {
        $this->language = $language;
        $language->addStoryImageTranslation($this);

        return $this;
    }

    public function updateLanguage(Language $language): self
    {
        $this->language->removeStoryImageTranslation($this);

        $this->setLanguage($language);

        return $this;
    }
}