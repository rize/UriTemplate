<?php

namespace Rize\UriTemplate\Node;

use Rize\UriTemplate\Parser;

class Literal extends Abstraction
{
    /**
     * Non-ASCII literal text must be pct-encoded on expansion (RFC 6570 #3.1).
     */
    public function expand(Parser $parser, array $params = []): ?string
    {
        return self::encodeNonAscii($this->getToken());
    }

    public function match(Parser $parser, string $uri, array $params = [], bool $strict = false): ?array
    {
        // also accept the encoded form that `expand` produces
        $encoded = self::encodeNonAscii($this->getToken());

        if ($encoded !== $this->getToken() && str_starts_with($uri, $encoded)) {
            return [substr($uri, strlen($encoded)), $params];
        }

        return parent::match($parser, $uri, $params, $strict);
    }

    private static function encodeNonAscii(string $value): string
    {
        return preg_replace_callback('#[\x80-\xFF]+#', static fn($m) => rawurlencode($m[0]), $value);
    }
}
