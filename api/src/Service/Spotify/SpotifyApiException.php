<?php

namespace App\Service\Spotify;

/**
 * Exception thrown when a Spotify API request fails.
 *
 * This exception is used for both Spotify Web API (api.spotify.com) and Spotify Accounts
 * (accounts.spotify.com) failures.
 *
 * When available, it carries:
 * - the HTTP status code returned by Spotify;
 * - the decoded response payload (usually JSON).
 */
class SpotifyApiException extends \RuntimeException
{
    /**
     * @var int|null HTTP status code returned by Spotify when known.
     */
    private ?int $statusCode;

    /**
     * @var mixed Decoded response payload when available.
     */
    private mixed $payload;

    /**
     * @param string $message Human-readable error message.
     * @param int|null $statusCode HTTP status code returned by Spotify (if available).
     * @param mixed $payload Decoded response payload (if available).
     * @param \Throwable|null $previous Previous exception.
     */
    public function __construct(string $message, ?int $statusCode = null, mixed $payload = null, ?\Throwable $previous = null)
    {
        parent::__construct($message, 0, $previous);
        $this->statusCode = $statusCode;
        $this->payload = $payload;
    }

    /**
     * Returns the HTTP status code when available.
     *
     * @return int|null The HTTP status code returned by Spotify, or null when unknown.
     */
    public function getStatusCode(): ?int
    {
        return $this->statusCode;
    }

    /**
     * Returns the decoded response payload when available.
     *
     * @return mixed The decoded payload, or null when unavailable.
     */
    public function getPayload(): mixed
    {
        return $this->payload;
    }
}
