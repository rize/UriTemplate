<?php

namespace Rize\UriTemplate\Node;

use Rize\UriTemplate\Parser;

/**
 * Base class for all Nodes
 */
abstract class Abstraction
{
    public function __construct(private string $token) {}

    /**
     * @param array<string, mixed> $params
     * Expands URI template
     */
    public function expand(Parser $parser, array $params = []): ?string
    {
        return $this->token;
    }

    /**
     * Matches given URI against current node
     *
     * @param array<string, mixed> $params
     * @return null|array{0: string, 1: array<string, mixed>} `uri and params` or `null` if not match and $strict is true
     */
    public function match(Parser $parser, string $uri, array $params = [], bool $strict = false): ?array
    {
        // match literal string from start to end
        $length = strlen($this->token);
        if (strpos($uri, $this->token) === 0) {
            $uri = substr($uri, $length);
        }

        // when there's no match, just return null if strict mode is given
        elseif ($strict) {
            return null;
        }

        return [$uri, $params];
    }

    public function getToken(): string
    {
        return $this->token;
    }
}
