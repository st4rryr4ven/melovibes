<?php

namespace App\Service\Api;

use App\Repository\MusicRepository;
use App\Service\Spotify\SpotifyApiClient;
use App\Service\Spotify\SpotifyCatalogService;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Stateless service holding custom API operations payload builders.
 */
final class MusicActionsService
{
    public function search(Request $request, SpotifyApiClient $spotify, MusicRepository $musicRepository): JsonResponse
    {
        $q = trim((string) $request->query->get('q', ''));
        if ($q === '') {
            return new JsonResponse(['message' => 'Missing query parameter q'], 400);
        }

        $limit = max(1, min(50, (int) $request->query->get('limit', 20)));
        $offset = max(0, (int) $request->query->get('offset', 0));
        $market = (string) $request->query->get('market', 'FR');

        $localTotal = $musicRepository->countLocalSearch($q);
        $localOffset = min($offset, $localTotal);
        $localLimit = min($limit, max(0, $localTotal - $localOffset));

        $localResults = $musicRepository->searchLocal($q, $localLimit, $localOffset);

        $items = [];
        $localSpotifyIds = [];

        foreach ($localResults as $m) {
            $artists = [];
            foreach ($m->getArtists() as $a) {
                $artists[] = [
                    'id' => $a->getId(),
                    'name' => $a->getName(),
                    'spotifyId' => $a->getSpotifyId(),
                ];
            }

            if ($m->getSpotifyId() !== null) {
                $localSpotifyIds[] = $m->getSpotifyId();
            }

            $items[] = [
                'source' => 'local',
                'local' => [
                    'musicId' => $m->getId(),
                    'spotifyId' => $m->getSpotifyId(),
                    'title' => $m->getTitle(),
                    'link' => $m->getLink(),
                    'picture' => $m->getPicture(),
                    'genre' => $m->getGenre(),
                    'popularity' => $m->getPopularity(),
                    'isValidated' => $m->isValidated(),
                    'artists' => $artists,
                ],
                'spotify' => null,
            ];
        }

        $spotifyLimit = $limit - count($localResults);
        $spotifyOffset = max(0, $offset - $localTotal);

        $spotifyMeta = null;

        if ($spotifyLimit > 0) {
            $payload = $spotify->search($q, ['track'], $spotifyLimit, $spotifyOffset, $market);
            $tracks = $payload['tracks']['items'] ?? [];
            if (!is_array($tracks)) {
                $tracks = [];
            }

            $spotifyMeta = [
                'limit' => $payload['tracks']['limit'] ?? $spotifyLimit,
                'offset' => $payload['tracks']['offset'] ?? $spotifyOffset,
                'total' => $payload['tracks']['total'] ?? null,
                'next' => $payload['tracks']['next'] ?? null,
                'previous' => $payload['tracks']['previous'] ?? null,
            ];

            $spotifyIds = [];
            foreach ($tracks as $t) {
                if (is_array($t) && isset($t['id'])) {
                    $sid = (string) $t['id'];
                    if ($sid !== '' && !in_array($sid, $localSpotifyIds, true)) {
                        $spotifyIds[] = $sid;
                    }
                }
            }

            $existing = $musicRepository->findBySpotifyIds(array_values(array_unique($spotifyIds)));
            $bySpotifyId = [];

            foreach ($existing as $m) {
                if ($m->getSpotifyId() !== null) {
                    $bySpotifyId[$m->getSpotifyId()] = $m->getId();
                }
            }

            foreach ($tracks as $t) {
                if (!is_array($t) || !isset($t['id'])) {
                    continue;
                }

                $sid = (string) $t['id'];
                if ($sid === '' || in_array($sid, $localSpotifyIds, true)) {
                    continue;
                }

                $items[] = [
                    'source' => 'spotify',
                    'local' => [
                        'isImported' => isset($bySpotifyId[$sid]),
                        'musicId' => $bySpotifyId[$sid] ?? null,
                    ],
                    'spotify' => $t,
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

    public function newReleases(Request $request, SpotifyApiClient $spotify): JsonResponse
    {
        $limit = max(1, min(50, (int) $request->query->get('limit', 20)));
        $offset = max(0, (int) $request->query->get('offset', 0));
        $country = (string) $request->query->get('country', 'FR');

        $payload = $spotify->getNewReleases($limit, $offset, $country);
        $albums = $payload['albums']['items'] ?? [];
        if (!is_array($albums)) {
            $albums = [];
        }

        $items = [];

        foreach ($albums as $a) {
            if (!is_array($a) || !isset($a['id'])) {
                continue;
            }

            $images = $a['images'] ?? null;
            $picture = null;
            if (is_array($images) && isset($images[0]['url'])) {
                $picture = (string) $images[0]['url'];
            }

            $artists = [];
            foreach (($a['artists'] ?? []) as $ar) {
                if (is_array($ar) && isset($ar['id']) && isset($ar['name'])) {
                    $artists[] = [
                        'id' => (string) $ar['id'],
                        'name' => (string) $ar['name'],
                    ];
                }
            }

            $items[] = [
                'albumId' => (string) $a['id'],
                'name' => (string) ($a['name'] ?? ''),
                'picture' => $picture,
                'link' => (string) ($a['external_urls']['spotify'] ?? ''),
                'releaseDate' => (string) ($a['release_date'] ?? ''),
                'totalTracks' => isset($a['total_tracks']) ? (int) $a['total_tracks'] : null,
                'artists' => $artists,
            ];
        }

        $meta = [
            'limit' => (int) ($payload['albums']['limit'] ?? $limit),
            'offset' => (int) ($payload['albums']['offset'] ?? $offset),
            'total' => isset($payload['albums']['total']) ? (int) $payload['albums']['total'] : null,
            'country' => $country,
        ];

        return new JsonResponse([
            'items' => $items,
            'meta' => $meta,
        ]);
    }

    public function albumTracks(string $spotifyAlbumId, Request $request, SpotifyApiClient $spotify, MusicRepository $musicRepository): JsonResponse
    {
        $market = (string) $request->query->get('market', 'FR');

        $album = $spotify->getAlbum($spotifyAlbumId, $market);

        $images = $album['images'] ?? null;
        $albumPicture = null;
        if (is_array($images) && isset($images[0]['url'])) {
            $albumPicture = (string) $images[0]['url'];
        }

        $albumArtists = [];
        foreach (($album['artists'] ?? []) as $ar) {
            if (is_array($ar) && isset($ar['id']) && isset($ar['name'])) {
                $albumArtists[] = [
                    'id' => (string) $ar['id'],
                    'name' => (string) $ar['name'],
                ];
            }
        }

        $trackIds = [];
        $offset = 0;

        while (true) {
            $page = $spotify->getAlbumTracks($spotifyAlbumId, 50, $offset, $market);
            $items = $page['items'] ?? [];
            if (!is_array($items) || count($items) === 0) {
                break;
            }

            foreach ($items as $t) {
                if (is_array($t) && isset($t['id'])) {
                    $id = trim((string) $t['id']);
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
            return new JsonResponse([
                'album' => [
                    'albumId' => (string) ($album['id'] ?? $spotifyAlbumId),
                    'name' => (string) ($album['name'] ?? ''),
                    'picture' => $albumPicture,
                    'link' => (string) ($album['external_urls']['spotify'] ?? ''),
                    'releaseDate' => (string) ($album['release_date'] ?? ''),
                    'totalTracks' => isset($album['total_tracks']) ? (int) $album['total_tracks'] : null,
                    'artists' => $albumArtists,
                ],
                'tracks' => [],
            ]);
        }

        $tracksById = [];

        foreach (array_chunk($trackIds, 50) as $chunk) {
            $payload = $spotify->getTracks($chunk, $market);
            $tracks = $payload['tracks'] ?? [];
            if (!is_array($tracks)) {
                continue;
            }

            foreach ($tracks as $t) {
                if (is_array($t) && isset($t['id'])) {
                    $tracksById[(string) $t['id']] = $t;
                }
            }
        }

        $existing = $musicRepository->findBySpotifyIds($trackIds);
        $existingBySpotifyId = [];

        foreach ($existing as $m) {
            if ($m->getSpotifyId() !== null) {
                $existingBySpotifyId[$m->getSpotifyId()] = $m->getId();
            }
        }

        $tracks = [];

        foreach ($trackIds as $tid) {
            $spotifyTrack = $tracksById[$tid] ?? null;
            if (!is_array($spotifyTrack)) {
                continue;
            }

            $artists = [];
            foreach (($spotifyTrack['artists'] ?? []) as $a) {
                if (is_array($a) && isset($a['id']) && isset($a['name'])) {
                    $artists[] = [
                        'id' => (string) $a['id'],
                        'name' => (string) $a['name'],
                    ];
                }
            }

            $tracks[] = [
                'spotify' => [
                    'id' => (string) $spotifyTrack['id'],
                    'name' => (string) ($spotifyTrack['name'] ?? ''),
                    'external_urls' => [
                        'spotify' => (string) ($spotifyTrack['external_urls']['spotify'] ?? ''),
                    ],
                    'artists' => $artists,
                    'albumPicture' => $albumPicture,
                ],
                'local' => [
                    'isImported' => isset($existingBySpotifyId[$tid]),
                    'musicId' => $existingBySpotifyId[$tid] ?? null,
                ],
            ];
        }

        return new JsonResponse([
            'album' => [
                'albumId' => (string) ($album['id'] ?? $spotifyAlbumId),
                'name' => (string) ($album['name'] ?? ''),
                'picture' => $albumPicture,
                'link' => (string) ($album['external_urls']['spotify'] ?? ''),
                'releaseDate' => (string) ($album['release_date'] ?? ''),
                'totalTracks' => isset($album['total_tracks']) ? (int) $album['total_tracks'] : null,
                'artists' => $albumArtists,
            ],
            'tracks' => $tracks,
        ]);
    }

    public function importSpotifyTrack(string $spotifyTrackId, Request $request, SpotifyCatalogService $catalog): JsonResponse
    {
        $market = (string) $request->query->get('market', 'FR');

        $music = $catalog->importTrackById($spotifyTrackId, $market, true);

        $artists = [];
        foreach ($music->getArtists() as $a) {
            $artists[] = [
                'id' => $a->getId(),
                'name' => $a->getName(),
                'spotifyId' => $a->getSpotifyId(),
            ];
        }

        return new JsonResponse([
            'musicId' => $music->getId(),
            'spotifyId' => $music->getSpotifyId(),
            'title' => $music->getTitle(),
            'link' => $music->getLink(),
            'picture' => $music->getPicture(),
            'genre' => $music->getGenre(),
            'popularity' => $music->getPopularity(),
            'artists' => $artists,
        ], 201);
    }
}
