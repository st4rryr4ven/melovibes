<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use App\Controller\ReviewCreateController;
use App\Repository\ReviewRepository;
use DateTimeImmutable;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: ReviewRepository::class)]
#[ORM\Table(
    name: "review",
    uniqueConstraints: [
        new ORM\UniqueConstraint(name: "unique_review_per_user_per_music", columns: ["author_id", "music_id"])
    ]
)]
#[UniqueEntity(
    fields: ['author', 'music'],
    message: 'Vous avez déjà posté un avis pour cette musique.'
)]
#[ApiResource(
    operations: [
        new Get(requirements: ['id' => '\d+']),
        new Post(
            controller: ReviewCreateController::class,
            denormalizationContext: ['groups' => ['review:write']],
            validationContext: ['groups' => ['review:write']]
        ),
        new Patch(
            inputFormats: ['json' => ['application/merge-patch+json']],
            denormalizationContext: ['groups' => ['serialization:review:update']],
            security: "is_granted('ROLE_ADMIN') or object.getAuthor() == user",
            validationContext: ['groups' => ['validation:review:update']]
        ),
        new Delete(
            security: "is_granted('ROLE_ADMIN') or object.getAuthor() == user"
        )
    ],
    normalizationContext: ['groups' => ['review:read']]
)]
class Review
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['music:read'])]
    private ?int $id = null;

    /**
     * Music which was reviewed
     */
    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['review:read', 'review:write'])]
    private ?Music $music = null;

    /**
     * The user who wrote the review
     */
    #[ORM\ManyToOne(inversedBy: 'reviews')]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['review:read', 'music:read'])]
    #[ApiProperty(readableLink: true)]
    private ?User $author = null;

    /**
     * Comment of the review
     */
    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Groups(['review:read', 'review:write', 'music:read'])]
    private ?string $comment = null;

    /**
     * Rating of the music, from 0 to 5 stars
     */
    #[ORM\Column(nullable: false)]
    #[Assert\NotNull]
    #[Assert\Range(
        notInRangeMessage: 'La note doit être comprise entre {{ min }} et {{ max }}.',
        min: 0,
        max: 5
    )]
    #[Groups(['review:read', 'review:write', 'music:read'])]
    private ?int $rating = null;

    /**
     * When the review was posted
     */
    #[ORM\Column]
    #[Groups(['review:read', 'music:read'])]
    private ?DateTimeImmutable $createdAt;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getMusic(): ?Music
    {
        return $this->music;
    }

    public function setMusic(?Music $music): static
    {
        $this->music = $music;

        return $this;
    }

    public function getAuthor(): ?User
    {
        return $this->author;
    }

    public function setAuthor(?User $author): static
    {
        $this->author = $author;

        return $this;
    }

    public function getComment(): ?string
    {
        return $this->comment;
    }

    public function setComment(?string $comment): static
    {
        $this->comment = $comment;
        return $this;
    }

    public function getRating(): ?int
    {
        return $this->rating;
    }

    public function setRating(int $rating): static
    {
        $this->rating = $rating;

        return $this;
    }

    public function getCreatedAt(): ?DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(DateTimeImmutable $createdAt): static
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function __construct()
    {
        $this->createdAt = new DateTimeImmutable();
    }

}
