<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use App\Repository\UserRepository;
use App\State\UserProcessor;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Application user entity.
 *
 * The user authenticates with an immutable email address.
 * A public username (login) is used for display purposes and can be updated by the user.
 */
#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\Table(name: 'user')]
#[ORM\UniqueConstraint(name: 'UNIQ_LOGIN', columns: ['login'])]
#[ORM\UniqueConstraint(name: 'UNIQ_EMAIL', columns: ['email'])]
#[UniqueEntity(fields: ['login'], message: "Ce nom d'utilisateur est déjà utilisé.")]
#[UniqueEntity(fields: ['email'], message: "Cet e-mail est déjà utilisé.")]
#[ApiResource(
    operations: [
        new GetCollection(security: "is_granted('ROLE_ADMIN')"),
        new Get(security: "is_granted('USER_VIEW', object)"),
        new Post(
            uriTemplate: '/users/register',
            denormalizationContext: ['groups' => ['serialization:user:create']],
            validationContext: ['groups' => ['Default', 'validation:user:create']],
            processor: UserProcessor::class
        ),
        new Patch(
            inputFormats: [
                'json' => ['application/merge-patch+json'],
            ],
            denormalizationContext: ['groups' => ['serialization:user:update']],
            security: "is_granted('USER_EDIT', object)",
            validationContext: ['groups' => ['Default', 'validation:user:update']],
            processor: UserProcessor::class
        ),
        new Patch(
            uriTemplate: '/users/{id}/favorites',
            inputFormats: [
                'json' => ['application/merge-patch+json'],
            ],
            denormalizationContext: ['groups' => ['serialization:user:update:favorites']],
            security: "is_granted('USER_EDIT', object)",
        ),
        new Delete(
            security: "is_granted('USER_DELETE', object)",
            processor: UserProcessor::class
        )
    ],
    normalizationContext: ['groups' => ['user:read', 'music:lite']],
)]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    /**
     * Unique identifier of the user.
     */
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['user:read'])]
    private ?int $id = null;

    /**
     * Public username displayed in the application.
     */
    #[ORM\Column(length: 30)]
    #[Assert\NotBlank(groups: ['validation:user:create', 'validation:user:update'])]
    #[Assert\Length(min: 4, max: 30, groups: ['validation:user:create', 'validation:user:update'])]
    #[Groups(['user:read', 'serialization:user:create', 'serialization:user:update', 'music:read'])]
    private ?string $login = null;

    /**
     * Hashed password.
     */
    #[ORM\Column]
    private string $password;

    /**
     * Plain password used for hashing (not persisted).
     */
    #[Assert\NotBlank(message: 'Le mot de passe est obligatoire.', groups: ['validation:user:create'])]
    #[Assert\Length(min: 8, minMessage: "Le mot de passe doit contenir au moins 8 caractères.", groups: ['validation:user:create'])]
    #[Groups(['serialization:user:create', 'serialization:user:update'])]
    private ?string $plainPassword = null;

    /**
     * Current plain password used to authorize profile updates (not persisted).
     */
    #[Assert\NotBlank(message: 'Le mot de passe est obligatoire pour faire les maj sur la compte.', groups: ['validation:user:update'])]
    #[Groups(['serialization:user:update'])]
    private ?string $currentPlainPassword = null;

    /**
     * Immutable email address used as the authentication identifier.
     */
    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(groups: ['validation:user:create'])]
    #[Assert\Email(groups: ['validation:user:create', 'validation:user:update'])]
    #[Groups(['user:read', 'serialization:user:create'])]
    private ?string $email = null;

    /**
     * User roles.
     *
     * @var array<int, string>
     */
    #[ORM\Column(type: 'json')]
    #[Groups(['user:read', 'serialization:user:update:admin', 'me:read'])]
    private array $roles = ['ROLE_USER'];

    /**
     * Reviews authored by the user.
     *
     * @var Collection<int, Review>
     */
    #[ORM\OneToMany(targetEntity: Review::class, mappedBy: 'author', orphanRemoval: true)]
    #[Groups(['user:read'])]
    #[ApiProperty(readableLink: true)]
    private Collection $reviews;

    /**
     * User favorite music.
     *
     * @var Collection<int, Music>
     */
    #[ORM\ManyToMany(targetEntity: Music::class)]
    #[Groups(['user:read'])]
    #[ApiProperty(readableLink: true)]
    private Collection $favoriteMusic;

    /**
     * Create a new user instance.
     */
    public function __construct()
    {
        $this->reviews = new ArrayCollection();
        $this->favoriteMusic = new ArrayCollection();
    }

    /**
     * Returns the unique identifier for authentication.
     */
    public function getUserIdentifier(): string
    {
        return $this->email ?? $this->login ?? '';
    }

    /**
     * Returns the list of roles granted to the user.
     *
     * @return array<int, string>
     */
    public function getRoles(): array
    {
        return array_unique(array_merge($this->roles, ['ROLE_USER']));
    }

    /**
     * Clears any transient sensitive data.
     */
    public function eraseCredentials(): void
    {
        $this->plainPassword = null;
        $this->currentPlainPassword = null;
    }

    /**
     * Returns the database identifier.
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * Returns the public username.
     */
    public function getLogin(): string
    {
        return $this->login ?? '';
    }

    /**
     * Returns the hashed password.
     */
    public function getPassword(): string
    {
        return $this->password;
    }

    /**
     * Returns the immutable email address.
     */
    public function getEmail(): string
    {
        return $this->email ?? '';
    }

    /**
     * Returns the plain password (not persisted).
     */
    public function getPlainPassword(): ?string
    {
        return $this->plainPassword;
    }

    /**
     * Sets the public username.
     */
    public function setLogin(string $login): self
    {
        $this->login = $login;

        return $this;
    }

    /**
     * Sets the immutable email address.
     */
    public function setEmail(string $email): static
    {
        $this->email = $email;

        return $this;
    }

    /**
     * Sets the hashed password.
     */
    public function setPassword(string $password): self
    {
        $this->password = $password;

        return $this;
    }

    /**
     * Replaces the user roles.
     *
     * @param array<int, string> $roles
     */
    public function setRoles(array $roles): self
    {
        $this->roles = $roles;

        return $this;
    }

    /**
     * Sets the plain password (not persisted).
     */
    public function setPlainPassword(string $plainPassword): self
    {
        $this->plainPassword = $plainPassword;

        return $this;
    }

    /**
     * Adds a role to the user.
     */
    public function addRole(string $role): self
    {
        if (!in_array($role, $this->roles, true)) {
            $this->roles[] = $role;
        }

        return $this;
    }

    /**
     * Removes a role from the user.
     */
    public function removeRole(string $role): self
    {
        $this->roles = array_values(array_filter($this->roles, static fn ($r) => $r !== $role));

        return $this;
    }

    /**
     * Returns the reviews authored by the user.
     *
     * @return Collection<int, Review>
     */
    public function getReviews(): Collection
    {
        return $this->reviews;
    }

    /**
     * Adds a review authored by the user.
     */
    public function addReview(Review $review): static
    {
        if (!$this->reviews->contains($review)) {
            $this->reviews->add($review);
            $review->setAuthor($this);
        }

        return $this;
    }

    /**
     * Removes a review authored by the user.
     */
    public function removeReview(Review $review): static
    {
        if ($this->reviews->removeElement($review)) {
            if ($review->getAuthor() === $this) {
                $review->setAuthor(null);
            }
        }

        return $this;
    }

    /**
     * Returns the current plain password (not persisted).
     */
    public function getCurrentPlainPassword(): ?string
    {
        return $this->currentPlainPassword;
    }

    /**
     * Sets the current plain password (not persisted).
     */
    public function setCurrentPlainPassword(?string $currentPlainPassword): self
    {
        $this->currentPlainPassword = $currentPlainPassword;

        return $this;
    }

    /**
     * Returns the user's favorite music.
     *
     * @return Collection<int, Music>
     */
    public function getFavoriteMusic(): Collection
    {
        return $this->favoriteMusic;
    }

    /**
     * Adds a music to the user's favorites.
     */
    public function addFavoriteMusic(Music $music): self
    {
        if (!$this->favoriteMusic->contains($music)) {
            $this->favoriteMusic->add($music);
        }

        return $this;
    }

    /**
     * Removes a music from the user's favorites.
     */
    public function removeFavoriteMusic(Music $music): self
    {
        $this->favoriteMusic->removeElement($music);

        return $this;
    }
}
