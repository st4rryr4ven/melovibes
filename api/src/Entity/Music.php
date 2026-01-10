<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Post;
use App\Api\Action\MusicImportSpotifyTrackAction;
use App\Api\Action\MusicNewReleasesAction;
use App\Api\Action\MusicSearchAction;
use App\Repository\MusicRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;
use ApiPlatform\Doctrine\Orm\Filter\BooleanFilter;
use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Delete;

#[ORM\Entity(repositoryClass: MusicRepository::class)]
#[ORM\Table(name: 'music')]
#[ORM\UniqueConstraint(name: 'UNIQ_SPOTIFY_ID', columns: ['spotify_id'])]
#[UniqueEntity(fields: ['spotifyId'], message: 'This spotifyId is already used.')]
#[ApiFilter(BooleanFilter::class, properties: ['isValidated'])]
#[ApiResource(
    operations: [
        new Get(
            requirements: ['id' => '\\d+'],
//            security: "(is_granted('ROLE_USER') and object == user) or is_granted('ROLE_ADMIN')"
        ),
        new GetCollection(
            security: "is_granted('ROLE_ADMIN')"
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
        new Post(
            uriTemplate: '/music/import/spotify/{spotifyTrackId}',
            controller: MusicImportSpotifyTrackAction::class,
            output: false,
            read: false,
            deserialize: false,
            validate: false,
        ),
        new Patch(
            inputFormats: [
                'json' => ['application/merge-patch+json'],
            ],
            denormalizationContext: ['groups' => ['music:update']],
            security: "is_granted('ROLE_ADMIN')"
        ),
        new Delete(security: "is_granted('ROLE_ADMIN')")


    ],
)]
class Music
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    /**
     * Spotify identifier when the record comes from Spotify.
     */
    #[ORM\Column(length: 64, unique: true, nullable: true)]
    private ?string $spotifyId = null;

    /**
     * Import source label for filtering curated lists.
     */
    #[ORM\Column(length: 64, nullable: true)]
    private ?string $importSource = null;

    /**
     * Date when the record was imported or last tagged by a sync process.
     */
    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $importedAt = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotNull]
    #[Assert\NotNull(message: "La musique doit avoir un titre", groups: ['validation.music:create', 'validation.music:update'])]
    private ?string $title = null;

    /**
     * @var Collection<int, Artist>
     */
    #[ORM\ManyToMany(targetEntity: Artist::class, inversedBy: 'musics')]
    #[Assert\NotNull]
    #[Assert\NotNull(message: "La musique doit avoir au moins un artiste", groups: ['validation.music:create', 'validation.music:update'])]
    private Collection $artists;

    #[ORM\Column(nullable: true)]
    private ?array $genre = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $picture = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $link = null;

    #[ORM\Column]
    #[Groups(['music:update', 'music:read'])]
    private ?bool $isValidated = null;

    #[ORM\Column(nullable: true)]
    private ?array $requestJSON = null;

    #[ORM\Column]
    private ?int $popularity = null;

    public function __construct()
    {
        $this->artists = new ArrayCollection();
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

    /**
     * @return Collection<int, Artist>
     */
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
