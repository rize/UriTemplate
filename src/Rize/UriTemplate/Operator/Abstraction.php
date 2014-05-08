<?php

namespace Rize\UriTemplate\Operator;

use Rize\UriTemplate\Node;
use Rize\UriTemplate\Parser;

/**
 * .------------------------------------------------------------------.
 * |          NUL     +      .       /       ;      ?      &      #   |
 * |------------------------------------------------------------------|
 * | first |  ""     ""     "."     "/"     ";"    "?"    "&"    "#"  |
 * | sep   |  ","    ","    "."     "/"     ";"    "&"    "&"    ","  |
 * | named | false  false  false   false   true   true   true   false |
 * | ifemp |  ""     ""     ""      ""      ""     "="    "="    ""   |
 * | allow |   U     U+R     U       U       U      U      U     U+R  |
 * `------------------------------------------------------------------'
 *
 * named = false
 * | 1   |    {/list}    /red,green,blue                  | {$value}*(?:,{$value}+)*
 * | 2   |    {/list*}   /red/green/blue                  | {$value}+(?:{$sep}{$value}+)*
 * | 3   |    {/keys}    /semi,%3B,dot,.,comma,%2C        | /(\w+,?)+
 * | 4   |    {/keys*}   /semi=%3B/dot=./comma=%2C        | /(?:\w+=\w+/?)*
 * named = true
 * | 1   |    {?list}    ?list=red,green,blue             | {name}=(?:\w+(?:,\w+?)*)*
 * | 2   |    {?list*}   ?list=red&list=green&list=blue   | {name}+=(?:{$value}+(?:{sep}{name}+={$value}*))*
 * | 3   |    {?keys}    ?keys=semi,%3B,dot,.,comma,%2C   | (same as 1)
 * | 4   |    {?keys*}   ?semi=%3B&dot=.&comma=%2C        | (same as 2)
 */
abstract class Abstraction
{
    /**
     * start - Variable offset position, level-2 operators start at 1
     *         (exclude operator itself, e.g. {?query})
     * first - If variables found, prepend this value to it
     * named - Whether or not the expansion includes the variable or key name
     * reserved - union of (unreserved / reserved / pct-encoded)
     */
    public $id,
           $named,
           $sep,
           $empty,
           $reserved,
           $start,
           $first;

    protected static $types = array(
                  '' => array(
                     'sep'   => ',',
                     'named' => false,
                     'empty' => '',
                     'reserved' => false,
                     'start' => 0,
                     'first' => null,
                  ),
                  '+' => array(
                     'sep'   => ',',
                     'named' => false,
                     'empty' => '',
                     'reserved' => true,
                     'start' => 1,
                     'first' => null,
                  ),
                  '.' => array(
                     'sep'   => '.',
                     'named' => false,
                     'empty' => '',
                     'reserved' => false,
                     'start' => 1,
                     'first' => '.',
                  ),
                  '/' => array(
                     'sep'   => '/',
                     'named' => false,
                     'empty' => '',
                     'reserved' => false,
                     'start' => 1,
                     'first' => '/',
                  ),
                  ';' => array(
                     'sep'   => ';',
                     'named' => true,
                     'empty' => '',
                     'reserved' => false,
                     'start' => 1,
                     'first' => ';',
                  ),
                  '?' => array(
                     'sep'   => '&',
                     'named' => true,
                     'empty' => '=',
                     'reserved' => false,
                     'start' => 1,
                     'first' => '?',
                  ),
                  '&' => array(
                     'sep'   => '&',
                     'named' => true,
                     'empty' => '=',
                     'reserved' => false,
                     'start' => 1,
                     'first' => '&',
                  ),
                  '#' => array(
                     'sep'   => ',',
                     'named' => false,
                     'empty' => '',
                     'reserved' => true,
                     'start' => 1,
                     'first' => '#',
                  ),
              ),
              $loaded = array();

    public function __construct($id, $named, $sep, $empty, $reserved, $start, $first)
    {
        $this->id    = $id;
        $this->named = $named;
        $this->sep   = $sep;
        $this->empty = $empty;
        $this->start = $start;
        $this->first = $first;
        $this->reserved = $reserved;
    }

    public function expand(Parser $parser, Node\Variable $var, array $params = array())
    {
        $options    = $var->options;
        $name       = $var->name;
        $is_prefix  = $options['modifier'] === ':';
        $is_explode = $options['modifier'] === '*';

        # skip null
        if (!isset($params[$name])) {
            return;
        }

        $val  = $params[$name];

        # This algorithm is based on RFC6570 http://tools.ietf.org/html/rfc6570
        # non-array, e.g. string
        if (!is_array($val)) {
            return $this->expandString($parser, $var, $val);
        }

        # non-explode
        else if (!$is_explode) {
            return $this->expandNonExplode($parser, $var, $val);
        }

        # explode
        else {
            return $this->expandExplode($parser, $var, $val);
        }
    }

    public function expandString(Parser $parser, Node\Variable $var, $val)
    {
        $val     = (string)$val;
        $options = $var->options;
        $result  = null;

        if ($options['modifier'] === ':') {
            $val = substr($val, 0, (int)$options['value']);
        }

        return $result.$this->encode($parser, $var, $val);
    }

    public function expandNonExplode(Parser $parser, Node\Variable $var, array $val)
    {
        if (empty($val)) {
            return;
        }

        return $this->encode($parser, $var, $val);
    }

    public function expandExplode(Parser $parser, Node\Variable $var, array $val)
    {
        if (empty($val)) {
            return;
        }

        return $this->encode($parser, $var, $val);
    }

    public function encode(Parser $parser, Node\Variable $var, $values)
    {
        $values    = (array)$values;
        $list      = isset($values[0]);
        $reserved  = $this->reserved;
        $sep       = $this->sep;
        $assoc_sep = '=';

        # non-explode modifier always use ',' as a separator
        if ($var->options['modifier'] !== '*') {
            $assoc_sep = $sep = ',';
        }

        return implode($sep, array_map(function($v, $k) use ($assoc_sep, $reserved, $list) {

            $encoded = rawurlencode($v);

            # assoc? encode key too
            if (!$list) {
                $encoded = rawurlencode($k).$assoc_sep.$encoded;
            }

            # rawurlencode is compliant with 'unreserved' set
            if (!$reserved) {
                return $encoded;
            }

            # decode chars in reserved set
            else {
                $maps = Parser::$reserved;

                return str_replace(
                    array_keys($maps),
                    $maps,
                    $encoded
                );
            }

        }, $values, array_keys($values)));
    }

    public function decode(Parser $parser, Node\Variable $var, $values)
    {

    }
    
    public static function createById($id)
    {
        if (!isset(static::$types[$id])) {
            throw new \Exception("Invalid operator [$id]");
        }

        if (isset(static::$loaded[$id])) {
            return static::$loaded[$id];
        }

        $op    = static::$types[$id];
        $class = __NAMESPACE__.'\\'.($op['named'] ? 'Named' : 'UnNamed');

        return static::$loaded[$id] = new $class($id, $op['named'], $op['sep'], $op['empty'], $op['reserved'], $op['start'], $op['first']);
    }

    public static function isValid($id)
    {
        return isset(static::$types[$id]);
    }
}