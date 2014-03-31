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
     * Converts current node to regular expression
     */
    public function toRegex(Parser $parser)
    {
        return preg_quote($this->token, '#');
    }
}