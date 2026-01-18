<?php

namespace App\Service\Spotify;

use App\Entity\Artist;
use App\Entity\Music;
use App\Entity\User;
use App\Repository\ArtistRepository;
use App\Repository\MusicRepository;
use DateInterval;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Imports and synchronizes Spotify catalog data into the local database.
 *
 * This service provides two categories of operations:
 * - Batch synchronization (for example importing Spotify "new releases").
 * - On-demand imports triggered by API requests (track import, album import, artist import).
 *
 * The import logic attempts to be idempotent:
 * - When a Spotify identifier is known, entities are matched by spotify_id.
 * - As a fallback for tracks, entities can also be matched by the Spotify public URL.
 *
 * Imported entities are persisted using Doctrine and can be enriched with import metadata
 * (import source, import timestamp, raw request JSON).
 */
readonly class SpotifyCatalogService
{
    /**
     * @param SpotifyApiClient $spotify Low-level Spotify HTTP client.
     * @param MusicRepository $musicRepository Repository used to find and upsert musics.
     * @param ArtistRepository $artistRepository Repository used to find and upsert artists.
     * @param EntityManagerInterface $entityManager Doctrine entity manager.
     */
    public function __construct(
        private SpotifyApiClient       $spotify,
        private MusicRepository        $musicRepository,
        private ArtistRepository       $artistRepository,
        private EntityManagerInterface $entityManager,
    )
    {
    }

    /**
     * Imports Spotify "new releases" into the local database.
     *
     * The method fetches new release albums, then fetches album tracks and their full track payloads,
     * and finally upserts each track as a local {@see Music} entity.
     *
     * @param string $country Market/country code used by Spotify (ISO 3166-1 alpha-2).
     * @param int $albumLimit Page size for the Spotify new releases endpoint (max 50).
     * @param int $albumOffset Offset for the Spotify new releases endpoint.
     * @param int $maxTracksPerAlbum Maximum number of tracks processed per album.
     * @param bool $updateExisting Whether existing rows matched by spotify_id/link should be updated.
     * @param bool $dryRun When true, no database writes are performed.
     *
     * @return int Number of tracks imported/updated.
     */
    public function syncNewReleases(
        string $country = 'FR',
        int    $albumLimit = 20,
        int    $albumOffset = 0,
        int    $maxTracksPerAlbum = 50,
        bool   $updateExisting = true,
        bool   $dryRun = false,
    ): int
    {
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

            $albumId = (string)$album['id'];
            $tracksPage = $this->spotify->getAlbumTracks($albumId, min(50, $maxTracksPerAlbum), 0, $country);
            $items = $tracksPage['items'] ?? [];

            if (!is_array($items) || count($items) === 0) {
                continue;
            }

            $trackIds = [];

            foreach ($items as $t) {
                if (is_array($t) && isset($t['id'])) {
                    $trackIds[] = (string)$t['id'];
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
                    $now = new DateTimeImmutable();
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

    /**
     * Imports a Spotify track by its identifier.
     *
     * This method fetches the full track payload from Spotify and upserts it into the local database.
     * When the music is first imported, default import metadata is set.
     *
     * @param string $trackId Spotify track id.
     * @param string $market Spotify market code used to localize the response.
     * @param bool $updateExisting Whether to update an existing row when the track is already present locally.
     *
     * @return Music The upserted local music entity.
     */
    public function importTrackById(string $trackId, string $market = 'FR', bool $updateExisting = true): Music
    {
        $track = $this->spotify->getTrack($trackId, $market);
        $artistDetailsById = $this->fetchArtistsByTracks([$track]);

        $music = $this->upsertTrackAndReturn($track, $artistDetailsById, $updateExisting);
        if ($music->getImportSource() === null) {
            $music->setImportSource('spotify:on_demand');
        }
        if ($music->getImportedAt() === null) {
            $music->setImportedAt(new DateTimeImmutable());
        }

        $this->entityManager->flush();

        return $music;
    }

    /**
     * Imports all tracks of a Spotify album.
     *
     * The method paginates through album tracks to collect track ids, then fetches full track payloads in batches
     * and upserts each track locally.
     *
     * @param string $albumId Spotify album id.
     * @param string $market Spotify market code used to localize the response.
     * @param bool $updateExisting Whether to update existing rows when tracks are already present locally.
     *
     * @return Music[] List of imported/upserted local music entities.
     */
    public function importAlbumById(string $albumId, string $market = 'FR', bool $updateExisting = true): array
    {
        $trackIds = [];
        $offset = 0;

        while (true) {
            $page = $this->spotify->getAlbumTracks($albumId, 50, $offset, $market);
            $items = $page['items'] ?? [];
            if (!is_array($items) || count($items) === 0) {
                break;
            }

            foreach ($items as $t) {
                if (is_array($t) && isset($t['id'])) {
                    $id = trim((string)$t['id']);
                    if ($id !== '') {
                        $trackIds[] = $id;
                    }
                }
            }

            $next = $page['next'] ?? null;
            if (!is_string($next) || $next === '') {
                break;
            }

            $offset += 50;
        }

        $trackIds = array_values(array_unique($trackIds));
        if (count($trackIds) === 0) {
            return [];
        }

        $fullTracks = [];

        foreach (array_chunk($trackIds, 50) as $chunk) {
            $payload = $this->spotify->getTracks($chunk, $market);
            $tracks = $payload['tracks'] ?? [];
            if (is_array($tracks)) {
                foreach ($tracks as $t) {
                    if (is_array($t)) {
                        $fullTracks[] = $t;
                    }
                }
            }
        }

        if (count($fullTracks) === 0) {
            return [];
        }

        $artistDetailsById = $this->fetchArtistsByTracks($fullTracks);
        $now = new DateTimeImmutable();
        $importSource = substr('spotify:album:' . $albumId, 0, 64);

        $imported = [];

        foreach ($fullTracks as $track) {
            $music = $this->upsertTrackInternal($track, $artistDetailsById, $updateExisting, false);
            if ($music === null) {
                continue;
            }

            $music->setImportSource($importSource);
            $music->setImportedAt($now);
            $imported[] = $music;
        }

        $this->entityManager->flush();

        return $imported;
    }

    /**
     * Imports a Spotify artist and optionally imports their top tracks.
     *
     * The artist is upserted locally. When requested, the Spotify "top tracks" endpoint is queried and each track
     * is upserted as a local {@see Music} entity.
     *
     * @param string $artistId Spotify artist id.
     * @param string $market Spotify market code used to localize the response.
     * @param bool $updateExisting Whether to update existing rows when already present locally.
     * @param bool $importTopTracks Whether to import the artist top tracks as local musics.
     *
     * @return Artist The upserted local artist entity.
     */
    public function importArtistById(string $artistId, string $market = 'FR', bool $updateExisting = true, bool $importTopTracks = true): Artist
    {
        $artistPayload = $this->spotify->getArtist($artistId);
        $artist = $this->upsertArtistFromSpotify($artistPayload, $updateExisting);

        if ($artist->getId() === null) {
            $this->entityManager->flush();
        }

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
                    $artistIds[] = (string)$artist['id'];
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
                    $byId[(string)$a['id']] = $a;
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
        $trackSpotifyId = isset($track['id']) ? trim((string)$track['id']) : '';
        $spotifyUrl = (string)($track['external_urls']['spotify'] ?? '');
        $title = (string)($track['name'] ?? '');

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
            $picture = (string)$images[0]['url'];
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
        $music->setPopularity((int)($track['popularity'] ?? 0));
        $music->setIsValidated(true);

        $music->setRequestJSON([
            'track' => $track,
            'artists' => array_values(array_filter(array_map(function (mixed $a) use ($artistDetailsById): mixed {
                if (!is_array($a) || !isset($a['id'])) {
                    return null;
                }

                return $artistDetailsById[(string)$a['id']] ?? $a;
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
        $spotifyId = isset($artistPayload['id']) ? trim((string)$artistPayload['id']) : '';
        $name = (string)($artistPayload['name'] ?? '');

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
        $uow = $this->entityManager->getUnitOfWork();
        $scheduledInserts = $uow->getScheduledEntityInsertions();

        foreach (($track['artists'] ?? []) as $artistData) {
            if (!is_array($artistData)) {
                continue;
            }

            $artistSpotifyId = isset($artistData['id']) ? trim((string)$artistData['id']) : '';
            $name = (string)($artistData['name'] ?? '');

            if ($name === '') {
                continue;
            }

            $artist = null;

            if ($artistSpotifyId !== '') {
                foreach ($scheduledInserts as $entity) {
                    if ($entity instanceof Artist && $entity->getSpotifyId() === $artistSpotifyId) {
                        $artist = $entity;
                        break;
                    }
                }
            }

            if ($artist === null && $artistSpotifyId !== '') {
                $artist = $this->artistRepository->findOneBySpotifyId($artistSpotifyId);
            }

            if ($artist === null) {
                foreach ($scheduledInserts as $entity) {
                    if ($entity instanceof Artist && strtolower($entity->getName()) === strtolower($name)) {
                        if ($entity->getSpotifyId() === null && $artistSpotifyId !== '') {
                            $entity->setSpotifyId($artistSpotifyId);
                        }
                        $artist = $entity;
                        break;
                    }
                }
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

            $details = $artistDetailsById[(string)$artistData['id']] ?? null;
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

    /**
     * @return array{added:int, scanned:int}
     */
    public function importUserLikedTracksToFavorites(User $user, string $userAccessToken, string $market = 'FR', int $maxTracks = 2000, bool $updateExisting = true): array
    {
        $added = 0;
        $scanned = 0;

        $offset = 0;
        $limit = 50;

        while (true) {
            if ($scanned >= $maxTracks) {
                break;
            }

            $accessToken = $this->getFreshSpotifyUserAccessToken($user, $userAccessToken);

            $page = $this->spotify->userGetSavedTracks($accessToken, $limit, $offset, $market);
            $items = $page['items'] ?? [];

            if (!is_array($items) || count($items) === 0) {
                break;
            }

            $trackIds = [];

            foreach ($items as $item) {
                if (!is_array($item)) {
                    continue;
                }
                $track = $item['track'] ?? null;
                if (is_array($track) && isset($track['id'])) {
                    $id = trim((string) $track['id']);
                    if ($id !== '') {
                        $trackIds[] = $id;
                    }
                }
            }

            $trackIds = array_values(array_unique($trackIds));
            if (count($trackIds) === 0) {
                $offset += $limit;
                continue;
            }

            $existing = $this->musicRepository->findBySpotifyIds($trackIds);
            $existingBySpotifyId = [];

            foreach ($existing as $m) {
                if ($m->getSpotifyId() !== null) {
                    $existingBySpotifyId[$m->getSpotifyId()] = $m;
                }
            }

            foreach ($trackIds as $spotifyId) {
                $scanned++;
                if ($scanned > $maxTracks) {
                    break 2;
                }

                $music = $existingBySpotifyId[$spotifyId] ?? null;

                if ($music === null) {
                    $music = $this->importTrackById($spotifyId, $market, $updateExisting);
                }

                $before = $user->getFavoriteMusic()->contains($music);
                $user->addFavoriteMusic($music);

                if (!$before) {
                    $added++;
                }
            }

            $offset += $limit;

            $next = $page['next'] ?? null;
            if (!is_string($next) || $next === '') {
                break;
            }
        }

        $this->entityManager->flush();

        return ['added' => $added, 'scanned' => $scanned];
    }

    private function getFreshSpotifyUserAccessToken(User $user, string $fallbackAccessToken): string
    {
        $storedAccess = $user->getSpotifyAccessToken();
        $storedExpiresAt = $user->getSpotifyAccessTokenExpiresAt();

        $accessToken = $storedAccess ?: $fallbackAccessToken;

        if ($accessToken === '') {
            throw new SpotifyApiException('Spotify user access token missing');
        }

        if ($storedExpiresAt === null) {
            return $accessToken;
        }

        $now = new DateTimeImmutable();
        $needsRefresh = $storedExpiresAt <= $now->add(new DateInterval('PT60S'));

        if (!$needsRefresh) {
            return $accessToken;
        }

        $refreshToken = $user->getSpotifyRefreshToken();
        if ($refreshToken === null || trim($refreshToken) === '') {
            throw new SpotifyApiException('Spotify refresh token missing (user must relink Spotify)');
        }

        $payload = $this->spotify->refreshUserAccessToken($refreshToken);

        $newAccess = (string) ($payload['access_token'] ?? '');
        $expiresIn = (int) ($payload['expires_in'] ?? 0);
        $newRefresh = isset($payload['refresh_token']) ? (string) $payload['refresh_token'] : null;

        if ($newAccess === '' || $expiresIn <= 0) {
            throw new SpotifyApiException('Spotify refresh failed');
        }

        $user->setSpotifyAccessToken($newAccess);
        $user->setSpotifyAccessTokenExpiresAt($now->modify(sprintf('+%d seconds', max(1, $expiresIn))));

        if ($newRefresh !== null && trim($newRefresh) !== '') {
            $user->setSpotifyRefreshToken($newRefresh);
        }

        $this->entityManager->flush();

        return $newAccess;
    }

}
