<?php

namespace App\Service\Spotify;

/**
 * Represents an error returned by Spotify Web API or Spotify Accounts.
 */
class SpotifyApiException extends \RuntimeException
{
    private ?int $statusCode;

    private mixed $payload;

    public function __construct(string $message, ?int $statusCode = null, mixed $payload = null, ?\Throwable $previous = null)
    {
        parent::__construct($message, 0, $previous);
        $this->statusCode = $statusCode;
        $this->payload = $payload;
    }

    /**
     * Returns the HTTP status code when available.
     */
    public function getStatusCode(): ?int
    {
        return $this->statusCode;
    }

    /**
     * Returns the parsed response payload when available.
     */
    public function getPayload(): mixed
    {
        return $this->payload;
    }
}
