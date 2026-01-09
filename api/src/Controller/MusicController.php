<?php

namespace App\Controller;

use App\Repository\MusicRepository;
use App\Service\Spotify\SpotifyApiClient;
use App\Service\Spotify\SpotifyCatalogService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Music endpoints: local-first search, curated lists, and on-demand Spotify imports.
 */
#[Route('/api/music', name: 'api_music_')]
class MusicController extends AbstractController
{
    #[Route('/search', name: 'search', methods: ['GET'])]
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

    #[Route('/new-releases', name: 'new_releases', methods: ['GET'])]
    public function newReleases(Request $request, MusicRepository $musicRepository): JsonResponse
    {
        $limit = max(1, min(50, (int) $request->query->get('limit', 20)));
        $offset = max(0, (int) $request->query->get('offset', 0));

        $source = (string) $request->query->get('source', 'spotify:new_releases');

        $total = $musicRepository->countNewReleases($source);
        $rows = $musicRepository->findNewReleases($source, $limit, $offset);

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
                'importSource' => $m->getImportSource(),
                'importedAt' => $m->getImportedAt()?->format(DATE_ATOM),
                'artists' => $artists,
            ];
        }

        return new JsonResponse([
            'items' => $items,
            'meta' => [
                'limit' => $limit,
                'offset' => $offset,
                'total' => $total,
                'source' => $source,
            ],
        ]);
    }

    #[Route('/import/spotify/{spotifyTrackId}', name: 'import_spotify_track', methods: ['POST'])]
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
