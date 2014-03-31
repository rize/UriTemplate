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

    public function toRegex(Parser $parser)
    {
        $regex = array();
        $op    = $this->operator;

        foreach($this->variables as $var) {
            $regex[] = '('.$op->toRegex($parser, $var).')';
        }

        /**
         * Structure of regex
         * Note that we only capture vars, not the expression itself
         *
         * (?:
         *   {operator}(var){sep}(var)
         * )?
         */
        return '(?:'.preg_quote($op->id, '#').implode(preg_quote($op->sep, '#'), $regex).')?';
    }
}