<?php

namespace Rize\UriTemplate\Node;

use Rize\UriTemplate\Parser;
use Rize\UriTemplate\Operator;

/**
 * Description
 */
class Expression extends Abstraction
{
    /**
     * @param string $forwardLookupSeparator
     */
    public function __construct($token, private readonly Operator\Abstraction $operator, private readonly ?array $variables = null, /**
     * Whether to do a forward lookup for a given separator
     */
        private $forwardLookupSeparator = null)
    {
        parent::__construct($token);
    }

    /**
     * @return Operator\Abstraction
     */
    public function getOperator()
    {
        return $this->operator;
    }

    /**
     * @return array
     */
    public function getVariables()
    {
        return $this->variables;
    }

    /**
     * @return string
     */
    public function getForwardLookupSeparator()
    {
        return $this->forwardLookupSeparator;
    }

    /**
     * @param string $forwardLookupSeparator
     */
    public function setForwardLookupSeparator($forwardLookupSeparator)
    {
        $this->forwardLookupSeparator = $forwardLookupSeparator;
    }

    /**
     * @param Parser $parser
     * @param array $params
     * @return null|string
     */
    public function expand(Parser $parser, array $params = []): ?string
    {
        $data = [];
        $op   = $this->operator;

        // check for variable modifiers
        foreach($this->variables as $var) {

            $val = $op->expand($parser, $var, $params);

            // skip null value
            if (!is_null($val)) {
                $data[] = $val;
            }
        }

        return $data ? $op->first . implode($op->sep, $data) : null;
    }

    /**
     * Matches given URI against current node
     *
     * @return null|array `uri and params` or `null` if not match and $strict is true
     */
    public function match(Parser $parser, string $uri, array $params = [], bool $strict = false): ?array
    {
        $op = $this->operator;

        // check expression operator first
        if ($op->id && isset($uri[0]) && $uri[0] !== $op->id) {
            return [$uri, $params];
        }

        // remove operator from input
        if ($op->id) {
            $uri = substr($uri, 1);
        }

        foreach($this->sortVariables($this->variables) as $var) {
            $regex = '#' . $op->toRegex($parser, $var) . '#';
            $val   = null;

            // do a forward lookup and get just the relevant part
            $remainingUri = '';
            $preparedUri = $uri;
            if ($this->forwardLookupSeparator) {
                $lastOccurrenceOfSeparator = stripos($uri, $this->forwardLookupSeparator);
                $preparedUri = substr($uri, 0, $lastOccurrenceOfSeparator);
                $remainingUri = substr($uri, $lastOccurrenceOfSeparator);
            }

            if (preg_match($regex, $preparedUri, $match)) {

                // remove matched part from input
                $preparedUri = preg_replace($regex, '', $preparedUri, 1);
                $val = $op->extract($parser, $var, $match[0]);
            }

            // if strict is given, we quit immediately when there's no match
            elseif ($strict) {
                return null;
            }

            $uri = $preparedUri . $remainingUri;

            $params[$var->getToken()] = $val;
        }

        return [$uri, $params];
    }

    /**
     * Sort variables before extracting data from uri.
     * We have to sort vars by non-explode to explode.
     *
     * @param array $vars
     * @return array
     */
    protected function sortVariables(array $vars)
    {
        usort($vars, fn($a, $b) => $a->options['modifier'] >= $b->options['modifier'] ? 1 : -1);

        return $vars;
    }
}
