<?php

namespace App\Controller;

use App\Entity\RefreshToken;
use App\Entity\User;
use App\Repository\UserRepository;
use App\Service\Spotify\SpotifyApiClient;
use App\Service\Spotify\SpotifyCatalogService;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Psr\Cache\InvalidArgumentException;
use Random\RandomException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;
use Throwable;

/**
 * Spotify OAuth endpoints (login, link/unlink) and Spotify-to-Melovibes synchronization utilities.
 *
 * This controller implements an Authorization Code flow with a short-lived server-side "state" entry
 * to protect against CSRF and a short-lived "handoff" code to safely transfer the result back to the frontend.
 *
 * The session endpoint exchanges the handoff code for HTTP-only cookies (BEARER JWT and refresh_token) used by the app.
 */
#[Route('/api/spotify')]
final class SpotifyConnectController extends AbstractController
{
    private const STATE_TTL_SECONDS = 600;
    private const HANDOFF_TTL_SECONDS = 90;

    /**
     * @param SpotifyApiClient $spotify Spotify API client.
     * @param SpotifyCatalogService $catalog Service responsible for importing Spotify data into local database.
     * @param UserRepository $users User repository.
     * @param EntityManagerInterface $em Entity manager.
     * @param CacheInterface $cache Cache for OAuth state/handoff storage.
     * @param JWTTokenManagerInterface $jwt JWT token manager.
     * @param UserPasswordHasherInterface $hasher Password hasher.
     * @param string $frontUrl Frontend base URL.
     * @param string $defaultMarket Default Spotify market (e.g. FR).
     */
    public function __construct(
        private readonly SpotifyApiClient $spotify,
        private readonly SpotifyCatalogService $catalog,
        private readonly UserRepository $users,
        private readonly EntityManagerInterface $em,
        private readonly CacheInterface $cache,
        private readonly JWTTokenManagerInterface $jwt,
        private readonly UserPasswordHasherInterface $hasher,
        #[Autowire('%env(FRONT_URL)%')] private readonly string $frontUrl,
        #[Autowire('%env(SPOTIFY_DEFAULT_MARKET)%')] private readonly string $defaultMarket = 'FR',
    ) {
    }

    /**
     * Starts Spotify OAuth login flow.
     *
     * @return RedirectResponse
     * @throws RandomException
     * @throws InvalidArgumentException
     */
    #[Route('/login', name: 'spotify_login', methods: ['GET'])]
    public function login(): RedirectResponse
    {
        $state = $this->storeState(['flow' => 'login']);

        $url = $this->spotify->buildUserAuthorizeUrl($state, [
            'user-read-email',
            'user-read-private',
            'user-library-read',
        ]);

        return new RedirectResponse($url);
    }

    /**
     * Starts Spotify OAuth linking flow for the currently authenticated user.
     *
     * @return RedirectResponse
     * @throws RandomException
     * @throws InvalidArgumentException
     */
    #[Route('/link', name: 'spotify_link', methods: ['GET'])]
    public function link(): RedirectResponse
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            return $this->redirectFront('/login?spotify=unauth');
        }

        $state = $this->storeState(['flow' => 'link', 'userId' => $user->getId()]);

        $url = $this->spotify->buildUserAuthorizeUrl($state, [
            'user-read-email',
            'user-read-private',
            'user-library-read',
        ]);

        return new RedirectResponse($url);
    }

    /**
     * Handles Spotify OAuth callback and redirects to frontend handoff page.
     *
     * @param Request $request
     * @return RedirectResponse
     * @throws RandomException
     * @throws InvalidArgumentException
     */
    #[Route('/callback', name: 'spotify_callback', methods: ['GET'])]
    public function callback(Request $request): RedirectResponse
    {
        $code = trim((string) $request->query->get('code', ''));
        $state = trim((string) $request->query->get('state', ''));
        $error = $request->query->get('error');

        if (is_string($error) && $error !== '') {
            return $this->redirectFront('/login?spotify=denied');
        }

        if ($code === '' || $state === '') {
            return $this->redirectFront('/login?spotify=missing');
        }

        $ctx = $this->consumeState($state);
        if ($ctx === null) {
            return $this->redirectFront('/login?spotify=state');
        }

        $tokenPayload = $this->spotify->exchangeUserAuthorizationCode($code);

        $accessToken = (string) ($tokenPayload['access_token'] ?? '');
        $refreshToken = isset($tokenPayload['refresh_token']) ? (string) $tokenPayload['refresh_token'] : null;
        $expiresIn = (int) ($tokenPayload['expires_in'] ?? 0);

        if ($accessToken === '' || $expiresIn <= 0) {
            return $this->redirectFront('/login?spotify=token');
        }

        $expiresAt = (new DateTimeImmutable())->modify(sprintf('+%d seconds', max(1, $expiresIn)));

        $me = $this->spotify->userGetMe($accessToken);

        $spotifyId = isset($me['id']) ? trim((string) $me['id']) : '';
        $spotifyEmail = isset($me['email']) ? trim((string) $me['email']) : '';
        $displayName = isset($me['display_name']) ? trim((string) $me['display_name']) : null;

        if ($spotifyId === '') {
            return $this->redirectFront('/login?spotify=profile');
        }

        $flow = (string) ($ctx['flow'] ?? 'login');

        if ($flow === 'link') {
            return $this->handleLinkFlow($ctx, $spotifyId, $displayName, $accessToken, $refreshToken, $expiresAt);
        }

        return $this->handleLoginFlow($spotifyId, $spotifyEmail, $displayName, $accessToken, $refreshToken, $expiresAt);
    }

    /**
     * Finalizes the OAuth handoff by setting auth cookies for the user.
     *
     * @param Request $request
     * @return JsonResponse
     * @throws RandomException
     * @throws InvalidArgumentException
     */
    #[Route('/session', name: 'spotify_session', methods: ['POST'])]
    public function session(Request $request): JsonResponse
    {
        $payload = $request->toArray();
        $code = isset($payload['code']) ? trim((string) $payload['code']) : '';

        $handoff = $this->consumeHandoff($code);
        if ($handoff === null) {
            return $this->json(['message' => 'Invalid code'], 400);
        }

        $user = $this->users->find((int) $handoff['userId']);
        if (!$user instanceof User) {
            return $this->json(['message' => 'User not found'], 404);
        }

        $response = $this->json([
            'success' => true,
            'tempPassword' => $handoff['tempPassword'] ?? null,
        ]);

        $this->attachSessionCookies($response, $user);

        return $response;
    }

    /**
     * Unlinks Spotify account from the currently authenticated user.
     *
     * @return JsonResponse
     */
    #[Route('/unlink', name: 'spotify_unlink', methods: ['POST'])]
    public function unlink(): JsonResponse
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            return $this->json(['message' => 'Unauthenticated'], 401);
        }

        $user->clearSpotifyLink();
        $this->em->flush();

        return $this->json(['success' => true]);
    }

    /**
     * Re-syncs the user's Spotify "Liked tracks" into Melovibes favorites.
     *
     * @return JsonResponse
     */
    #[Route('/sync-favorites', name: 'spotify_sync_favorites', methods: ['POST'])]
    public function syncFavorites(): JsonResponse
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            return $this->json(['message' => 'Unauthenticated'], 401);
        }

        if ($user->getSpotifyId() === null) {
            return $this->json(['message' => 'Spotify not linked'], 400);
        }

        $accessToken = $user->getSpotifyAccessToken();
        if ($accessToken === null || trim($accessToken) === '') {
            return $this->json(['message' => 'Spotify access token missing'], 400);
        }

        try {
            $result = $this->catalog->importUserLikedTracksToFavorites($user, $accessToken, $this->defaultMarket);
        } catch (Throwable) {
            return $this->json(['message' => 'Spotify sync failed'], 502);
        }

        return $this->json([
            'success' => true,
            'added' => $result['added'] ?? 0,
            'scanned' => $result['scanned'] ?? 0,
        ]);
    }

    /**
     * @param array<string, mixed> $ctx
     * @param string $spotifyId
     * @param string|null $displayName
     * @param string $accessToken
     * @param string|null $refreshToken
     * @param DateTimeImmutable $expiresAt
     * @return RedirectResponse
     * @throws RandomException
     * @throws InvalidArgumentException
     */
    private function handleLinkFlow(array $ctx, string $spotifyId, ?string $displayName, string $accessToken, ?string $refreshToken, DateTimeImmutable $expiresAt): RedirectResponse
    {
        $userId = isset($ctx['userId']) ? (int) $ctx['userId'] : 0;

        $user = $this->users->find($userId);
        if (!$user instanceof User) {
            return $this->redirectFront('/login?spotify=link_user');
        }

        $already = $this->users->findOneBySpotifyId($spotifyId);
        if ($already instanceof User && $already->getId() !== $user->getId()) {
            return $this->redirectFront('/profile?spotify=already_linked');
        }

        $this->setSpotifyDatas($user, $spotifyId, $displayName, $accessToken, $refreshToken, $expiresAt);

        $code = $this->storeHandoff(['userId' => $user->getId()]);
        return $this->redirectFront('/auth/spotify?code=' . rawurlencode($code) . '&linked=1');
    }

    /**
     * @param string $spotifyId
     * @param string $spotifyEmail
     * @param string|null $displayName
     * @param string $accessToken
     * @param string|null $refreshToken
     * @param DateTimeImmutable $expiresAt
     * @return RedirectResponse
     * @throws RandomException
     * @throws InvalidArgumentException
     */
    private function handleLoginFlow(string $spotifyId, string $spotifyEmail, ?string $displayName, string $accessToken, ?string $refreshToken, DateTimeImmutable $expiresAt): RedirectResponse
    {
        $tempPassword = null;

        $user = $this->users->findOneBySpotifyId($spotifyId);

        if (!$user instanceof User && $spotifyEmail !== '') {
            $user = $this->users->findOneBy(['email' => $spotifyEmail]);
        }

        if (!$user instanceof User) {
            $user = new User();

            $email = $spotifyEmail !== '' ? $spotifyEmail : sprintf('%s@spotify.local', $spotifyId);
            $user->setEmail($email);

            $loginBase = $displayName ?: ('spotify_' . $spotifyId);
            $user->setLogin($this->generateUniqueLogin($loginBase));

            $tempPassword = bin2hex(random_bytes(10));
            $user->setPassword($this->hasher->hashPassword($user, $tempPassword));

            $this->em->persist($user);
        }

        $already = $this->users->findOneBySpotifyId($spotifyId);
        if ($already instanceof User && $already->getId() !== $user->getId()) {
            return $this->redirectFront('/login?spotify=already_linked');
        }

        $this->setSpotifyDatas($user, $spotifyId, $displayName, $accessToken, $refreshToken, $expiresAt);

        $handoff = ['userId' => $user->getId()];
        if ($tempPassword !== null) {
            $handoff['tempPassword'] = $tempPassword;
        }

        $code = $this->storeHandoff($handoff);

        return $this->redirectFront('/auth/spotify?code=' . rawurlencode($code));
    }

    /**
     * @param Response $response
     * @param User $user
     * @return void
     * @throws RandomException
     */
    private function attachSessionCookies(Response $response, User $user): void
    {
        $jwt = $this->jwt->create($user);

        $response->headers->setCookie(
            Cookie::create('BEARER', $jwt)
                ->withPath('/')
                ->withHttpOnly(true)
                ->withSecure(false)
                ->withSameSite('lax')
        );

        $username = $user->getUserIdentifier();

        $existing = $this->em->getRepository(RefreshToken::class)->findBy(['username' => $username]);
        foreach ($existing as $rt) {
            $this->em->remove($rt);
        }

        $refresh = new RefreshToken();
        $refresh->setUsername($username);
        $refresh->setRefreshToken(bin2hex(random_bytes(64)));
        $refresh->setValid((new \DateTime())->modify('+30 days'));

        $this->em->persist($refresh);
        $this->em->flush();

        $response->headers->setCookie(
            Cookie::create('refresh_token', $refresh->getRefreshToken())
                ->withPath('/')
                ->withHttpOnly(true)
                ->withSecure(false)
                ->withSameSite('lax')
        );
    }

    /**
     * @param array<string, mixed> $payload
     * @return string
     * @throws RandomException
     * @throws InvalidArgumentException
     */
    private function storeState(array $payload): string
    {
        $state = bin2hex(random_bytes(24));
        $key = 'spotify.oauth.state.' . $state;

        $this->cache->get($key, function (ItemInterface $item) use ($payload): array {
            $item->expiresAfter(self::STATE_TTL_SECONDS);
            return $payload;
        });

        return $state;
    }

    /**
     * @param string $state
     * @return array<string, mixed>|null
     * @throws InvalidArgumentException
     */
    private function consumeState(string $state): ?array
    {
        $key = 'spotify.oauth.state.' . $state;

        $payload = $this->cache->get($key, function (ItemInterface $item): array {
            $item->expiresAfter(1);
            return [];
        });

        $this->cache->delete($key);

        if (!is_array($payload) || count($payload) === 0) {
            return null;
        }

        return $payload;
    }

    /**
     * @param array<string, mixed> $payload
     * @return string
     * @throws RandomException
     * @throws InvalidArgumentException
     */
    private function storeHandoff(array $payload): string
    {
        $code = bin2hex(random_bytes(24));
        $key = 'spotify.session.handoff.' . $code;

        $this->cache->get($key, function (ItemInterface $item) use ($payload): array {
            $item->expiresAfter(self::HANDOFF_TTL_SECONDS);
            return $payload;
        });

        return $code;
    }

    /**
     * @param string $code
     * @return array<string, mixed>|null
     * @throws InvalidArgumentException
     */
    private function consumeHandoff(string $code): ?array
    {
        $code = trim($code);
        if ($code === '') {
            return null;
        }

        $key = 'spotify.session.handoff.' . $code;

        $payload = $this->cache->get($key, function (ItemInterface $item): array {
            $item->expiresAfter(1);
            return [];
        });

        $this->cache->delete($key);

        if (!is_array($payload) || !isset($payload['userId'])) {
            return null;
        }

        return $payload;
    }

    /**
     * @param string $path
     * @return RedirectResponse
     */
    private function redirectFront(string $path): RedirectResponse
    {
        $base = rtrim(trim($this->frontUrl), '/');

        $p = trim($path);
        if ($p === '') {
            $p = '/';
        } elseif ($p[0] !== '/') {
            $p = '/' . $p;
        }

        return new RedirectResponse($base . $p);
    }

    /**
     * @param string $base
     * @return string
     * @throws RandomException
     */
    private function generateUniqueLogin(string $base): string
    {
        $candidate = preg_replace('/[^a-zA-Z0-9_\-]/', '', $base) ?? 'spotify';
        $candidate = trim($candidate);
        if ($candidate === '') {
            $candidate = 'spotify';
        }

        $candidate = substr($candidate, 0, 24);

        $i = 0;
        $login = $candidate;

        while ($this->users->findOneBy(['login' => $login]) !== null) {
            $i++;
            $suffix = (string) $i;
            $login = substr($candidate, 0, max(4, 30 - 1 - strlen($suffix))) . '_' . $suffix;
            if ($i > 50) {
                $login = 'spotify_' . bin2hex(random_bytes(4));
                break;
            }
        }

        return $login;
    }

    /**
     * @param User $user
     * @param string $spotifyId
     * @param string|null $displayName
     * @param string $accessToken
     * @param string|null $refreshToken
     * @param DateTimeImmutable $expiresAt
     * @return void
     */
    private function setSpotifyDatas(User $user, string $spotifyId, ?string $displayName, string $accessToken, ?string $refreshToken, DateTimeImmutable $expiresAt): void
    {
        $user->setSpotifyId($spotifyId);
        $user->setSpotifyDisplayName($displayName);
        $user->setSpotifyAccessToken($accessToken);
        if ($refreshToken !== null) {
            $user->setSpotifyRefreshToken($refreshToken);
        }
        $user->setSpotifyAccessTokenExpiresAt($expiresAt);

        $this->em->flush();

        try {
            $this->catalog->importUserLikedTracksToFavorites($user, $accessToken, $this->defaultMarket);
        } catch (Throwable) {
        }
    }
}
