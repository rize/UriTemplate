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
 * `------------------------------------------------------------------'.
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
 *
 * UNRESERVED
 * ----------
 * RFC 1738 ALPHA | DIGIT | "-" | "." | "_" |     | "$" | "+" | "!" | "*" | "'" | "(" | ")" | ","
 * RFC 3986 ALPHA | DIGIT | "-" | "." | "_" | "~"
 * RFC 6570 ALPHA | DIGIT | "-" | "." | "_" | "~"
 *
 * RESERVED
 * --------
 * RFC 1738 ":" | "/" | "?" |                 | "@" | "!" | "$" | "&" | "'" | "(" | ")" | "*" | "+" | "," | ";" | "=" | "-" | "_" | "." |
 * RFC 3986 ":" | "/" | "?" | "#" | "[" | "]" | "@" | "!" | "$" | "&" | "'" | "(" | ")" | "*" | "+" | "," | ";" | "="
 * RFC 6570 ":" | "/" | "?" | "#" | "[" | "]" | "@" | "!" | "$" | "&" | "'" | "(" | ")" | "*" | "+" | "," | ";" | "="
 *
 * PHP_QUERY_RFC3986 was added in PHP 5.4.0
 */
abstract class Abstraction
{
    /**
     * start - Variable offset position, level-2 operators start at 1
     *         (exclude operator itself, e.g. {?query})
     * first - If variables found, prepend this value to it
     * named - Whether the expansion includes the variable or key name
     * reserved - union of (unreserved / reserved / pct-encoded).
     */
    public $id;
    public $named;
    public $sep;
    public $empty;
    public $reserved;
    public $start;
    public $first;

    /**
     * gen-delims | sub-delims.
     */
    public static $reserved_chars = [
        '%3A' => ':',
        '%2F' => '/',
        '%3F' => '?',
        '%23' => '#',
        '%5B' => '[',
        '%5D' => ']',
        '%40' => '@',
        '%21' => '!',
        '%24' => '$',
        '%26' => '&',
        '%27' => "'",
        '%28' => '(',
        '%29' => ')',
        '%2A' => '*',
        '%2B' => '+',
        '%2C' => ',',
        '%3B' => ';',
        '%3D' => '=',
    ];

    protected static $types = [
        '' => [
            'sep' => ',',
            'named' => false,
            'empty' => '',
            'reserved' => false,
            'start' => 0,
            'first' => null,
        ],
        '+' => [
            'sep' => ',',
            'named' => false,
            'empty' => '',
            'reserved' => true,
            'start' => 1,
            'first' => null,
        ],
        '.' => [
            'sep' => '.',
            'named' => false,
            'empty' => '',
            'reserved' => false,
            'start' => 1,
            'first' => '.',
        ],
        '/' => [
            'sep' => '/',
            'named' => false,
            'empty' => '',
            'reserved' => false,
            'start' => 1,
            'first' => '/',
        ],
        ';' => [
            'sep' => ';',
            'named' => true,
            'empty' => '',
            'reserved' => false,
            'start' => 1,
            'first' => ';',
        ],
        '?' => [
            'sep' => '&',
            'named' => true,
            'empty' => '=',
            'reserved' => false,
            'start' => 1,
            'first' => '?',
        ],
        '&' => [
            'sep' => '&',
            'named' => true,
            'empty' => '=',
            'reserved' => false,
            'start' => 1,
            'first' => '&',
        ],
        '#' => [
            'sep' => ',',
            'named' => false,
            'empty' => '',
            'reserved' => true,
            'start' => 1,
            'first' => '#',
        ],
    ];

    protected static $loaded = [];

    /**
     * RFC 3986 Allowed path characters regex except the path delimiter '/'.
     *
     * @var string
     */
    protected static $pathRegex = '(?:[a-zA-Z0-9\-\._~!\$&\'\(\)\*\+,;=%:@]+|%(?![A-Fa-f0-9]{2}))';

    /**
     * RFC 3986 Allowed query characters regex except the query parameter delimiter '&'.
     *
     * @var string
     */
    protected static $queryRegex = '(?:[a-zA-Z0-9\-\._~!\$\'\(\)\*\+,;=%:@\/\?]+|%(?![A-Fa-f0-9]{2}))';

    public function __construct($id, $named, $sep, $empty, $reserved, $start, $first)
    {
        $this->id = $id;
        $this->named = $named;
        $this->sep = $sep;
        $this->empty = $empty;
        $this->start = $start;
        $this->first = $first;
        $this->reserved = $reserved;
    }

    abstract public function toRegex(Parser $parser, Node\Variable $var): string;

    public function expand(Parser $parser, Node\Variable $var, array $params = [])
    {
        $options    = $var->options;
        $name       = $var->name;
        $is_explode = in_array($options['modifier'], ['*', '%']);

        // skip null
        if (!isset($params[$name])) {
            return null;
        }

        $val = $params[$name];

        // This algorithm is based on RFC6570 http://tools.ietf.org/html/rfc6570
        // non-array, e.g. string
        if (!is_array($val)) {
            return $this->expandString($parser, $var, $val);
        }

        // non-explode ':'
        if (!$is_explode) {
            return $this->expandNonExplode($parser, $var, $val);
        }

        // explode '*', '%'

        return $this->expandExplode($parser, $var, $val);
    }

    public function expandString(Parser $parser, Node\Variable $var, $val)
    {
        $val     = (string) $val;
        $options = $var->options;
        $result  = null;

        if ($options['modifier'] === ':') {
            $val = substr($val, 0, (int) $options['value']);
        }

        return $result . $this->encode($parser, $var, $val);
    }

    /**
     * Non explode modifier ':'.
     */
    public function expandNonExplode(Parser $parser, Node\Variable $var, array $val): ?string
    {
        if (empty($val)) {
            return null;
        }

        return $this->encode($parser, $var, $val);
    }

    /**
     * Explode modifier '*', '%'.
     */
    public function expandExplode(Parser $parser, Node\Variable $var, array $val): ?string
    {
        if (empty($val)) {
            return null;
        }

        return $this->encode($parser, $var, $val);
    }

    /**
     * Encodes variable according to spec (reserved or unreserved).
     *
     * @return string encoded string
     */
    public function encode(Parser $parser, Node\Variable $var, mixed $values)
    {
        $values    = (array) $values;
        $list      = isset($values[0]);
        $reserved  = $this->reserved;
        $maps      = static::$reserved_chars;
        $sep       = $this->sep;
        $assoc_sep = '=';

        // non-explode modifier always use ',' as a separator
        if ($var->options['modifier'] !== '*') {
            $assoc_sep = $sep = ',';
        }

        array_walk($values, function (&$v, $k) use ($assoc_sep, $reserved, $list, $maps): void {
            $encoded = rawurlencode($v);

            // assoc? encode key too
            if (!$list) {
                $encoded = rawurlencode($k) . $assoc_sep . $encoded;
            }

            // rawurlencode is compliant with 'unreserved' set
            if (!$reserved) {
                $v = $encoded;
            }

            // decode chars in reserved set
            else {
                $v = str_replace(
                    array_keys($maps),
                    $maps,
                    $encoded,
                );
            }
        });

        return implode($sep, $values);
    }

    /**
     * Decodes variable.
     *
     * @return string decoded string
     */
    public function decode(Parser $parser, Node\Variable $var, mixed $values)
    {
        $single = !is_array($values);
        $values = (array) $values;

        array_walk($values, function (&$v, $k): void {
            $v = rawurldecode($v);
        });

        return $single ? reset($values) : $values;
    }

    /**
     * Extracts value from variable.
     */
    public function extract(Parser $parser, Node\Variable $var, string $data): array|string
    {
        $value = $data;
        $vals = array_filter(explode($this->sep, $data));
        $options = $var->options;

        switch ($options['modifier']) {
            case '*':
                $value = [];
                foreach ($vals as $val) {
                    if (str_contains($val, '=')) {
                        [$k, $v] = explode('=', $val);
                        $value[$k] = $v;
                    } else {
                        $value[] = $val;
                    }
                }

                break;

            case ':':
                break;

            default:
                $value = str_contains($value, (string) $this->sep) ? $vals : $value;
        }

        return $this->decode($parser, $var, $value);
    }

    public static function createById($id)
    {
        if (!isset(static::$types[$id])) {
            throw new \InvalidArgumentException("Invalid operator [{$id}]");
        }

        if (isset(static::$loaded[$id])) {
            return static::$loaded[$id];
        }

        $op    = static::$types[$id];
        $class = __NAMESPACE__ . '\\' . ($op['named'] ? 'Named' : 'UnNamed');

        return static::$loaded[$id] = new $class($id, $op['named'], $op['sep'], $op['empty'], $op['reserved'], $op['start'], $op['first']);
    }

    public static function isValid($id): bool
    {
        return isset(static::$types[$id]);
    }

    /**
     * Returns the correct regex given the variable location in the URI.
     */
    protected function getRegex(): string
    {
        return match ($this->id) {
            '?', '&', '#' => self::$queryRegex,
            default => self::$pathRegex,
        };
    }
}
