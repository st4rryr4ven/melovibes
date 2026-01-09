<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use App\Repository\ArtistRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: ArtistRepository::class)]
#[ApiResource(
    operations: [
        new GetCollection(),
        new Get()
    ],
    normalizationContext: ['groups' => ['artist:read']],
)]
class Artist
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['artist:read', 'music:read'])]
    private ?int $id = null;

    /**
     * Name of the artist
     */
    #[ORM\Column(length: 255)]
    #[Groups(['artist:read', 'music:read'])]
    private ?string $name = null;

    /**
     * All the musics of the artist
     * @var Collection<int, Music>
     */
    #[ORM\ManyToMany(targetEntity: Music::class, mappedBy: 'artists')]
    private Collection $musics;

    public function __construct()
    {
        $this->musics = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
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
    public function getMusics(): Collection
    {
        return $this->musics;
    }

    public function addMusic(Music $music): static
    {
        if (!$this->musics->contains($music)) {
            $this->musics->add($music);
            $music->addArtist($this);
        }

        return $this;
    }

    public function removeMusic(Music $music): static
    {
        if ($this->musics->removeElement($music)) {
            $music->removeArtist($this);
        }

        return $this;
    }
}
