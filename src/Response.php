<?php

declare(strict_types=1);

namespace SuperKernel\HttpMessage;

use InvalidArgumentException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;
use SuperKernel\HttpMessage\StartLine\ResponseStatus;

class Response extends Message implements ResponseInterface
{
    private int $statusCode;

    private string $reasonPhrase;

    /**
     * @param array<string, string|string[]> $headers
     *
     * @throws InvalidArgumentException When the status, reason phrase, protocol version, or a header is invalid
     */
    public function __construct(
        int                         $statusCode = 200,
        array                       $headers = [],
        StreamInterface|string|null $body = null,
        string                      $protocolVersion = '1.1',
        string                      $reasonPhrase = '',
    )
    {
        parent::__construct($headers, $body, $protocolVersion);

        [$this->statusCode, $this->reasonPhrase] = ResponseStatus::normalize($statusCode, $reasonPhrase);
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    public function withStatus(int $code, string $reasonPhrase = ''): static
    {
        [$code, $reasonPhrase] = ResponseStatus::normalize($code, $reasonPhrase);

        $clone = clone $this;
        $clone->statusCode = $code;
        $clone->reasonPhrase = $reasonPhrase;

        return $clone;
    }

    public function getReasonPhrase(): string
    {
        return $this->reasonPhrase;
    }
}
