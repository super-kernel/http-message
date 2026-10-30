<?php

declare(strict_types=1);

namespace SuperKernel\HttpMessage\StartLine;

use InvalidArgumentException;

use function preg_match;
use function sprintf;

/**
 * Validates response status codes and reason phrases and supplies the standard phrase for an omitted one.
 *
 * @internal
 */
final class ResponseStatus
{
    /** @var array<int, string> */
    private const array REASON_PHRASES = [
        100 => 'Continue',
        101 => 'Switching Protocols',
        102 => 'Processing',
        103 => 'Early Hints',
        200 => 'OK',
        201 => 'Created',
        202 => 'Accepted',
        203 => 'Non-Authoritative Information',
        204 => 'No Content',
        205 => 'Reset Content',
        206 => 'Partial Content',
        207 => 'Multi-Status',
        208 => 'Already Reported',
        226 => 'IM Used',
        300 => 'Multiple Choices',
        301 => 'Moved Permanently',
        302 => 'Found',
        303 => 'See Other',
        304 => 'Not Modified',
        305 => 'Use Proxy',
        307 => 'Temporary Redirect',
        308 => 'Permanent Redirect',
        400 => 'Bad Request',
        401 => 'Unauthorized',
        402 => 'Payment Required',
        403 => 'Forbidden',
        404 => 'Not Found',
        405 => 'Method Not Allowed',
        406 => 'Not Acceptable',
        407 => 'Proxy Authentication Required',
        408 => 'Request Timeout',
        409 => 'Conflict',
        410 => 'Gone',
        411 => 'Length Required',
        412 => 'Precondition Failed',
        413 => 'Content Too Large',
        414 => 'URI Too Long',
        415 => 'Unsupported Media Type',
        416 => 'Range Not Satisfiable',
        417 => 'Expectation Failed',
        418 => "I'm a teapot",
        421 => 'Misdirected Request',
        422 => 'Unprocessable Content',
        423 => 'Locked',
        424 => 'Failed Dependency',
        425 => 'Too Early',
        426 => 'Upgrade Required',
        428 => 'Precondition Required',
        429 => 'Too Many Requests',
        431 => 'Request Header Fields Too Large',
        451 => 'Unavailable For Legal Reasons',
        500 => 'Internal Server Error',
        501 => 'Not Implemented',
        502 => 'Bad Gateway',
        503 => 'Service Unavailable',
        504 => 'Gateway Timeout',
        505 => 'HTTP Version Not Supported',
        506 => 'Variant Also Negotiates',
        507 => 'Insufficient Storage',
        508 => 'Loop Detected',
        510 => 'Not Extended',
        511 => 'Network Authentication Required',
    ];

    /**
     * @return array{int, string} The status code and the given phrase, or the standard phrase when it is empty
     *
     * @throws InvalidArgumentException When the code is outside 100..599 or the phrase contains a prohibited control
     *                                  character
     */
    public static function normalize(int $code, string $reasonPhrase): array
    {
        if (100 > $code || 599 < $code) {
            throw new InvalidArgumentException(sprintf('Invalid HTTP status code %d.', $code));
        }

        if (1 === preg_match('/[\x00-\x08\x0A-\x1F\x7F]/', $reasonPhrase)) {
            throw new InvalidArgumentException('HTTP reason phrases cannot contain prohibited control characters.');
        }

        return [
            $code,
            '' === $reasonPhrase ? self::REASON_PHRASES[$code] ?? '' : $reasonPhrase,
        ];
    }
}
