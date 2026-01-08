<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use App\Repository\MusicRepository;
use App\State\MusicProcessor;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: MusicRepository::class)]
#[ApiResource(
    operations: [
        new GetCollection(),
        new Get(),
        new Post(
            denormalizationContext: ['groups' => ['music:write']],
            security: "is_granted('ROLE_USER')",
            processor: MusicProcessor::class
        ),
        new Patch(
            security: "is_granted('ROLE_ADMIN')"
        ),
        new Delete(
            security: "is_granted('ROLE_ADMIN')"
        )
    ]
)]
class Music
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    /**
     * Title of the music
     */
    #[ORM\Column(length: 255)]
    #[Assert\NotNull]
    #[Assert\NotBlank(
        message: 'La musique doit avoir un titre.',
        groups: ['validation.music:create', 'validation.music:update']
    )]
    private ?string $title = null;

    /**
     * Artists of the music (can have more than one)
     * @var Collection<int, Artist>
     */
    #[Assert\Count(
        min: 1,
        minMessage: 'Une musique doit avoir au moins un artiste.',
        groups: ['validation.music:create', 'validation.music:update']
    )]
    #[ORM\ManyToMany(targetEntity: Artist::class, inversedBy: 'musics')]
    #[Assert\NotNull]
    #[Assert\NotNull(message: "La musique doit avoir au moins un artiste", groups: ['validation.music:create', 'validation.music:update'])]
    private Collection $artists;

    /**
     * Genres of the music (can have more than one)
     */
    #[Assert\Count(
        min: 1,
        minMessage: 'Une musique doit avoir au moins un genre.',
        groups: ['validation.music:create', 'validation.music:update']
    )]
    #[ORM\Column(nullable: true)]
    private ?array $genre = null;

    /**
     * Picture related to the music (picture of the album, singer...)
     */
    #[ORM\Column(length: 255, nullable: true)]
    private ?string $picture = null;

    /**
     * Link related to the music (to a Spotify music, to a youtube video...)
     */
    #[ORM\Column(length: 255, nullable: true)]
    #[Assert\Url(
        message: 'Le lien doit être une URL valide.',
        groups: ['validation.music:create', 'validation.music:update']
    )]
    private ?string $link = null;

    /**
     * Boolean to see if the music is validated by an admin or not
     */
    #[ORM\Column]
    #[Groups(['music:admin'])]
    private ?bool $isValidated = null;

    /**
     * JSON with all the information from the Spotify API request
     */
    #[ORM\Column(nullable: true)]
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
