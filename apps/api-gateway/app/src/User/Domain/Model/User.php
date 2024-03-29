<?php

declare(strict_types=1);

namespace App\User\Domain\Model;

use App\Common\Domain\File\ImageableInterface;
use App\Common\Domain\File\ImageableTrait;
use App\Common\Domain\Model\AbstractEntity;
use App\Language\Domain\Model\Language;
use App\Language\Domain\Model\LanguageableInterface;
use App\Language\Domain\Repository\LanguageNoResultException;
use App\Language\Domain\Repository\LanguageRepositoryInterface;
use App\Language\Domain\Repository\ReadingLanguageNoResultException;
use App\Story\Domain\Model\Story;
use App\Story\Domain\Model\StoryRating;
use App\User\Domain\Security\UserPasswordEncoderInterface;
use App\User\Infrastructure\Validator\Constraint as AppUserAssert;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\String\Slugger\AsciiSlugger;
use Symfony\Component\String\UnicodeString;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity(repositoryClass="App\User\Infrastructure\Doctrine\Repository\UserDoctrineORMRepository")
 * @ORM\Table(name="usr_user")
 * @UniqueEntity(
 *      fields = {"email"}
 * )
 * @UniqueEntity(
 *      fields = {"passwordForgottenSecret"}
 * )
 */
class User extends AbstractEntity implements UserInterface, UserableInterface, ImageableInterface, LanguageableInterface
{
    use ImageableTrait;

    /**
     * @ORM\Column(name="gender", type="string")
     * @Assert\NotBlank
     * @Assert\Choice(callback={"App\User\Domain\Model\UserGender", "getChoices"})
     */
    private string $gender;

    /**
     * @ORM\Column(name="name", type="string")
     * @Assert\NotBlank
     */
    private string $name;

    /**
     * @ORM\Column(name="name_slug", type="string")
     * @Assert\NotBlank
     */
    private string $nameSlug;

    /**
     * @ORM\Column(name="email", type="string", unique=true)
     * @Assert\NotBlank
     * @AppUserAssert\Email
     */
    private string $email;

    /**
     * @ORM\Column(name="email_validated", type="boolean")
     * @Assert\NotNull
     */
    private bool $emailValidated;

    /**
     * @ORM\Column(name="email_validation_code", type="string")
     * @Assert\NotBlank
     * @Assert\Regex("/^([0-9]{6})$/")
     */
    private string $emailValidationCode;

    /**
     * @ORM\Column(name="email_validation_code_used", type="boolean")
     * @Assert\NotNull
     */
    private bool $emailValidationCodeUsed;

    /**
     * @ORM\Column(name="password", type="string")
     * @Assert\NotBlank
     */
    private string $password;

    /**
     * @ORM\Column(name="password_forgotten_secret", type="guid", unique=true)
     * @Assert\NotBlank
     * @Assert\Uuid
     */
    private string $passwordForgottenSecret;

    /**
     * @ORM\Column(name="password_forgotten_secret_used", type="boolean")
     * @Assert\NotNull
     */
    private bool $passwordForgottenSecretUsed;

    /**
     * @ORM\Column(name="password_forgotten_secret_created_at", type="datetime")
     * @Assert\NotBlank
     */
    private \DateTime $passwordForgottenSecretCreatedAt;

    /**
     * @Assert\NotNull
     * @ORM\Column(name="image_defined", type="boolean")
     */
    private bool $imageDefined;

    /**
     * @ORM\Column(name="role", type="string")
     * @Assert\NotBlank
     * @Assert\Choice(callback={"App\User\Domain\Model\UserRole", "getChoices"})
     */
    private string $role;

    /**
     * @ORM\Column(name="status", type="string")
     * @Assert\NotBlank
     * @Assert\Choice(callback={"App\User\Domain\Model\UserStatus", "getChoices"})
     */
    private string $status;

    /**
     * @ORM\ManyToOne(targetEntity="App\Language\Domain\Model\Language", inversedBy="users")
     * @ORM\JoinColumn(name="language_id", referencedColumnName="id", nullable=false)
     */
    private Language $language;

    /**
     * @ORM\OneToMany(targetEntity="App\User\Domain\Model\UserHasReadingLanguage", mappedBy="user", cascade={"persist", "remove"}, orphanRemoval=true)
     */
    private Collection $userHasReadingLanguages;

    /**
     * @ORM\OneToMany(targetEntity="App\User\Domain\Model\AccessToken", mappedBy="user", cascade={"remove"})
     */
    private Collection $accessTokens;

    /**
     * @ORM\OneToMany(targetEntity="App\Story\Domain\Model\Story", mappedBy="user", cascade={"remove"})
     */
    private Collection $stories;

    /**
     * @ORM\OneToMany(targetEntity="App\Story\Domain\Model\StoryRating", mappedBy="user", cascade={"remove"})
     */
    private Collection $storyRatings;

    public function __construct()
    {
        parent::__construct();

        // init values
        $this->regenerateEmailValidationCode();
        $this->regeneratePasswordForgottenSecret(true); // not claimed by user at account creation, so block it by forcing true  until new claim
        $this->userHasReadingLanguages = new ArrayCollection();
        $this->accessTokens = new ArrayCollection();
        $this->stories = new ArrayCollection();
        $this->storyRatings = new ArrayCollection();
    }

    public function getGender(): string
    {
        return $this->gender;
    }

    public function setGender(string $gender): self
    {
        $this->gender = $gender;

        return $this;
    }

    public function updateGender(string $gender): self
    {
        $this->setGender($gender);
        $this->updateLastUpdatedAt();

        return $this;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        $slugger = new AsciiSlugger();
        $this->nameSlug = $slugger->slug($name)->lower()->toString();

        return $this;
    }

    public function updateName(string $name): self
    {
        $this->setName($name);
        $this->updateLastUpdatedAt();

        return $this;
    }

    public function getNameSlug(): string
    {
        return $this->nameSlug;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function setEmail(string $email): self
    {
        $this->email = (new UnicodeString($email))->lower()->toString();

        return $this;
    }

    public function updateEmail(string $email): self
    {
        $this->setEmail($email);
        $this->regenerateEmailValidationCode();
        $this->updateLastUpdatedAt();

        return $this;
    }

    public function isEmailValidated(): bool
    {
        return $this->emailValidated;
    }

    public function setEmailValidated(bool $validated): self
    {
        $this->emailValidated = $validated;

        return $this;
    }

    public function validateEmail(): self
    {
        $this->setEmailValidated(true);
        $this->setEmailValidationCodeUsed(true);
        $this->updateLastUpdatedAt();

        return $this;
    }

    public function getEmailValidationCode(): string
    {
        return $this->emailValidationCode;
    }

    public function setEmailValidationCode(string $code): self
    {
        $this->emailValidationCode = $code;

        return $this;
    }

    public function regenerateEmailValidationCode(): self
    {
        $this->setEmailValidationCode(sprintf('%06d', random_int(0, 999999)));
        $this->setEmailValidationCodeUsed(false);
        $this->setEmailValidated(false);
        $this->updateLastUpdatedAt();

        return $this;
    }

    public function isEmailValidationCodeUsed(): bool
    {
        return $this->emailValidationCodeUsed;
    }

    public function setEmailValidationCodeUsed(bool $used): self
    {
        $this->emailValidationCodeUsed = $used;

        return $this;
    }

    public function getPassword(): string
    {
        return $this->password;
    }

    public function setPassword(string $plainPassword, UserPasswordEncoderInterface $passwordEncoder): self
    {
        $this->password = $passwordEncoder->encodePassword($plainPassword);

        return $this;
    }

    public function updatePassword(string $plainPassword, UserPasswordEncoderInterface $passwordEncoder): self
    {
        $this->setPassword($plainPassword, $passwordEncoder);
        $this->setPasswordForgottenSecretUsed(true);
        $this->updateLastUpdatedAt();

        return $this;
    }

    public function getPasswordForgottenSecret(): string
    {
        return $this->passwordForgottenSecret;
    }

    public function setPasswordForgottenSecret(string $secret): self
    {
        $this->passwordForgottenSecret = $secret;

        return $this;
    }

    public function regeneratePasswordForgottenSecret(bool $used = false): self
    {
        $this->setPasswordForgottenSecret(Uuid::v4()->toRfc4122());
        $this->setPasswordForgottenSecretUsed($used);
        $this->setPasswordForgottenSecretCreatedAt(new \DateTime());
        $this->updateLastUpdatedAt();

        return $this;
    }

    public function isPasswordForgottenSecretUsed(): bool
    {
        return $this->passwordForgottenSecretUsed;
    }

    public function setPasswordForgottenSecretUsed(bool $used): self
    {
        $this->passwordForgottenSecretUsed = $used;

        return $this;
    }

    public function getPasswordForgottenSecretCreatedAt(): \DateTime
    {
        return $this->passwordForgottenSecretCreatedAt;
    }

    public function setPasswordForgottenSecretCreatedAt(\DateTime $date): self
    {
        $this->passwordForgottenSecretCreatedAt = $date;

        return $this;
    }

    public function isImageDefined(): bool
    {
        return $this->imageDefined;
    }

    public function setImageDefined(bool $imageDefined): self
    {
        $this->imageDefined = $imageDefined;

        return $this;
    }

    public function updateImageDefined(bool $imageDefined): self
    {
        $this->setImageDefined($imageDefined);
        $this->updateLastUpdatedAt();

        return $this;
    }

    public function getRole(): string
    {
        return $this->role;
    }

    public function setRole(string $role): self
    {
        $this->role = $role;

        return $this;
    }

    public function updateRole(string $role): self
    {
        $this->setRole($role);
        $this->updateLastUpdatedAt();

        return $this;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus(string $status): self
    {
        $this->status = $status;

        return $this;
    }

    public function updateStatus(string $status): self
    {
        $this->setStatus($status);
        $this->updateLastUpdatedAt();

        return $this;
    }

    public function getLanguage(): Language
    {
        return $this->language;
    }

    public function setLanguage(Language $language): self
    {
        $this->language = $language;
        $language->addUser($this);

        return $this;
    }

    public function updateLanguage(Language $language): self
    {
        $this->language->removeUser($this);
        $this->setLanguage($language);
        $this->updateLastUpdatedAt();

        return $this;
    }

    public function getUserHasReadingLanguages(): Collection
    {
        return $this->userHasReadingLanguages;
    }

    public function addUserHasReadingLanguage(UserHasReadingLanguage $userHasReadingLanguage): self
    {
        $this->userHasReadingLanguages[] = $userHasReadingLanguage;
        $this->updateLastUpdatedAt();

        return $this;
    }

    public function removeUserHasReadingLanguage(UserHasReadingLanguage $userHasReadingLanguage): self
    {
        $this->userHasReadingLanguages->removeElement($userHasReadingLanguage);
        $this->updateLastUpdatedAt();

        return $this;
    }

    public function getReadingLanguages(): Collection
    {
        $readingLanguages = array_values(array_map(function (UserHasReadingLanguage $userHasReadingLanguage) {
            return $userHasReadingLanguage->getLanguage();
        }, $this->userHasReadingLanguages->toArray()));

        return new ArrayCollection($readingLanguages);
    }

    public function addReadingLanguage(Language $language): self
    {
        $exists = false;

        foreach ($this->getUserHasReadingLanguages() as $userHasReadingLanguage) {
            if ($userHasReadingLanguage->getLanguage()->getId() === $language->getId()) {
                $exists = true;

                break;
            }
        }

        if (false === $exists) {
            (new UserHasReadingLanguage())
                ->setUser($this)
                ->setLanguage($language)
            ;
        }

        return $this;
    }

    /**
     * @throws ReadingLanguageNoResultException
     */
    public function addReadingLanguages(array $readingLanguageIds, LanguageRepositoryInterface $languageRepository): self
    {
        try {
            foreach ($readingLanguageIds as $readingLanguageId) {
                if (false === Uuid::isValid($readingLanguageId)) {
                    throw new ReadingLanguageNoResultException();
                }

                $readingLanguage = $languageRepository->findOne($readingLanguageId);
                $this->addReadingLanguage($readingLanguage);
            }

            return $this;
        } catch (LanguageNoResultException $e) {
            throw new ReadingLanguageNoResultException($e);
        }
    }

    public function cleanReadingLanguages(array $readingLanguageIds): self
    {
        foreach ($this->userHasReadingLanguages as $userHasReadingLanguage) {
            if (false === in_array($userHasReadingLanguage->getLanguage()->getId(), $readingLanguageIds)) {
                $this->removeUserHasReadingLanguage($userHasReadingLanguage);
            }
        }

        return $this;
    }

    public function updateReadingLanguages(array $readingLanguageIds, LanguageRepositoryInterface $languageRepository): self
    {
        $this->addReadingLanguages($readingLanguageIds, $languageRepository);
        $this->cleanReadingLanguages($readingLanguageIds);

        return $this;
    }

    public function getAccessTokens(): Collection
    {
        return $this->accessTokens;
    }

    public function addAccessToken(AccessToken $accessToken): self
    {
        $this->accessTokens[] = $accessToken;
        $this->updateLastUpdatedAt();

        return $this;
    }

    public function removeAccessToken(AccessToken $accessToken): self
    {
        $this->accessTokens->removeElement($accessToken);
        $this->updateLastUpdatedAt();

        return $this;
    }

    public function getStories(): Collection
    {
        return $this->stories;
    }

    public function addStory(Story $story): self
    {
        $this->stories[] = $story;
        $this->updateLastUpdatedAt();

        return $this;
    }

    public function removeStory(Story $story): self
    {
        $this->stories->removeElement($story);
        $this->updateLastUpdatedAt();

        return $this;
    }

    public function getStoryRatings(): Collection
    {
        return $this->storyRatings;
    }

    public function addStoryRating(StoryRating $storyRating): self
    {
        $this->storyRatings[] = $storyRating;
        $this->updateLastUpdatedAt();

        return $this;
    }

    public function removeStoryRating(StoryRating $storyRating): self
    {
        $this->storyRatings->removeElement($storyRating);
        $this->updateLastUpdatedAt();

        return $this;
    }

    public function getUser(): UserInterface
    {
        return $this;
    }

    public function hasImage(): bool
    {
        return $this->isImageDefined();
    }

    public static function getImageBasePath(): string
    {
        return 'user';
    }
}
