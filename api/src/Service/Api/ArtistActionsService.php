<?php

namespace App\Service\Api;

use App\Repository\ArtistRepository;
use App\Repository\MusicRepository;
use App\Service\Spotify\SpotifyApiClient;
use App\Service\Spotify\SpotifyCatalogService;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Stateless service that builds JSON payloads for artist-related custom API operations.
 *
 * The application exposes a few non-CRUD endpoints around artists (search, Spotify import, listing an artist's music,
 * and fetching top tracks from Spotify). This service keeps the controller/actions thin by encapsulating the request
 * parsing, data aggregation, and payload shaping logic.
 */
final class ArtistActionsService
{
    /**
     * Searches artists across the local database and Spotify catalog.
     *
     * The response is a merged list: local results first, then Spotify results (excluding Spotify artists already
     * present locally). Pagination parameters apply to the merged result set.
     *
     * Expected query parameters:
     * - q (string, required): search query.
     * - limit (int, optional, 1..50): max items to return.
     * - offset (int, optional, >=0): offset in the merged result set.
     * - market (string, optional): Spotify market code.
     *
     * @param Request $request Incoming HTTP request.
     * @param SpotifyApiClient $spotify Spotify API client used for catalog searches.
     * @param ArtistRepository $artistRepository Repository used to search locally persisted artists.
     *
     * @return JsonResponse JSON payload: { items: [...], meta: { limit, offset, localTotal, spotify: {...} } }.
     */
    public function search(Request $request, SpotifyApiClient $spotify, ArtistRepository $artistRepository): JsonResponse
    {
        $q = trim((string) $request->query->get('q', ''));
        if ($q === '') {
            return new JsonResponse(['message' => 'Missing query parameter q'], 400);
        }

        $limit = max(1, min(50, (int) $request->query->get('limit', 20)));
        $offset = max(0, (int) $request->query->get('offset', 0));
        $market = (string) $request->query->get('market', 'FR');

        $localTotal = $artistRepository->countLocalSearch($q);
        $localOffset = min($offset, $localTotal);
        $localLimit = min($limit, max(0, $localTotal - $localOffset));

        $localResults = $artistRepository->searchLocal($q, $localLimit, $localOffset);

        $items = [];
        $localSpotifyIds = [];

        foreach ($localResults as $a) {
            if ($a->getSpotifyId() !== null) {
                $localSpotifyIds[] = $a->getSpotifyId();
            }

            $items[] = [
                'source' => 'local',
                'local' => [
                    'artistId' => $a->getId(),
                    'spotifyId' => $a->getSpotifyId(),
                    'name' => $a->getName(),
                ],
                'spotify' => null,
            ];
        }

        $spotifyLimit = $limit - count($localResults);
        $spotifyOffset = max(0, $offset - $localTotal);

        $spotifyMeta = null;

        if ($spotifyLimit > 0) {
            $payload = $spotify->search($q, ['artist'], $spotifyLimit, $spotifyOffset, $market);
            $artists = $payload['artists']['items'] ?? [];
            if (!is_array($artists)) {
                $artists = [];
            }

            $spotifyMeta = [
                'limit' => $payload['artists']['limit'] ?? $spotifyLimit,
                'offset' => $payload['artists']['offset'] ?? $spotifyOffset,
                'total' => $payload['artists']['total'] ?? null,
                'next' => $payload['artists']['next'] ?? null,
                'previous' => $payload['artists']['previous'] ?? null,
            ];

            $spotifyIds = [];
            foreach ($artists as $a) {
                if (is_array($a) && isset($a['id'])) {
                    $sid = (string) $a['id'];
                    if ($sid !== '' && !in_array($sid, $localSpotifyIds, true)) {
                        $spotifyIds[] = $sid;
                    }
                }
            }

            $existing = $artistRepository->findBySpotifyIds(array_values(array_unique($spotifyIds)));
            $bySpotifyId = [];

            foreach ($existing as $a) {
                if ($a->getSpotifyId() !== null) {
                    $bySpotifyId[$a->getSpotifyId()] = $a->getId();
                }
            }

            foreach ($artists as $a) {
                if (!is_array($a) || !isset($a['id'])) {
                    continue;
                }

                $sid = (string) $a['id'];
                if ($sid === '' || in_array($sid, $localSpotifyIds, true)) {
                    continue;
                }

                $items[] = [
                    'source' => 'spotify',
                    'local' => [
                        'isImported' => isset($bySpotifyId[$sid]),
                        'artistId' => $bySpotifyId[$sid] ?? null,
                    ],
                    'spotify' => $a,
                ];
            }
        }

        return new JsonResponse([
            'items' => $items,
            'meta' => [
                'limit' => $limit,
                'offset' => $offset,
                'localTotal' => $localTotal,
                'spotify' => $spotifyMeta,
            ],
        ]);
    }

    /**
     * Imports a Spotify artist into the local database.
     *
     * If the artist already exists locally (same spotify_id), it is returned as-is.
     *
     * Expected query parameters:
     * - market (string, optional): Spotify market code.
     *
     * @param string $spotifyArtistId Spotify artist id.
     * @param Request $request Incoming HTTP request.
     * @param SpotifyCatalogService $catalog Domain service responsible for translating Spotify catalog objects into
     *                                      local entities.
     * @param ArtistRepository $artistRepository Repository used to check for an existing import.
     *
     * @return JsonResponse JSON payload containing the persisted artist.
     */
    public function importSpotifyArtist(
        string $spotifyArtistId,
        Request $request,
        SpotifyCatalogService $catalog,
        ArtistRepository $artistRepository
    ): JsonResponse {
        $spotifyArtistId = trim($spotifyArtistId);
        if ($spotifyArtistId === '') {
            return new JsonResponse(['message' => 'Spotify ID is missing'], 400);
        }

        $existing = $artistRepository->findOneBySpotifyId($spotifyArtistId);
        if ($existing !== null) {
            return new JsonResponse([
                'artistId' => $existing->getId(),
                'spotifyId' => $existing->getSpotifyId(),
                'name' => $existing->getName(),
            ], 200);
        }

        $market = (string) $request->query->get('market', 'FR');
        $artist = $catalog->importArtistById($spotifyArtistId, $market, true, true);

        return new JsonResponse([
            'artistId' => $artist->getId(),
            'spotifyId' => $artist->getSpotifyId(),
            'name' => $artist->getName(),
        ], 201);
    }

    /**
     * Lists the musics linked to a local artist.
     *
     * Expected query parameters:
     * - limit (int, optional, 1..50): max items to return.
     * - offset (int, optional, >=0): offset in the artist's music list.
     *
     * @param int $artistId Local artist id.
     * @param Request $request Incoming HTTP request.
     * @param ArtistRepository $artistRepository Repository used to load the artist.
     * @param MusicRepository $musicRepository Repository used to query musics.
     *
     * @return JsonResponse JSON payload: { artist: {...}, items: [...], meta: {...} }.
     */
    public function music(int $artistId, Request $request, ArtistRepository $artistRepository, MusicRepository $musicRepository): JsonResponse
    {
        $artist = $artistRepository->find($artistId);
        if ($artist === null) {
            return new JsonResponse(['message' => 'Artist not found'], 404);
        }

        $limit = max(1, min(50, (int) $request->query->get('limit', 20)));
        $offset = max(0, (int) $request->query->get('offset', 0));

        $total = $musicRepository->countByArtistId($artistId);
        $rows = $musicRepository->findByArtistId($artistId, $limit, $offset);

        $items = [];

        foreach ($rows as $m) {
            $artists = [];
            foreach ($m->getArtists() as $a) {
                $artists[] = [
                    'id' => $a->getId(),
                    'name' => $a->getName(),
                    'spotifyId' => $a->getSpotifyId(),
                ];
            }

            $items[] = [
                'musicId' => $m->getId(),
                'spotifyId' => $m->getSpotifyId(),
                'title' => $m->getTitle(),
                'link' => $m->getLink(),
                'picture' => $m->getPicture(),
                'genre' => $m->getGenre(),
                'popularity' => $m->getPopularity(),
                'artists' => $artists,
            ];
        }

        return new JsonResponse([
            'artist' => [
                'artistId' => $artist->getId(),
                'spotifyId' => $artist->getSpotifyId(),
                'name' => $artist->getName(),
            ],
            'items' => $items,
            'meta' => [
                'limit' => $limit,
                'offset' => $offset,
                'total' => $total,
            ],
        ]);
    }

    /**
     * Returns Spotify top tracks for a local artist.
     *
     * The returned list includes import metadata for each track (whether it already exists locally and the local id
     * if available).
     *
     * Expected query parameters:
     * - market (string, optional): Spotify market code.
     * - limit (int, optional, 1..50): max items to return.
     *
     * @param int $artistId Local artist id.
     * @param Request $request Incoming HTTP request.
     * @param ArtistRepository $artistRepository Repository used to load the artist.
     * @param MusicRepository $musicRepository Repository used to detect existing imported tracks.
     * @param SpotifyApiClient $spotify Spotify API client.
     *
     * @return JsonResponse JSON payload: { artist: {...}, items: [...], meta: {...} }.
     */
    public function topTracks(int $artistId, Request $request, ArtistRepository $artistRepository, MusicRepository $musicRepository, SpotifyApiClient $spotify): JsonResponse
    {
        $artist = $artistRepository->find($artistId);
        if ($artist === null) {
            return new JsonResponse(['message' => 'Artist not found'], 404);
        }

        $spotifyArtistId = $artist->getSpotifyId();
        if ($spotifyArtistId === null || trim($spotifyArtistId) === '') {
            return new JsonResponse(['message' => 'Artist is not linked to Spotify'], 400);
        }

        $market = (string) $request->query->get('market', 'FR');
        $limit = max(1, min(50, (int) $request->query->get('limit', 10)));

        $payload = $spotify->getArtistTopTracks($spotifyArtistId, $market);
        $tracks = $payload['tracks'] ?? [];

        if (!is_array($tracks)) {
            $tracks = [];
        }

        $tracks = array_slice($tracks, 0, $limit);

        $spotifyIds = [];
        foreach ($tracks as $t) {
            if (is_array($t) && isset($t['id'])) {
                $spotifyIds[] = (string) $t['id'];
            }
        }

        $existing = $musicRepository->findBySpotifyIds(array_values(array_unique($spotifyIds)));
        $bySpotifyId = [];

        foreach ($existing as $m) {
            if ($m->getSpotifyId() !== null) {
                $bySpotifyId[$m->getSpotifyId()] = $m->getId();
            }
        }

        $items = [];

        foreach ($tracks as $t) {
            if (!is_array($t) || !isset($t['id'])) {
                continue;
            }

            $sid = (string) $t['id'];

            $items[] = [
                'spotify' => $t,
                'local' => [
                    'isImported' => isset($bySpotifyId[$sid]),
                    'musicId' => $bySpotifyId[$sid] ?? null,
                ],
            ];
        }

        return new JsonResponse([
            'artist' => [
                'artistId' => $artist->getId(),
                'spotifyId' => $artist->getSpotifyId(),
                'name' => $artist->getName(),
            ],
            'items' => $items,
            'meta' => [
                'market' => $market,
                'limit' => $limit,
            ],
        ]);
    }
}
