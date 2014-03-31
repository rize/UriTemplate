<?php

namespace Rize\UriTemplate\Operator;

use Rize\UriTemplate\Node;
use Rize\UriTemplate\Parser;

/**
 * | 1   |    {/list}    /red,green,blue                  | {$value}*(?:,{$value}+)*
 * | 2   |    {/list*}   /red/green/blue                  | {$value}+(?:{$sep}{$value}+)*
 * | 3   |    {/keys}    /semi,%3B,dot,.,comma,%2C        | /(\w+,?)+
 * | 4   |    {/keys*}   /semi=%3B/dot=./comma=%2C        | /(?:\w+=\w+/?)*
 */
class UnNamed extends Abstraction
{
    public function toRegex(Parser $parser, Node\Variable $var)
    {
        $regex   = null;
        $value   = '(?:[\w\.\-]|%[\da-fA-F]{2})';
        $options = $var->options;

        if ($options['modifier']) {
            switch($options['modifier']) {
                case '*':
                    # 2 | 4
                    $regex = "{$value}+(?:{$this->sep}{$value}+)*";
                    break;
                case ':':
                    $regex = $value.'{0,'.$options['value'].'}';
                    break;
                default:
                    throw new \Exception("Unknown modifier `{$options['modifier']}`");
            }
        }

        else {
            # 1, 3
            $regex = "{$value}*(?:,{$value}+)*";
        }

        return $regex;
    }

    public function extract(Parser $parser, Node\Variable $var, $data)
    {
        $value   = $data;
        $vals    = array_filter(explode($this->sep, $data));
        $options = $var->options;

        switch ($options['modifier']) {

            case '*':
                $data = array();
                foreach($vals as $val) {
                    
                    if (strpos($val, '=') !== false) {
                        list($k, $v) = explode('=', $val);
                        $data[$k] = $v;
                    }

                    else {
                        $data[] = $val;
                    }
                }

                break;
            case ':':
                break;
            default:
                $data = strpos($data, $this->sep) !== false ? $vals : urldecode($value);
        }

        return $data;
    }
}