<?php 

namespace Rize\UriTemplate\Node;

use Rize\UriTemplate\Parser;
use Rize\UriTemplate\Operator;

/**
 * Description
 */
class Expression extends Abstraction
{
    public $token,
           $operator,
           $variables = array();

    public function __construct($token, Operator\Abstraction $operator, array $variables = null)
    {
        $this->token     = $token;
        $this->operator  = $operator;
        $this->variables = $variables;
    }

    public function expand(Parser $parser, array $params = array())
    {
        $data = array();
        $op   = $this->operator;

        # check for variable modifiers
        foreach($this->variables as $var) {

            $val = $op->expand($parser, $var, $params);

            # skip null value
            if (!is_null($val)) {
                $data[] = $val;
            }
        }

        return $data ? $op->first.implode($op->sep, $data) : null;
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
        $op = $this->operator;

        # check expression operator first
        if ($op->id and $uri[0] !== $op->id) {
          return array($uri, $params);
        }

        # remove operator from input
        if ($op->id) {
            $uri = substr($uri, 1);
        }

        foreach($this->sortVariables($this->variables) as $var) {
            $regex = '#'.$op->toRegex($parser, $var).'#';
            $val   = null;

            if (preg_match($regex, $uri, $match)) {

                # remove matched part from input
                $uri = preg_replace($regex, '', $uri, $limit = 1);
                $val = $op->extract($parser, $var, $match[0]);
            }

            $params[$var->token] = $val;
        }

        return array($uri, $params);
    }

    /**
     * Sort variables before extracting data from uri.
     * We have to sort vars by non-explode to explode.
     *
     * @params array $vars
     */
    protected function sortVariables(array $vars)
    {
        usort($vars, function($a, $b) {
            return $a->options['modifier'] >= $b->options['modifier'] ? 1 : -1;
        });

        return $vars;
    }
}