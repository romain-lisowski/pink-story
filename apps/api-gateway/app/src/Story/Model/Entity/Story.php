<?php

declare(strict_types=1);

namespace App\Story\Model\Entity;

use App\Language\Model\Entity\Language;
use App\Language\Model\Entity\LanguageableInterface;
use App\Language\Model\Entity\LanguageableTrait;
use App\Model\DepthableInterface;
use App\Model\DepthableTrait;
use App\Model\Entity\AbstractEntity;
use App\Model\Entity\PositionableInterface;
use App\Model\Entity\PositionableTrait;
use App\Story\Exception\StoryThemeDepthException;
use App\User\Model\Entity\User;
use App\User\Model\UserEditableInterface;
use App\User\Model\UserEditableTrait;
use App\User\Model\UserInterface;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use InvalidArgumentException;
use Symfony\Component\Serializer\Annotation as Serializer;
use Symfony\Component\String\Slugger\AsciiSlugger;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Table(name="sty_story")
 * @ORM\Entity(repositoryClass="App\Story\Repository\Entity\StoryRepository")
 */
class Story extends AbstractEntity implements UserEditableInterface, LanguageableInterface, DepthableInterface, PositionableInterface
{
    use UserEditableTrait;
    use LanguageableTrait;
    use DepthableTrait;
    use PositionableTrait;

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
     * @Serializer\Groups({"serializer"})
     * @Assert\NotBlank
     * @ORM\Column(name="content", type="text")
     */
    private string $content;

    /**
     * @Serializer\Groups({"serializer"})
     * @ORM\ManyToOne(targetEntity="App\User\Model\Entity\User", inversedBy="stories")
     * @ORM\JoinColumn(name="user_id", referencedColumnName="id", nullable=false)
     */
    private UserInterface $user;

    /**
     * @Serializer\Groups({"serializer"})
     * @ORM\ManyToOne(targetEntity="App\Language\Model\Entity\Language", inversedBy="stories")
     * @ORM\JoinColumn(name="language_id", referencedColumnName="id", nullable=false)
     */
    private Language $language;

    /**
     * @ORM\ManyToOne(targetEntity="App\Story\Model\Entity\Story", inversedBy="children")
     * @ORM\JoinColumn(name="parent_id", referencedColumnName="id")
     */
    private ?DepthableInterface $parent;

    /**
     * @ORM\OneToMany(targetEntity="App\Story\Model\Entity\Story", mappedBy="parent", cascade={"remove"})
     * @ORM\OrderBy({"position" = "ASC"})
     */
    private Collection $children;

    /**
     * @Serializer\Groups({"serializer"})
     * @ORM\ManyToOne(targetEntity="App\Story\Model\Entity\StoryImage", inversedBy="stories")
     * @ORM\JoinColumn(name="story_image_id", referencedColumnName="id")
     */
    private ?StoryImage $storyImage;

    /**
     * @ORM\OneToMany(targetEntity="App\Story\Model\Entity\StoryHasStoryTheme", mappedBy="story", cascade={"persist", "remove"}, orphanRemoval=true)
     */
    private Collection $storyHasStoryThemes;

    /**
     * @ORM\OneToMany(targetEntity="App\Story\Model\Entity\StoryRating", mappedBy="story", cascade={"remove"})
     */
    private Collection $storyRatings;

    public function __construct(string $title = '', string $content = '', User $user, Language $language, ?Story $parent = null, ?StoryImage $storyImage = null)
    {
        parent::__construct();

        // init zero values
        $this->title = '';
        $this->titleSlug = '';
        $this->content = '';
        $this->parent = null;
        $this->position = 1;
        $this->children = new ArrayCollection();
        $this->storyImage = null;
        $this->storyHasStoryThemes = new ArrayCollection();
        $this->storyRatings = new ArrayCollection();

        // init values
        $this->setTitle($title)
            ->setContent($content)
            ->setUser($user)
            ->setLanguage($language)
            ->setParent($parent)
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

    public function getContent(): string
    {
        return $this->content;
    }

    public function setContent(string $content): self
    {
        $this->content = $content;

        return $this;
    }

    public function updateContent(string $content): self
    {
        $this->setContent($content);

        return $this;
    }

    public function setUser(UserInterface $user): self
    {
        if (!$user instanceof User) {
            throw new InvalidArgumentException();
        }

        $this->user = $user;
        $user->addStory($this);

        return $this;
    }

    public function setLanguage(Language $language): self
    {
        $this->language = $language;
        $language->addStory($this);

        return $this;
    }

    public function updateLanguage(Language $language): self
    {
        $this->language->removeStory($this);

        $this->setLanguage($language);

        return $this;
    }

    public function getStoryImage(): ?StoryImage
    {
        return $this->storyImage;
    }

    public function setStoryImage(?StoryImage $storyImage): self
    {
        $this->storyImage = $storyImage;

        if (null !== $storyImage) {
            $storyImage->addStory($this);
        }

        return $this;
    }

    public function updateStoryImage(?StoryImage $storyImage): self
    {
        if (null !== $this->storyImage) {
            $this->storyImage->removeStory($this);
        }

        $this->setStoryImage($storyImage);

        return $this;
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

    public function addStoryTheme(StoryTheme $storyTheme): self
    {
        if ($storyTheme->getChildren()->count() > 0) {
            throw new StoryThemeDepthException();
        }

        new StoryHasStoryTheme($this, $storyTheme);

        return $this;
    }

    public function getStoryRatings(): Collection
    {
        return $this->storyRatings;
    }

    public function addStoryRating(StoryRating $storyRating): self
    {
        $this->storyRatings[] = $storyRating;

        return $this;
    }

    public function removeStoryRating(StoryRating $storyRating): self
    {
        $this->storyRatings->removeElement($storyRating);

        return $this;
    }
}