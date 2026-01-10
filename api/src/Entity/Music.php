<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\ApiProperty;
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
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;
use ApiPlatform\Doctrine\Orm\Filter\BooleanFilter;
use ApiPlatform\Metadata\ApiFilter;

#[ORM\Entity(repositoryClass: MusicRepository::class)]
#[ORM\Table(name: 'music')]
#[ORM\UniqueConstraint(name: 'UNIQ_SPOTIFY_ID', columns: ['spotify_id'])]
#[UniqueEntity(fields: ['spotifyId'], message: 'This spotifyId is already used.')]
#[ApiFilter(BooleanFilter::class, properties: ['isValidated'])]
#[ApiResource(
    operations: [
        new Get(
            requirements: ['id' => '\\d+'],
        ),
        new GetCollection(
//            security: "is_granted('ROLE_ADMIN')"
        ),
        new GetCollection(
            uriTemplate: '/music/search',
            controller: MusicSearchAction::class,
            paginationEnabled: false,
            output: false,
            read: false,
            deserialize: false,
        ),
        new GetCollection(
            uriTemplate: '/music/new-releases',
            controller: MusicNewReleasesAction::class,
            paginationEnabled: false,
            output: false,
            read: false,
            deserialize: false,
        ),
        new GetCollection(
            uriTemplate: '/albums/spotify/{spotifyAlbumId}/tracks',
            controller: AlbumTracksAction::class,
            paginationEnabled: false,
            output: false,
            read: false,
            deserialize: false,
        ),
        new Post(
            uriTemplate: '/music/import/spotify/{spotifyTrackId}',
            controller: MusicImportSpotifyTrackAction::class,
            output: false,
            read: false,
            deserialize: false,
            validate: false,
        ),
    ],
    normalizationContext: ['groups' => ['music:read']]
)]
class Music
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['music:read'])]
    private ?int $id = null;

    #[ORM\Column(length: 64, unique: true, nullable: true)]
    private ?string $spotifyId = null;

    #[ORM\Column(length: 64, nullable: true)]
    private ?string $importSource = null;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $importedAt = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotNull]
    #[Assert\NotBlank(
        message: 'La musique doit avoir un titre.',
        groups: ['validation:music:create', 'validation:music:update']
    )]
    #[Groups(['music:read', 'serialization:music:create', 'serialization:music:update'])]
    private ?string $title = null;

    /**
     * @var Collection<int, Artist>
     */
    #[Assert\Count(
        min: 1,
        minMessage: 'Une musique doit avoir au moins un artiste.',
        groups: ['validation:music:create', 'validation:music:update']
    )]
    #[ORM\ManyToMany(targetEntity: Artist::class, inversedBy: 'music')]
    #[Assert\NotNull(groups: ['validation:music:create', 'validation:music:update'])]
    #[Groups(['music:read', 'serialization:music:create', 'serialization:music:update'])]
    #[ApiProperty(readableLink: false)]
    private Collection $artists;

    /**
     * Genres of the music (can have more than one)
     */
    #[Assert\Count(
        min: 1,
        minMessage: 'Une musique doit avoir au moins un genre.',
        groups: ['validation:music:create', 'validation:music:update']
    )]
    #[ORM\Column(nullable: true)]
    #[Groups(['music:read', 'serialization:music:create', 'serialization:music:update'])]
    private ?array $genre = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['music:read', 'serialization:music:create', 'serialization:music:update'])]
    private ?string $picture = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Assert\Url(groups: ['validation:music:create', 'validation:music:update'])]
    #[Groups(['music:read', 'serialization:music:create', 'serialization:music:update'])]
    private ?string $link = null;

    #[ORM\Column]
    #[Groups(['music:read', 'music:admin:read', 'serialization:music:update'])]
    private ?bool $isValidated;

    #[ORM\Column(nullable: true)]
    //#[Groups(['music:admin:read'])]
    private ?array $requestJSON = null;

    /**
     * Popularity of the music
     */
    #[Assert\PositiveOrZero(
        message: 'La popularité doit être positive.',
        groups: ['validation.music:create', 'validation.music:update']
    )]
    #[Assert\Range(
        min: 0,
        max: 100,
        groups: ['validation.music:create', 'validation.music:update']
    )]
    #[ORM\Column]
    #[Groups(['music:read', 'serialization:music:create'])]
    private ?int $popularity = null;

    public function __construct()
    {
        $this->artists = new ArrayCollection();
        $this->isValidated = false;
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
        if (!$this->artists->contains($artist)) {
            $this->artists->add($artist);
        }

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

    public function isValidated(): ?bool
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
}
