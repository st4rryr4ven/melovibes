<?php

namespace App\Service\Spotify;

use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

/**
 * Spotify Web API client using Client Credentials flow for catalog access.
 */
class SpotifyApiClient
{
    private const TOKEN_CACHE_KEY = 'spotify.app_access_token';

    private string $clientId;

    private string $clientSecret;

    private string $defaultMarket;

    private string $apiBaseUrl = 'https://api.spotify.com/v1';

    private string $accountsTokenUrl = 'https://accounts.spotify.com/api/token';

    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly CacheInterface $cache,
        #[Autowire('%env(CLIENT_ID)%')] string $clientId,
        #[Autowire('%env(CLIENT_SECRET)%')] string $clientSecret,
        #[Autowire('%env(SPOTIFY_DEFAULT_MARKET)%')] string $defaultMarket = 'FR',
    ) {
        $this->clientId = $clientId;
        $this->clientSecret = $clientSecret;
        $this->defaultMarket = $defaultMarket;
        $this->apiBaseUrl = rtrim($this->apiBaseUrl, '/');
    }

    public function getAppAccessToken(): string
    {
        return $this->cache->get(self::TOKEN_CACHE_KEY, function (ItemInterface $item): string {
            $token = $this->requestAccountsToken(['grant_type' => 'client_credentials']);
            $ttl = max(1, (int) ($token['expires_in'] ?? 3600) - 60);
            $item->expiresAfter($ttl);

            return (string) $token['access_token'];
        });
    }

    /**
     * @param string[] $types
     */
    public function search(string $query, array $types = ['track'], int $limit = 20, int $offset = 0, ?string $market = null): array
    {
        return $this->apiGet('/search', [
            'q' => $query,
            'type' => implode(',', $types),
            'limit' => $limit,
            'offset' => $offset,
            'market' => $market ?? $this->defaultMarket,
        ]);
    }

    public function getNewReleases(int $limit = 20, int $offset = 0, ?string $country = null): array
    {
        return $this->apiGet('/browse/new-releases', [
            'limit' => $limit,
            'offset' => $offset,
            'country' => $country ?? $this->defaultMarket,
        ]);
    }

    public function getAlbumTracks(string $albumId, int $limit = 50, int $offset = 0, ?string $market = null): array
    {
        return $this->apiGet('/albums/' . rawurlencode($albumId) . '/tracks', [
            'limit' => $limit,
            'offset' => $offset,
            'market' => $market ?? $this->defaultMarket,
        ]);
    }

    public function getTrack(string $trackId, ?string $market = null): array
    {
        return $this->apiGet('/tracks/' . rawurlencode($trackId), [
            'market' => $market ?? $this->defaultMarket,
        ]);
    }

    /**
     * @param string[] $trackIds
     */
    public function getTracks(array $trackIds, ?string $market = null): array
    {
        return $this->apiGet('/tracks', [
            'ids' => implode(',', array_values($trackIds)),
            'market' => $market ?? $this->defaultMarket,
        ]);
    }

    /**
     * @param string[] $artistIds
     */
    public function getArtists(array $artistIds): array
    {
        return $this->apiGet('/artists', [
            'ids' => implode(',', array_values($artistIds)),
        ]);
    }

    public function getArtist(string $artistId): array
    {
        return $this->apiGet('/artists/' . rawurlencode($artistId));
    }

    public function getArtistTopTracks(string $artistId, ?string $market = null): array
    {
        return $this->apiGet('/artists/' . rawurlencode($artistId) . '/top-tracks', [
            'market' => $market ?? $this->defaultMarket,
        ]);
    }

    private function apiGet(string $path, array $query = []): array
    {
        return $this->requestJson('GET', $this->apiBaseUrl . $path, $query, null, $this->getAppAccessToken(), []);
    }

    private function requestAccountsToken(array $form): array
    {
        $auth = base64_encode($this->clientId . ':' . $this->clientSecret);

        return $this->requestJson('POST', $this->accountsTokenUrl, [], $form, null, [
            'Authorization' => 'Basic ' . $auth,
        ]);
    }

    private function requestJson(
        string $method,
        string $url,
        array $query = [],
        ?array $formBody = null,
        ?string $bearerToken = null,
        array $extraHeaders = []
    ): array {
        $headers = array_merge($extraHeaders, [
            'Accept' => 'application/json',
        ]);

        if ($bearerToken !== null) {
            $headers['Authorization'] = 'Bearer ' . $bearerToken;
        }

        $options = [
            'headers' => $headers,
            'query' => $this->normalizeQuery($query),
        ];

        if ($formBody !== null) {
            $options['body'] = $formBody;
        }

        $attempts = 0;

        while (true) {
            $attempts++;

            try {
                $response = $this->httpClient->request($method, $url, $options);
                $status = $response->getStatusCode();

                if ($status === 429 && $attempts < 4) {
                    $retryAfter = $this->getRetryAfterSeconds($response);
                    if ($retryAfter > 0) {
                        sleep($retryAfter);
                        continue;
                    }
                }

                return $this->decodeJsonResponse($response);
            } catch (TransportExceptionInterface|ClientExceptionInterface|RedirectionExceptionInterface|ServerExceptionInterface $e) {
                if ($attempts < 3) {
                    usleep(200000);
                    continue;
                }

                throw new SpotifyApiException($e->getMessage(), null, null, $e);
            }
        }
    }

    private function decodeJsonResponse(ResponseInterface $response): array
    {
        $status = $response->getStatusCode();
        $content = $response->getContent(false);
        $data = null;

        if ($content !== '') {
            $data = json_decode($content, true);
        }

        if ($status < 200 || $status >= 300) {
            $message = 'Spotify API request failed';
            if (is_array($data)) {
                $message = (string) ($data['error']['message'] ?? $data['error_description'] ?? $data['message'] ?? $message);
            }

            throw new SpotifyApiException($message, $status, $data);
        }

        return is_array($data) ? $data : [];
    }

    private function getRetryAfterSeconds(ResponseInterface $response): int
    {
        $headers = $response->getHeaders(false);
        $values = $headers['retry-after'] ?? $headers['Retry-After'] ?? null;
        if (!is_array($values) || count($values) === 0) {
            return 0;
        }

        $seconds = (int) trim((string) $values[0]);

        return max(0, $seconds);
    }

    private function normalizeQuery(array $query): array
    {
        $normalized = [];

        foreach ($query as $key => $value) {
            if ($value === null) {
                continue;
            }

            $normalized[$key] = is_bool($value) ? ($value ? 'true' : 'false') : $value;
        }

        return $normalized;
    }
}
