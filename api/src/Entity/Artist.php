<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Post;
use App\Api\Action\ArtistImportSpotifyArtistAction;
use App\Api\Action\ArtistMusicsAction;
use App\Api\Action\ArtistSearchAction;
use App\Api\Action\ArtistTopTracksAction;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use App\Repository\ArtistRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

#[ORM\Entity(repositoryClass: ArtistRepository::class)]
#[ORM\Table(name: 'artist')]
#[ORM\UniqueConstraint(name: 'UNIQ_SPOTIFY_ID', columns: ['spotify_id'])]
#[UniqueEntity(fields: ['spotify_id'], message: 'This spotifyId is already used.')]
#[ApiResource(
    operations: [
        new Get(
//            security: "(is_granted('ROLE_USER') and object == user) or is_granted('ROLE_ADMIN')"
        ),
        new GetCollection(
            security: "is_granted('ROLE_ADMIN')"
        ),
        new GetCollection(
            uriTemplate: '/artist/search',
            controller: ArtistSearchAction::class,
            paginationEnabled: false,
            output: false,
            read: false,
            deserialize: false,
        ),
        new Post(
            uriTemplate: '/artist/import/spotify/{spotifyArtistId}',
            controller: ArtistImportSpotifyArtistAction::class,
            output: false,
            read: false,
            deserialize: false,
            validate: false,
        ),
        new Get(
            uriTemplate: '/artist/{id}/musics',
            requirements: ['id' => '\\d+'],
            controller: ArtistMusicsAction::class,
            output: false,
            read: false,
            deserialize: false,
        ),
        new Get(
            uriTemplate: '/artist/{id}/top-tracks',
            requirements: ['id' => '\\d+'],
            controller: ArtistTopTracksAction::class,
            output: false,
            read: false,
            deserialize: false,
        ),
    ],
)]
class Artist
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['artist:read', 'music:read'])]
    private ?int $id = null;

    /**
     * Spotify identifier when the record comes from Spotify.
     */
    #[ORM\Column(length: 64, unique: true, nullable: true)]
    private ?string $spotifyId = null;

    #[ORM\Column(length: 255)]
    #[Groups(['artist:read', 'music:read'])]
    private ?string $name = null;

    /**
     * All the music of the artist
     * @var Collection<int, Music>
     */
    #[ORM\ManyToMany(targetEntity: Music::class, mappedBy: 'artists')]
    private Collection $music;

    public function __construct()
    {
        $this->music = new ArrayCollection();
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

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @return Collection<int, Music>
     */
    public function getMusic(): Collection
    {
        return $this->music;
    }

    public function addMusic(Music $music): static
    {
        if (!$this->music->contains($music)) {
            $this->music->add($music);
            $music->addArtist($this);
        }

        return $this;
    }

    public function removeMusic(Music $music): static
    {
        if ($this->music->removeElement($music)) {
            $music->removeArtist($this);
        }

        return $this;
    }
}
