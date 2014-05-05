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
     */
    public function expand(Parser $parser, array $params = array())
    {
        return $this->token;
    }

    /**
     * Extracts variables from URI template
     */
    public function extract(Parser $parser, array $params = array())
    {
        return array();
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