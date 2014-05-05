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

    public function extract(Parser $parser, array $vars = array())
    {
        $params   = array();
        $operator = $this->operator;

        foreach($this->variables as $i => $var) {
            $val  = isset($vars[$i]) ? $vars[$i] : null;
            $data = $operator->extract($parser, $var, $val);

            $params[$var->token] = $data === '' ? null : $data;
        }

        return $params;
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

        foreach($this->variables as $var) {
            $regex = '#'.$op->toRegex($parser, $var).'#';
            $val   = null;

            # var_dump($regex, $uri); echo "\n";
            if (preg_match($regex, $uri, $match)) {

                # remove matched part from input
                $uri = preg_replace($regex, '', $uri, $limit = 1);
                $val = $op->extract($parser, $var, $match[0]);
            }

            $params[$var->token] = $val;
        }

        return array($uri, $params);
    }
}