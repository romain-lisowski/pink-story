<?php

declare(strict_types=1);

namespace App\User\Entity;

use App\Entity\AbstractEntity;
use App\File\ImageableInterface;
use App\File\ImageableTrait;
use App\Story\Entity\Story;
use App\User\Validator\Constraints as AppUserAssert;
use DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\String\Slugger\AsciiSlugger;
use Symfony\Component\String\UnicodeString;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Table(name="usr_user")
 * @ORM\Entity(repositoryClass="App\User\Repository\UserRepository")
 * @UniqueEntity(
 *      fields = {"email"}
 * )
 */
class User extends AbstractEntity implements UserInterface, UserableInterface, ImageableInterface
{
    use ImageableTrait;

    /**
     * @Groups({"medium", "full"})
     * @Assert\NotBlank
     * @ORM\Column(name="name", type="string", length=255)
     */
    private string $name;

    /**
     * @Groups({"medium", "full"})
     * @Assert\NotBlank
     * @ORM\Column(name="name_slug", type="string", length=255)
     */
    private string $nameSlug;

    /**
     * @Groups({"full"})
     * @Assert\NotBlank
     * @AppUserAssert\Email
     * @ORM\Column(name="email", type="string", length=255, unique=true)
     */
    private string $email;

    /**
     * @Assert\NotNull
     * @ORM\Column(name="email_validated", type="boolean")
     */
    private bool $emailValidated;

    /**
     * @Assert\NotBlank
     * @ORM\Column(name="email_validation_secret", type="guid", unique=true)
     */
    private string $emailValidationSecret;

    /**
     * @Assert\NotNull
     * @ORM\Column(name="email_validation_secret_used", type="boolean")
     */
    private bool $emailValidationSecretUsed;

    /**
     * @Assert\NotBlank
     * @ORM\Column(name="password", type="string", length=255)
     */
    private string $password;

    /**
     * @Assert\NotBlank
     * @ORM\Column(name="password_forgotten_secret", type="guid", unique=true)
     */
    private string $passwordForgottenSecret;

    /**
     * @Assert\NotNull
     * @ORM\Column(name="password_forgotten_secret_used", type="boolean")
     */
    private bool $passwordForgottenSecretUsed;

    /**
     * @Assert\NotBlank
     * @ORM\Column(name="password_forgotten_secret_created_at", type="datetime")
     */
    private DateTime $passwordForgottenSecretCreatedAt;

    /**
     * @Assert\NotBlank
     * @ORM\Column(name="secret", type="guid")
     */
    private string $secret;

    /**
     * @Assert\NotBlank
     * @ORM\Column(name="role", type="string", length=255)
     */
    private string $role;

    /**
     * @Assert\NotNull
     * @ORM\Column(name="image_defined", type="boolean")
     */
    private bool $imageDefined;

    /**
     * @ORM\OneToMany(targetEntity="App\Story\Entity\Story", mappedBy="user", cascade={"remove"})
     * @ORM\OrderBy({"title" = "ASC"})
     */
    private Collection $stories;

    public function __construct(string $name = '', string $email = '', string $role = UserRole::ROLE_USER)
    {
        parent::__construct();

        // init zero values
        $this->name = '';
        $this->nameSlug = '';
        $this->email = '';
        $this->emailValidated = false;
        $this->emailValidationSecret = Uuid::v4()->toRfc4122();
        $this->emailValidationSecretUsed = false;
        $this->password = '';
        $this->passwordForgottenSecret = Uuid::v4()->toRfc4122();
        $this->passwordForgottenSecretUsed = true; // not claimed by user at account creation, so block it until new claim
        $this->passwordForgottenSecretCreatedAt = new DateTime();
        $this->secret = Uuid::v4()->toRfc4122();
        $this->role = UserRole::ROLE_USER;
        $this->imageDefined = false;
        $this->stories = new ArrayCollection();

        // init values
        $this->setName($name)
            ->setEmail($email)
            ->setRole($role)
        ;
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

    public function rename(string $name): self
    {
        $this->setName($name);

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
        $this->regenerateEmailValidationSecret();

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
        $this->setEmailValidationSecretUsed(true);

        return $this;
    }

    public function getEmailValidationSecret(): string
    {
        return $this->emailValidationSecret;
    }

    public function setEmailValidationSecret(string $secret): self
    {
        $this->emailValidationSecret = $secret;

        return $this;
    }

    public function regenerateEmailValidationSecret(): self
    {
        $this->setEmailValidationSecret(Uuid::v4()->toRfc4122());
        $this->setEmailValidationSecretUsed(false);
        $this->setEmailValidated(false);

        return $this;
    }

    public function isEmailValidationSecretUsed(): bool
    {
        return $this->emailValidationSecretUsed;
    }

    public function setEmailValidationSecretUsed(bool $used): self
    {
        $this->emailValidationSecretUsed = $used;

        return $this;
    }

    public function getPassword(): string
    {
        return $this->password;
    }

    public function setPassword(string $password): self
    {
        $this->password = $password;

        return $this;
    }

    public function updatePassword(string $password): self
    {
        $this->setPassword($password);
        $this->setPasswordForgottenSecretUsed(true);

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

    public function regeneratePasswordForgottenSecret(): self
    {
        $this->setPasswordForgottenSecret(Uuid::v4()->toRfc4122());
        $this->setPasswordForgottenSecretUsed(false);
        $this->initPasswordForgottenSecretCreatedAt();

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

    public function getPasswordForgottenSecretCreatedAt(): DateTime
    {
        return $this->passwordForgottenSecretCreatedAt;
    }

    public function setPasswordForgottenSecretCreatedAt(DateTime $date): self
    {
        $this->passwordForgottenSecretCreatedAt = $date;

        return $this;
    }

    public function initPasswordForgottenSecretCreatedAt(): self
    {
        $this->setPasswordForgottenSecretCreatedAt(new DateTime());

        return $this;
    }

    public function getSecret(): string
    {
        return $this->secret;
    }

    public function setSecret(string $secret): self
    {
        $this->secret = $secret;

        return $this;
    }

    public function regenerateSecret(): self
    {
        $this->setSecret(Uuid::v4()->toRfc4122());

        return $this;
    }

    public function getRole(): string
    {
        return $this->role;
    }

    public function setRole($role): self
    {
        $this->role = $role;

        return $this;
    }

    public function hasImageDefined(): bool
    {
        return $this->imageDefined;
    }

    public function setImageDefined(bool $imageDefined): self
    {
        $this->imageDefined = $imageDefined;

        return $this;
    }

    public function getUser(): User
    {
        return $this;
    }

    public function setUser(User $user): self
    {
        return $this;
    }

    public function hasImage(): bool
    {
        return $this->imageDefined;
    }

    public function removeImage(): self
    {
        $this->setImageDefined(false);

        return $this;
    }

    public function getImageBasePath(): string
    {
        return '/user';
    }

    public function getUsername(): string
    {
        return $this->email;
    }

    public function getRoles(): array
    {
        return [$this->role];
    }

    public function getSalt(): ?string
    {
        // not needed with the current algorithm in security.yaml
        return null;
    }

    public function eraseCredentials(): void
    {
    }

    public function getStories(): Collection
    {
        return $this->stories;
    }

    public function addStory(Story $story): self
    {
        $this->stories[] = $story;

        return $this;
    }

    public function removeStory(Story $story): self
    {
        $this->stories->remove($story);

        return $this;
    }
}
