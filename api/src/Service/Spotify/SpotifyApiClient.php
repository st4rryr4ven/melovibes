<?php

namespace App\Service\Spotify;

use Psr\Cache\InvalidArgumentException;
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
 * Spotify Web API client.
 *
 * Responsibilities:
 * - Implements the Client Credentials flow for application-level access to the Spotify catalog.
 * - Implements Authorization Code helpers for user-level actions (profile, liked tracks).
 * - Provides convenience wrappers for the endpoints used by the project (search, new releases, albums, tracks, artists).
 *
 * Error handling:
 * - Throws {@see SpotifyApiException} when Spotify responds with a non-2xx status or when transport errors occur.
 * - Implements basic retry logic for transient failures and rate limiting (HTTP 429 with Retry-After).
 */
class SpotifyApiClient
{
    /**
     * Cache key used to store the app access token obtained via Client Credentials.
     */
    private const TOKEN_CACHE_KEY = 'spotify.app_access_token';

    private string $clientId;

    private string $clientSecret;

    private string $defaultMarket;

    private string $redirectUri;

    private string $apiBaseUrl = 'https://api.spotify.com/v1';

    private string $accountsAuthorizeUrl = 'https://accounts.spotify.com/authorize';

    private string $accountsTokenUrl = 'https://accounts.spotify.com/api/token';

    /**
     * @param HttpClientInterface $httpClient Symfony HTTP client.
     * @param CacheInterface $cache Cache storage used for the app access token.
     * @param string $clientId Spotify app client id.
     * @param string $clientSecret Spotify app client secret.
     * @param string $defaultMarket Default market/country used by Spotify endpoints (e.g. FR).
     * @param string $redirectUri Redirect URI configured in Spotify Developer Dashboard (Authorization Code flow).
     */
    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly CacheInterface $cache,
        #[Autowire('%env(CLIENT_ID)%')] string $clientId,
        #[Autowire('%env(CLIENT_SECRET)%')] string $clientSecret,
        #[Autowire('%env(SPOTIFY_DEFAULT_MARKET)%')] string $defaultMarket = 'FR',
        #[Autowire('%env(SPOTIFY_REDIRECT_URI)%')] string $redirectUri = '',
    ) {
        $this->clientId = $clientId;
        $this->clientSecret = $clientSecret;
        $this->defaultMarket = $defaultMarket;
        $this->redirectUri = $redirectUri;
        $this->apiBaseUrl = rtrim($this->apiBaseUrl, '/');
    }

    /**
     * Returns the application access token (Client Credentials flow).
     *
     * The token is cached to avoid requesting a new token for every API call.
     *
     * @return string OAuth access token for Spotify Web API.
     *
     * @throws SpotifyApiException|InvalidArgumentException When token retrieval fails.
     */
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
     * Builds the Spotify Accounts authorization URL (Authorization Code flow).
     *
     * @param string $state CSRF protection value that must be validated on callback.
     * @param string[] $scopes OAuth scopes requested (space-separated by Spotify).
     * @param bool $showDialog Whether Spotify should force re-approval dialog.
     *
     * @return string Fully-qualified URL to redirect the user to.
     *
     * @throws SpotifyApiException When SPOTIFY_REDIRECT_URI is missing.
     */
    public function buildUserAuthorizeUrl(string $state, array $scopes, bool $showDialog = false): string
    {
        if (trim($this->redirectUri) === '') {
            throw new SpotifyApiException('SPOTIFY_REDIRECT_URI is missing');
        }

        $query = http_build_query([
            'response_type' => 'code',
            'client_id' => $this->clientId,
            'redirect_uri' => $this->redirectUri,
            'scope' => implode(' ', array_values(array_unique($scopes))),
            'state' => $state,
            'show_dialog' => $showDialog ? 'true' : 'false',
        ]);

        return $this->accountsAuthorizeUrl . '?' . $query;
    }

    /**
     * Exchanges an authorization code for a user access token (and refresh token).
     *
     * @param string $code Authorization code received on the callback endpoint.
     *
     * @return array<string, mixed> Spotify token response payload.
     *
     * @throws SpotifyApiException When SPOTIFY_REDIRECT_URI is missing or the exchange fails.
     */
    public function exchangeUserAuthorizationCode(string $code): array
    {
        if (trim($this->redirectUri) === '') {
            throw new SpotifyApiException('SPOTIFY_REDIRECT_URI is missing');
        }

        return $this->requestAccountsToken([
            'grant_type' => 'authorization_code',
            'code' => $code,
            'redirect_uri' => $this->redirectUri,
        ]);
    }

    /**
     * Refreshes a user access token.
     *
     * @param string $refreshToken Spotify refresh token.
     *
     * @return array<string, mixed> Spotify token response payload.
     *
     * @throws SpotifyApiException When the refresh request fails.
     */
    public function refreshUserAccessToken(string $refreshToken): array
    {
        return $this->requestAccountsToken([
            'grant_type' => 'refresh_token',
            'refresh_token' => $refreshToken,
        ]);
    }

    /**
     * Fetches the current user's Spotify profile.
     *
     * @param string $accessToken User access token.
     *
     * @return array<string, mixed> Spotify profile payload.
     *
     * @throws SpotifyApiException When the request fails.
     */
    public function userGetMe(string $accessToken): array
    {
        return $this->requestJson('GET', $this->apiBaseUrl . '/me', [], null, $accessToken, []);
    }

    /**
     * Fetches the current user's saved tracks ("Liked Songs").
     *
     * @param string $accessToken User access token.
     * @param int $limit Page size (1..50).
     * @param int $offset Offset.
     * @param string|null $market Spotify market (defaults to configured default market).
     *
     * @return array<string, mixed> Spotify saved tracks payload.
     *
     * @throws SpotifyApiException When the request fails.
     */
    public function userGetSavedTracks(string $accessToken, int $limit = 50, int $offset = 0, ?string $market = null): array
    {
        return $this->requestJson('GET', $this->apiBaseUrl . '/me/tracks', [
            'limit' => max(1, min(50, $limit)),
            'offset' => max(0, $offset),
            'market' => $market ?? $this->defaultMarket,
        ], null, $accessToken, []);
    }

    /**
     * Searches the Spotify catalog.
     *
     * @param string $query Search query.
     * @param string[] $types Spotify search types (e.g. track, artist, album).
     * @param int $limit Page size.
     * @param int $offset Offset.
     * @param string|null $market Market code (defaults to configured default market).
     *
     * @return array<string, mixed> Spotify search payload.
     *
     * @throws SpotifyApiException When the request fails.
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

    /**
     * Returns the Spotify "New Releases" albums.
     *
     * @param int $limit Page size.
     * @param int $offset Offset.
     * @param string|null $country Market/country code (defaults to configured default market).
     *
     * @return array<string, mixed> Spotify new releases payload.
     *
     * @throws SpotifyApiException When the request fails.
     */
    public function getNewReleases(int $limit = 20, int $offset = 0, ?string $country = null): array
    {
        return $this->apiGet('/browse/new-releases', [
            'limit' => $limit,
            'offset' => $offset,
            'country' => $country ?? $this->defaultMarket,
        ]);
    }

    /**
     * Fetches a Spotify album.
     *
     * @param string $albumId Spotify album id.
     * @param string|null $market Market code (defaults to configured default market).
     *
     * @return array<string, mixed> Album payload.
     *
     * @throws SpotifyApiException When the request fails.
     */
    public function getAlbum(string $albumId, ?string $market = null): array
    {
        return $this->apiGet('/albums/' . rawurlencode($albumId), [
            'market' => $market ?? $this->defaultMarket,
        ]);
    }

    /**
     * Fetches tracks of a Spotify album.
     *
     * @param string $albumId Spotify album id.
     * @param int $limit Page size (1..50).
     * @param int $offset Offset.
     * @param string|null $market Market code (defaults to configured default market).
     *
     * @return array<string, mixed> Album tracks payload.
     *
     * @throws SpotifyApiException When the request fails.
     */
    public function getAlbumTracks(string $albumId, int $limit = 50, int $offset = 0, ?string $market = null): array
    {
        return $this->apiGet('/albums/' . rawurlencode($albumId) . '/tracks', [
            'limit' => $limit,
            'offset' => $offset,
            'market' => $market ?? $this->defaultMarket,
        ]);
    }

    /**
     * Fetches a Spotify track.
     *
     * @param string $trackId Spotify track id.
     * @param string|null $market Market code (defaults to configured default market).
     *
     * @return array<string, mixed> Track payload.
     *
     * @throws SpotifyApiException When the request fails.
     */
    public function getTrack(string $trackId, ?string $market = null): array
    {
        return $this->apiGet('/tracks/' . rawurlencode($trackId), [
            'market' => $market ?? $this->defaultMarket,
        ]);
    }

    /**
     * Fetches multiple Spotify tracks.
     *
     * @param string[] $trackIds Spotify track ids (max 50).
     * @param string|null $market Market code (defaults to configured default market).
     *
     * @return array<string, mixed> Tracks payload.
     *
     * @throws SpotifyApiException When the request fails.
     */
    public function getTracks(array $trackIds, ?string $market = null): array
    {
        return $this->apiGet('/tracks', [
            'ids' => implode(',', array_values($trackIds)),
            'market' => $market ?? $this->defaultMarket,
        ]);
    }

    /**
     * Fetches multiple Spotify artists.
     *
     * @param string[] $artistIds Spotify artist ids (max 50).
     *
     * @return array<string, mixed> Artists payload.
     *
     * @throws SpotifyApiException When the request fails.
     */
    public function getArtists(array $artistIds): array
    {
        return $this->apiGet('/artists', [
            'ids' => implode(',', array_values($artistIds)),
        ]);
    }

    /**
     * Fetches a Spotify artist.
     *
     * @param string $artistId Spotify artist id.
     *
     * @return array<string, mixed> Artist payload.
     *
     * @throws SpotifyApiException When the request fails.
     */
    public function getArtist(string $artistId): array
    {
        return $this->apiGet('/artists/' . rawurlencode($artistId));
    }

    /**
     * Fetches the Spotify top tracks for an artist.
     *
     * @param string $artistId Spotify artist id.
     * @param string|null $market Market code (defaults to configured default market).
     *
     * @return array<string, mixed> Top tracks payload.
     *
     * @throws SpotifyApiException When the request fails.
     */
    public function getArtistTopTracks(string $artistId, ?string $market = null): array
    {
        return $this->apiGet('/artists/' . rawurlencode($artistId) . '/top-tracks', [
            'market' => $market ?? $this->defaultMarket,
        ]);
    }

    /**
     * Performs a GET request against the Spotify Web API using the application access token.
     *
     * @param string $path API path (must start with '/').
     * @param array<string, mixed> $query Query parameters.
     *
     * @return array<string, mixed> Decoded JSON response.
     *
     * @throws SpotifyApiException When the request fails.
     */
    private function apiGet(string $path, array $query = []): array
    {
        return $this->requestJson('GET', $this->apiBaseUrl . $path, $query, null, $this->getAppAccessToken(), []);
    }

    /**
     * Performs a token request against Spotify Accounts service.
     *
     * @param array<string, mixed> $form Form-encoded payload.
     *
     * @return array<string, mixed> Decoded JSON response.
     *
     * @throws SpotifyApiException When the request fails.
     */
    private function requestAccountsToken(array $form): array
    {
        $auth = base64_encode($this->clientId . ':' . $this->clientSecret);

        return $this->requestJson('POST', $this->accountsTokenUrl, [], $form, null, [
            'Authorization' => 'Basic ' . $auth,
        ]);
    }

    /**
     * Executes an HTTP request and returns the decoded JSON response.
     *
     * Retries:
     * - Up to 3 attempts for transport/client/server errors.
     * - Up to 4 attempts for rate limiting (HTTP 429) when Retry-After is present.
     *
     * @param string $method HTTP method.
     * @param string $url Full URL.
     * @param array<string, mixed> $query Query parameters.
     * @param array<string, mixed>|null $formBody Form-encoded body (for Accounts endpoints).
     * @param string|null $bearerToken Bearer token, when required.
     * @param array<string, string> $extraHeaders Extra headers.
     *
     * @return array<string, mixed> Decoded JSON response.
     *
     * @throws SpotifyApiException When all retry attempts fail or Spotify returns a non-2xx response.
     */
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

    /**
     * Decodes a Spotify response and throws an exception when the status code is not successful.
     *
     * @param ResponseInterface $response HTTP response.
     *
     * @return array<string, mixed> Decoded JSON payload.
     *
     * @throws SpotifyApiException When status is not 2xx.
     * @throws TransportExceptionInterface
     * @throws ServerExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ClientExceptionInterface
     */
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

    /**
     * Reads Spotify rate limiting headers.
     *
     * @param ResponseInterface $response HTTP response.
     *
     * @return int Number of seconds to wait before retrying.
     *
     * @throws TransportExceptionInterface
     * @throws ServerExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ClientExceptionInterface
     */
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

    /**
     * Normalizes query parameters by removing null values and stringifying booleans.
     *
     * @param array<string, mixed> $query Query parameters.
     *
     * @return array<string, mixed> Normalized query parameters.
     */
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
