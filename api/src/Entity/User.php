<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;
use ApiPlatform\Metadata\Delete;
use App\Controller\CurrentUserController;
use App\Repository\UserRepository;
use App\State\UserProcessor;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\Validator\Constraints\UserPassword;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\Table(name: 'user')]
#[ORM\UniqueConstraint(name: 'UNIQ_LOGIN', columns: ['login'])]
#[ORM\UniqueConstraint(name: 'UNIQ_EMAIL', columns: ['email'])]
#[UniqueEntity(fields: ['login'], message: "Ce nom d'utilisateur est déjà utilisé.")]
#[UniqueEntity(fields: ['email'], message: "Cet e-mail est déjà utilisé.")]
#[ApiResource(
    operations: [
        new GetCollection(
            security: "is_granted('ROLE_ADMIN')"
        ),
        new Get(
            security: "(is_granted('ROLE_USER') and object == user) or is_granted('ROLE_ADMIN')"
        ),
        new Post(
            uriTemplate: '/users/register',
            denormalizationContext: ['groups' => ['serialization:user:create']],
            validationContext: ['groups' => ['Default', 'validation:user:create']],
            processor: UserProcessor::class
        ),
        new Patch(
            denormalizationContext: ['groups' => ['serialization:user:update']],
            security: "(is_granted('ROLE_USER') and object == user) or is_granted('ROLE_ADMIN')",
            validationContext: ['groups' => ['Default', 'validation:user:update']],
            processor: UserProcessor::class
        ),

        new Delete(
            security: "(is_granted('ROLE_USER') and object == user) or is_granted('ROLE_ADMIN')"
        ),
    ],
    normalizationContext: ['groups' => ['user:read']],
)]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    /**
     * Unique identifier of the user
     */
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['user:read'])]
    private ?int $id = null;

    /**
     * Unique username
     */
    #[ORM\Column(length: 30)]
    #[Assert\NotBlank(groups: ['validation:user:create'])]
    #[Assert\Length(min: 4, max: 30, groups: ['validation:user:create'])]
    #[Groups(['user:read', 'serialization:user:create'])]
    private ?string $login = null;

    /**
     * Hashed password
     */
    #[ORM\Column]
    private string $password;

    /**
     * Plain password (not persisted)
     */
    #[Assert\NotBlank(message: 'Le mot de passe est obligatoire.', groups: ['validation:user:create', 'validation:user:update'])]
    #[Assert\Length(min: 8, minMessage: "Le mot de passe doit contenir au moins 8 caractères.", groups: ['validation:user:create', 'validation:user:update'])]
    #[Groups(['serialization:user:create', 'serialization:user:update'])]
    private ?string $plainPassword = null;

    #[UserPassword(message: "Mot de passe est incorrect.", groups: ['validation:user:update:password'])]
    #[Groups(['serialization:user:update'])]
    private ?string $currentPlainPassword = null;

    /**
     * User email address
     */
    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(groups: ['validation:user:create'])]
    #[Assert\Email(groups: ['validation:user:create', 'validation:user:update'])]
    #[Groups(['user:read', 'serialization:user:create', 'serialization:user:update'])]
    private ?string $email = null;

    /**
     * User roles
     */
    #[ORM\Column(type: 'json')]
    #[Groups(['user:read', 'serialization:user:update:admin', 'me:read'])]
    private array $roles = ['ROLE_USER'];

    /**
     * @var Collection<int, Review>
     */
    #[ORM\OneToMany(targetEntity: Review::class, mappedBy: 'author')]
    private Collection $reviews;

    public function __construct()
    {
        $this->reviews = new ArrayCollection();
    }

    /**
     * Security methods
     */
    public function getUserIdentifier(): string
    {
        return $this->login;
    }

    /**
     * Returns user roles
     */
    public function getRoles(): array
    {
        return array_unique(array_merge($this->roles, ['ROLE_USER']));
    }

    public function eraseCredentials(): void
    {
        $this->plainPassword = null;
        $this->currentPlainPassword = null;
    }

    /**
     * Getters
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    public function getLogin(): string
    {
        return $this->login;
    }

    /**
     * Returns hashed password
     */
    public function getPassword(): string
    {
        return $this->password;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function getPlainPassword(): ?string
    {
        return $this->plainPassword;
    }

    /**
     * Setters
     */
    public function setLogin(string $login): self
    {
        $this->login = $login;
        return $this;
    }

    public function setEmail(string $email): static
    {
        $this->email = $email;
        return $this;
    }

    /**
     * Set hashed password
     */
    public function setPassword(string $password): self
    {
        $this->password = $password;

        return $this;
    }

    public function setRoles(array $roles): self
    {
        $this->roles = $roles;

        return $this;
    }

    public function setPlainPassword(string $plainPassword): self
    {
        $this->plainPassword = $plainPassword;
        return $this;
    }

    public function addRole(string $role): self
    {
        if (!in_array($role, $this->roles)) {
            $this->roles[] = $role;
        }
        return $this;
    }

    public function removeRole(string $role): self
    {
        $this->roles = array_filter($this->roles, fn($r) => $r !== $role);
        return $this;
    }

    /**
     * @return Collection<int, Review>
     */
    public function getReviews(): Collection
    {
        return $this->reviews;
    }

    public function addReview(Review $review): static
    {
        if (!$this->reviews->contains($review)) {
            $this->reviews->add($review);
            $review->setAuthor($this);
        }

        return $this;
    }

    public function removeReview(Review $review): static
    {
        if ($this->reviews->removeElement($review)) {
            // set the owning side to null (unless already changed)
            if ($review->getAuthor() === $this) {
                $review->setAuthor(null);
            }
        }

        return $this;
    }

}
