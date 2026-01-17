<?php

namespace App\Entity;

use ApiPlatform\Doctrine\Orm\Filter\BooleanFilter;
use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use App\Api\Action\AlbumTracksAction;
use App\Api\Action\MusicImportSpotifyTrackAction;
use App\Api\Action\MusicNewReleasesAction;
use App\Api\Action\MusicSearchAction;
use App\Repository\MusicRepository;
use App\State\MusicProcessor;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: MusicRepository::class)]
#[ORM\Table(name: 'music')]
#[ORM\UniqueConstraint(name: 'UNIQ_SPOTIFY_ID', columns: ['spotify_id'])]
#[UniqueEntity(fields: ['spotifyId'], message: 'This spotifyId is already used.')]
#[ApiFilter(BooleanFilter::class, properties: ['isValidated'])]
#[ApiResource(
    operations: [
        new Get(
            requirements: ['id' => '\d+'],
            security: "is_granted('ROLE_USER') or object.getIsValidated() == true"
        ),
        new GetCollection(security: "is_granted('ROLE_USER')"),
        new GetCollection(
            uriTemplate: '/music/search',
            controller: MusicSearchAction::class,
            paginationEnabled: false,
            output: false,
            read: false,
            deserialize: false
        ),
        new GetCollection(
            uriTemplate: '/music/new-releases',
            controller: MusicNewReleasesAction::class,
            paginationEnabled: false,
            output: false,
            read: false,
            deserialize: false
        ),
        new GetCollection(
            uriTemplate: '/albums/spotify/{spotifyAlbumId}/tracks',
            controller: AlbumTracksAction::class,
            paginationEnabled: false,
            output: false,
            read: false,
            deserialize: false
        ),
        new Post(
            uriTemplate: '/music/import/spotify/{spotifyTrackId}',
            controller: MusicImportSpotifyTrackAction::class,
            output: false,
            read: false,
            deserialize: false,
            validate: false
        ),
        new Post(
            denormalizationContext: ['groups' => ['serialization:music:create']],
            security: "is_granted('ROLE_USER')",
            validationContext: ['groups' => ['validation:music:create']],
            processor: MusicProcessor::class
        ),
        new Patch(
            inputFormats: ['json' => ['application/merge-patch+json']],
            denormalizationContext: ['groups' => ['serialization:music:update']],
            security: "is_granted('ROLE_ADMIN')",
            validationContext: ['groups' => ['validation:music:update']]
        ),
        new Delete(security: "is_granted('ROLE_ADMIN')")
    ],
    normalizationContext: ['groups' => ['music:read']]
)]
class Music
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['music:read', 'music:lite'])]
    private ?int $id = null;

    #[ORM\Column(length: 64, unique: true, nullable: true)]
    #[Groups(['music:read', 'music:lite'])]
    private ?string $spotifyId = null;

    #[ORM\Column(length: 64, nullable: true)]
    private ?string $importSource = null;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $importedAt = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotNull]
    #[Assert\NotBlank(message: 'La musique doit avoir un titre.', groups: ['validation:music:create', 'validation:music:update'])]
    #[Groups(['music:read', 'music:lite', 'review:read', 'serialization:music:create', 'serialization:music:update'])]
    private ?string $title = null;

    /**
     * @var Collection<int, Artist>
     */
    #[ORM\ManyToMany(targetEntity: Artist::class, inversedBy: 'music')]
    #[Assert\NotNull(groups: ['validation:music:create', 'validation:music:update'])]
    #[Assert\Count(min: 1, minMessage: 'Une musique doit avoir au moins un artiste.', groups: ['validation:music:create', 'validation:music:update'])]
    #[Groups(['music:read', 'serialization:music:create', 'serialization:music:update'])]
    #[ApiProperty(readableLink: true)]
    private Collection $artists;

    #[ORM\Column(nullable: true)]
    #[Assert\Count(min: 1, minMessage: 'Une musique doit avoir au moins un genre.', groups: ['validation:music:create', 'validation:music:update'])]
    #[Groups(['music:read', 'serialization:music:create', 'serialization:music:update'])]
    private ?array $genre = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['music:read', 'music:lite', 'serialization:music:create', 'serialization:music:update'])]
    private ?string $picture = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Assert\Url(groups: ['validation:music:create', 'validation:music:update'])]
    #[Groups(['music:read', 'music:lite', 'serialization:music:create', 'serialization:music:update'])]
    private ?string $link = null;

    #[ORM\Column]
    #[Groups([
        'music:read',
        'music:admin:read',
        'serialization:music:update'
    ])]
    private ?bool $isValidated = false;

    #[ORM\Column(nullable: true)]
    private ?array $requestJSON = null;

    #[ORM\Column(nullable: true)]
    #[Groups(['music:read', 'music:lite', 'serialization:music:create'])]
    private ?int $popularity = null;

    /**
     * @var Collection<int, Review>
     */
    #[ORM\OneToMany(targetEntity: Review::class, mappedBy: 'music')]
    #[Groups(['music:read'])]
    #[ApiProperty(readableLink: true)]
    private Collection $reviews;

    public function __construct()
    {
        $this->artists = new ArrayCollection();
        $this->isValidated = false;
        $this->reviews = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getSpotifyId(): ?string
    {
        return $this->spotifyId;
    }

    public function setSpotifyId(?string $spotifyId): static
    {
        $this->spotifyId = $spotifyId;
        return $this;
    }

    public function getImportSource(): ?string
    {
        return $this->importSource;
    }

    public function setImportSource(?string $importSource): static
    {
        $this->importSource = $importSource;
        return $this;
    }

    public function getImportedAt(): ?\DateTimeImmutable
    {
        return $this->importedAt;
    }

    public function setImportedAt(?\DateTimeImmutable $importedAt): static
    {
        $this->importedAt = $importedAt;
        return $this;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(string $title): static
    {
        $this->title = $title;
        return $this;
    }

    public function getArtists(): Collection
    {
        return $this->artists;
    }

    public function addArtist(Artist $artist): static
    {
        if (!$this->artists->contains($artist)) $this->artists->add($artist);
        return $this;
    }

    public function removeArtist(Artist $artist): static
    {
        $this->artists->removeElement($artist);
        return $this;
    }

    public function getGenre(): ?array
    {
        return $this->genre;
    }

    public function setGenre(?array $genre): static
    {
        $this->genre = $genre;
        return $this;
    }

    public function getPicture(): ?string
    {
        return $this->picture;
    }

    public function setPicture(?string $picture): static
    {
        $this->picture = $picture;
        return $this;
    }

    public function getLink(): ?string
    {
        return $this->link;
    }

    public function setLink(?string $link): static
    {
        $this->link = $link;
        return $this;
    }

    public function getIsValidated(): ?bool
    {
        return $this->isValidated;
    }

    public function setIsValidated(bool $isValidated): static
    {
        $this->isValidated = $isValidated;
        return $this;
    }

    public function getRequestJSON(): ?array
    {
        return $this->requestJSON;
    }

    public function setRequestJSON(?array $requestJSON): static
    {
        $this->requestJSON = $requestJSON;
        return $this;
    }

    public function getPopularity(): ?int
    {
        return $this->popularity;
    }

    public function setPopularity(int $popularity): static
    {
        $this->popularity = $popularity;
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
            $review->setMusic($this);
        }

        return $this;
    }

    public function removeReview(Review $review): static
    {
        if ($this->reviews->removeElement($review)) {
            if ($review->getMusic() === $this) {
                $review->setMusic(null);
            }
        }

        return $this;
    }
}
