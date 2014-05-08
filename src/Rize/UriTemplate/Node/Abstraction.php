<?php 

namespace Rize\UriTemplate\Node;

use Rize\UriTemplate\Parser;

/**
 * Base class for all Nodes
 */
abstract class Abstraction
{
    public $token;
    public function __construct($token)
    {
        $this->token = $token;
    }

    /**
     * Expands URI template
     *
     * @param Parser $parser
     * @param array  $params
     */
    public function expand(Parser $parser, array $params = array())
    {
        return $this->token;
    }

    /**
     * Matches given URI against current node
     *
     * @param Parser $parser
     * @param string $uri
     * @param array  $params
     */
    public function match(Parser $parser, $uri, $params = array())
    {
        # match literal string from start to end
        $length = strlen($this->token);
        if (($tmp = substr($uri, 0, $length)) === $this->token) {
            $uri = substr($uri, $length);
        }

        return array($uri, $params);
    }
}