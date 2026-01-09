<?php

namespace App\Service\Spotify;

use App\Entity\Artist;
use App\Entity\Music;
use App\Repository\ArtistRepository;
use App\Repository\MusicRepository;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Synchronizes Spotify catalog data into the local database and supports on-demand imports.
 */
readonly class SpotifyCatalogService
{
    public function __construct(
        private SpotifyApiClient       $spotify,
        private MusicRepository        $musicRepository,
        private ArtistRepository       $artistRepository,
        private EntityManagerInterface $entityManager,
    ) {
    }

    public function syncNewReleases(
        string $country = 'FR',
        int $albumLimit = 20,
        int $albumOffset = 0,
        int $maxTracksPerAlbum = 50,
        bool $updateExisting = true,
        bool $dryRun = false,
    ): int {
        $count = 0;

        $payload = $this->spotify->getNewReleases($albumLimit, $albumOffset, $country);
        $albums = $payload['albums']['items'] ?? [];

        if (!is_array($albums)) {
            return 0;
        }

        foreach ($albums as $album) {
            if (!is_array($album) || !isset($album['id'])) {
                continue;
            }

            $albumId = (string) $album['id'];
            $tracksPage = $this->spotify->getAlbumTracks($albumId, min(50, $maxTracksPerAlbum), 0, $country);
            $items = $tracksPage['items'] ?? [];

            if (!is_array($items) || count($items) === 0) {
                continue;
            }

            $trackIds = [];

            foreach ($items as $t) {
                if (is_array($t) && isset($t['id'])) {
                    $trackIds[] = (string) $t['id'];
                }
            }

            $trackIds = array_values(array_unique($trackIds));
            if (count($trackIds) === 0) {
                continue;
            }

            $fullTracksPayload = $this->spotify->getTracks(array_slice($trackIds, 0, 50), $country);
            $fullTracks = $fullTracksPayload['tracks'] ?? [];

            if (!is_array($fullTracks) || count($fullTracks) === 0) {
                continue;
            }

            $artistDetailsById = $this->fetchArtistsByTracks($fullTracks);

            foreach ($fullTracks as $track) {
                if (!is_array($track)) {
                    continue;
                }

                $music = $this->upsertTrackInternal($track, $artistDetailsById, $updateExisting, $dryRun);
                if ($music !== null) {
                    $now = new \DateTimeImmutable();
                    $music->setImportSource('spotify:new_releases');
                    $music->setImportedAt($now);
                    $count++;
                }
            }

            if (!$dryRun) {
                $this->entityManager->flush();
            }
        }

        return $count;
    }

    public function importTrackById(string $trackId, string $market = 'FR', bool $updateExisting = true): Music
    {
        $track = $this->spotify->getTrack($trackId, $market);
        $artistDetailsById = $this->fetchArtistsByTracks([$track]);

        $music = $this->upsertTrackAndReturn($track, $artistDetailsById, $updateExisting);
        if ($music->getImportSource() === null) {
            $music->setImportSource('spotify:on_demand');
        }
        if ($music->getImportedAt() === null) {
            $music->setImportedAt(new \DateTimeImmutable());
        }

        $this->entityManager->flush();

        return $music;
    }

    public function importArtistById(string $artistId, string $market = 'FR', bool $updateExisting = true, bool $importTopTracks = true): Artist
    {
        $artistPayload = $this->spotify->getArtist($artistId);
        $artist = $this->upsertArtistFromSpotify($artistPayload, $updateExisting);

        if ($importTopTracks) {
            $top = $this->spotify->getArtistTopTracks($artistId, $market);
            $tracks = $top['tracks'] ?? [];
            if (is_array($tracks) && count($tracks) > 0) {
                $artistDetailsById = $this->fetchArtistsByTracks($tracks);
                foreach ($tracks as $track) {
                    if (is_array($track)) {
                        $this->upsertTrackInternal($track, $artistDetailsById, $updateExisting, false);
                    }
                }
            }
        }

        $this->entityManager->flush();

        return $artist;
    }

    /**
     * @param array<int, array> $tracks
     * @return array<string, array>
     */
    private function fetchArtistsByTracks(array $tracks): array
    {
        $artistIds = [];

        foreach ($tracks as $track) {
            if (!is_array($track)) {
                continue;
            }

            foreach (($track['artists'] ?? []) as $artist) {
                if (is_array($artist) && isset($artist['id'])) {
                    $artistIds[] = (string) $artist['id'];
                }
            }
        }

        $artistIds = array_values(array_unique($artistIds));
        if (count($artistIds) === 0) {
            return [];
        }

        $byId = [];

        foreach (array_chunk($artistIds, 50) as $chunk) {
            $data = $this->spotify->getArtists($chunk);
            $artists = $data['artists'] ?? [];
            if (!is_array($artists)) {
                continue;
            }

            foreach ($artists as $a) {
                if (is_array($a) && isset($a['id'])) {
                    $byId[(string) $a['id']] = $a;
                }
            }
        }

        return $byId;
    }

    /**
     * @param array<string, array> $artistDetailsById
     */
    private function upsertTrackAndReturn(array $track, array $artistDetailsById, bool $updateExisting): Music
    {
        $music = $this->upsertTrackInternal($track, $artistDetailsById, $updateExisting, false);

        if ($music === null) {
            throw new SpotifyApiException('Import failed', 500, ['track' => $track]);
        }

        return $music;
    }

    /**
     * @param array<string, array> $artistDetailsById
     */
    private function upsertTrackInternal(array $track, array $artistDetailsById, bool $updateExisting, bool $dryRun): ?Music
    {
        $trackSpotifyId = isset($track['id']) ? trim((string) $track['id']) : '';
        $spotifyUrl = (string) ($track['external_urls']['spotify'] ?? '');
        $title = (string) ($track['name'] ?? '');

        if ($title === '') {
            return null;
        }

        $existing = null;

        if ($trackSpotifyId !== '') {
            $existing = $this->musicRepository->findOneBySpotifyId($trackSpotifyId);
        }

        if ($existing === null && $spotifyUrl !== '') {
            $existing = $this->musicRepository->findOneByLink($spotifyUrl);
        }

        if ($existing !== null && !$updateExisting) {
            return null;
        }

        $picture = null;
        $images = $track['album']['images'] ?? null;
        if (is_array($images) && isset($images[0]['url'])) {
            $picture = (string) $images[0]['url'];
        }

        $genres = $this->computeGenres($track, $artistDetailsById);
        $music = $existing ?? new Music();

        if ($trackSpotifyId !== '' && $music->getSpotifyId() === null) {
            $music->setSpotifyId($trackSpotifyId);
        }

        $music->setTitle($title);
        if ($spotifyUrl !== '') {
            $music->setLink($spotifyUrl);
        }
        $music->setPicture($picture);
        $music->setGenre($genres);
        $music->setPopularity((int) ($track['popularity'] ?? 0));
        $music->setIsValidated(true);

        $music->setRequestJSON([
            'track' => $track,
            'artists' => array_values(array_filter(array_map(function (mixed $a) use ($artistDetailsById): mixed {
                if (!is_array($a) || !isset($a['id'])) {
                    return null;
                }

                return $artistDetailsById[(string) $a['id']] ?? $a;
            }, $track['artists'] ?? []))),
        ]);

        $importedArtists = $this->upsertArtistsForTrack($track);

        if ($updateExisting) {
            $this->syncMusicArtists($music, $importedArtists);
        } else {
            foreach ($importedArtists as $artist) {
                $music->addArtist($artist);
            }
        }

        if (!$dryRun && $existing === null) {
            $this->entityManager->persist($music);
        }

        return $music;
    }

    private function upsertArtistFromSpotify(array $artistPayload, bool $updateExisting): Artist
    {
        $spotifyId = isset($artistPayload['id']) ? trim((string) $artistPayload['id']) : '';
        $name = (string) ($artistPayload['name'] ?? '');

        if ($spotifyId === '' || $name === '') {
            throw new SpotifyApiException('Invalid artist payload', 500, ['artist' => $artistPayload]);
        }

        $artist = $this->artistRepository->findOneBySpotifyId($spotifyId);

        if ($artist === null) {
            $artist = $this->artistRepository->findOneByName($name);
            if ($artist !== null && $artist->getSpotifyId() === null) {
                $artist->setSpotifyId($spotifyId);
            }
        }

        if ($artist === null) {
            $artist = new Artist();
            $artist->setSpotifyId($spotifyId);
            $artist->setName($name);
            $this->entityManager->persist($artist);

            return $artist;
        }

        if ($updateExisting) {
            if ($artist->getSpotifyId() === null) {
                $artist->setSpotifyId($spotifyId);
            }
            if ($artist->getName() !== $name) {
                $artist->setName($name);
            }
        }

        return $artist;
    }

    /**
     * @return Artist[]
     */
    private function upsertArtistsForTrack(array $track): array
    {
        $artists = [];

        foreach (($track['artists'] ?? []) as $artistData) {
            if (!is_array($artistData)) {
                continue;
            }

            $artistSpotifyId = isset($artistData['id']) ? trim((string) $artistData['id']) : '';
            $name = (string) ($artistData['name'] ?? '');

            if ($name === '') {
                continue;
            }

            $artist = null;

            if ($artistSpotifyId !== '') {
                $artist = $this->artistRepository->findOneBySpotifyId($artistSpotifyId);
            }

            if ($artist === null) {
                $artist = $this->artistRepository->findOneByName($name);
                if ($artist !== null && $artistSpotifyId !== '' && $artist->getSpotifyId() === null) {
                    $artist->setSpotifyId($artistSpotifyId);
                }
            }

            if ($artist === null) {
                $artist = new Artist();
                $artist->setName($name);
                $artist->setSpotifyId($artistSpotifyId !== '' ? $artistSpotifyId : null);
                $this->entityManager->persist($artist);
            } else {
                if ($artist->getName() !== $name) {
                    $artist->setName($name);
                }
            }

            $artists[] = $artist;
        }

        return $artists;
    }

    /**
     * @param Artist[] $importedArtists
     */
    private function syncMusicArtists(Music $music, array $importedArtists): void
    {
        $wantedIds = [];

        foreach ($importedArtists as $a) {
            $wantedIds[] = $a->getId();
        }

        foreach ($music->getArtists() as $current) {
            if (!in_array($current->getId(), $wantedIds, true)) {
                $music->removeArtist($current);
            }
        }

        foreach ($importedArtists as $a) {
            $music->addArtist($a);
        }
    }

    /**
     * @param array<string, array> $artistDetailsById
     */
    private function computeGenres(array $track, array $artistDetailsById): array
    {
        $genres = [];

        foreach (($track['artists'] ?? []) as $artistData) {
            if (!is_array($artistData) || !isset($artistData['id'])) {
                continue;
            }

            $details = $artistDetailsById[(string) $artistData['id']] ?? null;
            if (!is_array($details)) {
                continue;
            }

            foreach (($details['genres'] ?? []) as $g) {
                if (is_string($g) && $g !== '') {
                    $genres[] = $g;
                }
            }
        }

        $genres = array_values(array_unique($genres));
        sort($genres);

        return $genres;
    }
}
