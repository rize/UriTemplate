<?php

namespace Rize;

use Rize\UriTemplate\Node;
use Rize\UriTemplate\Parser;

/**
 * URI Template
 */
class UriTemplate
{
             /**
              * @var Rize\UriTemplate\Parser
              */
    protected $parser,
              $parsed = array(),
              $base_uri,
              $params = array();

    public function __construct($base_uri = '', $params = array(), Node\Parser $parser = null)
    {
        $this->base_uri = $base_uri;
        $this->params   = $params;
        $this->parser   = $parser ?: $this->createNodeParser();
    }

    /**
     * Expands URI Template
     *
     * @param string $uri_template  URI Template
     * @param array  $params        URI Template's parameters
     */
    public function expand($uri, $params = array())
    {
        $params += $this->params;
        $uri     = $this->base_uri.$uri;
        $result  = array();

        # quick check
        if (($start = strpos($uri, '{')) === false) {
            return $uri;
        }

        $parser = $this->parser;
        $nodes  = $parser->parse($uri);

        foreach($nodes as $node) {
            $result[] = $node->expand($parser, $params);
        }

        return implode('', $result);
    }

    /**
     * Extracts variables from URI
     *
     * @param  string $uri
     * @param  bool   $strict  This will perform a full match
     * @return array  params
     */
    public function extract($template, $uri, $strict = false)
    {
        $params = array();
        $nodes  = $this->parser->parse($template);

        foreach($nodes as $node) {

            # uri'll be truncated from the start when a match is found
            $match = $node->match($this->parser, $uri, $params, $strict);

            # if strict is given, just return null when there's no match
            if ($strict and !$match) {
                return;
            }

            list($uri, $params) = $match;
        }

        return $params;
    }

    public function getParser()
    {
        return $this->parser;
    }

    protected function createNodeParser()
    {
        static $parser;

        if ($parser) {
            return $parser;
        }

        return $parser = new Parser;
    }
}
