<?php

declare(strict_types=1);

namespace SuperKernel\HttpMessage;

use InvalidArgumentException;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\UploadedFileInterface;
use Psr\Http\Message\UriInterface;
use SuperKernel\HttpMessage\Upload\UploadedFileTree;

use function array_key_exists;
use function get_debug_type;
use function is_array;
use function is_object;
use function is_string;
use function sprintf;

class ServerRequest extends Request implements ServerRequestInterface
{
    /** @var array<string, mixed> */
    private array $serverParams;

    /** @var array<string, mixed> */
    private array $cookieParams;

    /** @var array<string, mixed> */
    private array $queryParams;

    /** @var array<array-key, UploadedFileInterface|array> */
    private array $uploadedFiles;

    private array|object|null $parsedBody;

    /** @var array<string, mixed> */
    private array $attributes;

    /**
     * @param array<string, mixed>                          $serverParams
     * @param array<string, string|string[]>                $headers
     * @param array<string, mixed>                          $cookieParams
     * @param array<string, mixed>                          $queryParams
     * @param array<array-key, UploadedFileInterface|array> $uploadedFiles
     * @param array<string, mixed>                          $attributes
     *
     * @throws InvalidArgumentException When request metadata or an uploaded-file tree is invalid
     */
    public function __construct(
        string                      $method,
        UriInterface|string         $uri,
        array                       $serverParams = [],
        array                       $headers = [],
        StreamInterface|string|null $body = null,
        string                      $protocolVersion = '1.1',
        array                       $cookieParams = [],
        array                       $queryParams = [],
        array                       $uploadedFiles = [],
        array|object|null           $parsedBody = null,
        array                       $attributes = [],
    )
    {
        parent::__construct($method, $uri, $headers, $body, $protocolVersion);

        UploadedFileTree::validate($uploadedFiles);

        foreach ($attributes as $name => $_) {
            if (!is_string($name)) {
                throw new InvalidArgumentException('HTTP request attribute names must be strings.');
            }
        }

        $this->serverParams = $serverParams;
        $this->cookieParams = $cookieParams;
        $this->queryParams = $queryParams;
        $this->uploadedFiles = $uploadedFiles;
        $this->parsedBody = $parsedBody;
        $this->attributes = $attributes;
    }

    /**
     * @return array<string, mixed>
     */
    public function getServerParams(): array
    {
        return $this->serverParams;
    }

    /**
     * @return array<string, mixed>
     */
    public function getCookieParams(): array
    {
        return $this->cookieParams;
    }

    public function withCookieParams(array $cookies): static
    {
        $clone = clone $this;
        $clone->cookieParams = $cookies;

        return $clone;
    }

    /**
     * @return array<string, mixed>
     */
    public function getQueryParams(): array
    {
        return $this->queryParams;
    }

    public function withQueryParams(array $query): static
    {
        $clone = clone $this;
        $clone->queryParams = $query;

        return $clone;
    }

    /**
     * @return array<array-key, UploadedFileInterface|array>
     */
    public function getUploadedFiles(): array
    {
        return $this->uploadedFiles;
    }

    public function withUploadedFiles(array $uploadedFiles): static
    {
        UploadedFileTree::validate($uploadedFiles);

        $clone = clone $this;
        $clone->uploadedFiles = $uploadedFiles;

        return $clone;
    }

    public function getParsedBody(): array|object|null
    {
        return $this->parsedBody;
    }

    public function withParsedBody(mixed $data): static
    {
        if (null !== $data && !is_array($data) && !is_object($data)) {
            throw new InvalidArgumentException(sprintf(
                'Parsed HTTP request bodies must be arrays, objects, or null; %s provided.',
                get_debug_type($data),
            ));
        }

        $clone = clone $this;
        $clone->parsedBody = $data;

        return $clone;
    }

    /**
     * @return array<string, mixed>
     */
    public function getAttributes(): array
    {
        return $this->attributes;
    }

    public function getAttribute(string $name, mixed $default = null): mixed
    {
        return array_key_exists($name, $this->attributes) ? $this->attributes[$name] : $default;
    }

    public function withAttribute(string $name, mixed $value): static
    {
        $clone = clone $this;
        $clone->attributes[$name] = $value;

        return $clone;
    }

    public function withoutAttribute(string $name): static
    {
        $clone = clone $this;
        unset($clone->attributes[$name]);

        return $clone;
    }
}
