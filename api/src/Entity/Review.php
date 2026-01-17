<?php

namespace App\Entity;

use ApiPlatform\Doctrine\Orm\Filter\SearchFilter;
use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
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
#[ApiFilter(SearchFilter::class, properties: [
    'author' => 'exact',
    'music' => 'exact',
])]
#[UniqueEntity(
    fields: ['author', 'music'],
    message: 'Vous avez déjà posté un avis pour cette musique.'
)]
#[ApiResource(
    operations: [
        new Get(requirements: ['id' => '\d+']),
        new GetCollection(),
        new Post(
            controller: ReviewCreateController::class,
            denormalizationContext: ['groups' => ['review:write']],
            security: "is_granted('ROLE_USER')",
            validationContext: ['groups' => ['review:write']]
        ),
        new Patch(
            inputFormats: ['json' => ['application/merge-patch+json']],
            denormalizationContext: ['groups' => ['review:update']],
            security: "is_granted('REVIEW_EDIT', object)",
            validationContext: ['groups' => ['review:update']]
        ),
        new Delete(
            security: "is_granted('REVIEW_DELETE', object)"
        )
    ],
    normalizationContext: ['groups' => ['review:read']]
)]
class Review
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['review:read', 'music:read', 'user:read'])]
    private ?int $id = null;

    /**
     * Music which was reviewed
     */
    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['review:read', 'review:write'])]
    #[ApiProperty(readableLink: true)]
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
    #[Groups(['review:read', 'review:write', 'review:update', 'music:read'])]
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
    #[Groups(['review:read', 'review:write', 'review:update', 'music:read'])]
    private ?int $rating = null;

    #[ORM\Column(nullable: true)]
    #[Assert\Range(min: 0, max: 5)]
    #[Groups(['review:read', 'review:write', 'review:update', 'music:read'])]
    private ?int $melodyRating = null;

    #[ORM\Column(nullable: true)]
    #[Assert\Range(min: 0, max: 5)]
    #[Groups(['review:read', 'review:write', 'review:update', 'music:read'])]
    private ?int $lyricsRating = null;

    #[ORM\Column(nullable: true)]
    #[Assert\Range(min: 0, max: 5)]
    #[Groups(['review:read', 'review:write', 'review:update', 'music:read'])]
    private ?int $vocalsRating = null;

    #[ORM\Column(nullable: true)]
    #[Assert\Range(min: 0, max: 5)]
    #[Groups(['review:read', 'review:write', 'review:update', 'music:read'])]
    private ?int $impactRating = null;

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

    #[Groups(['review:read'])]
    public function getMusicTitle(): ?string
    {
        return $this->music?->getTitle();
    }

    #[Groups(['review:read'])]
    public function getMusicId(): ?int
    {
        return $this->music?->getId();
    }

    public function getMelodyRating(): ?int
    {
        return $this->melodyRating;
    }

    public function setMelodyRating(?int $melodyRating): void
    {
        $this->melodyRating = $melodyRating;
    }

    public function getLyricsRating(): ?int
    {
        return $this->lyricsRating;
    }

    public function setLyricsRating(?int $lyricsRating): void
    {
        $this->lyricsRating = $lyricsRating;
    }

    public function getVocalsRating(): ?int
    {
        return $this->vocalsRating;
    }

    public function setVocalsRating(?int $vocalsRating): void
    {
        $this->vocalsRating = $vocalsRating;
    }

    public function getImpactRating(): ?int
    {
        return $this->impactRating;
    }

    public function setImpactRating(?int $impactRating): void
    {
        $this->impactRating = $impactRating;
    }

}
