<?php

namespace Rize;

use Rize\UriTemplate\Node;
use Rize\UriTemplate\Parser;

/**
 * URI Template
 */
class UriTemplate
{
    protected $parsed = array();

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
     * @return array  params
     */
    public function extract($template, $uri = null)
    {
        $params = array();
        $nodes  = $this->parser->parse($template);

        # Steps:
        # 1. Convert each node to regex
        # 2. Match each regex against given uri
        # 3. If matched uri is found, remove the matched uri from uri string,
        # and continue matching the rest of uri

        $regex = array();

        foreach($nodes as $node) {
            $regex[] = $node->toRegex($this->parser);

            if ($node instanceof Node\Expression) {

                $reg = '#'.implode('', $regex).'#';

                if (!preg_match($reg, $uri, $matches)) {
                    continue;
                }

                $params += $node->extract($this->parser, array_slice($matches, 1));

                # reset regex and remove matched part from uri
                $uri     = substr($uri, strlen($matches[0]));
                $regex   = array();
            }
        }

        return $params;
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