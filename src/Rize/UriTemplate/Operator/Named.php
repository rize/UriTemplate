<?php

namespace Rize\UriTemplate\Operator;

use Rize\UriTemplate\Node;
use Rize\UriTemplate\Parser;

/**
 * | 1   |    {?list}    ?list=red,green,blue             | {name}=(?:\w+(?:,\w+?)*)*
 * | 2   |    {?list*}   ?list=red&list=green&list=blue   | {name}+=(?:{$value}+(?:{sep}{name}+={$value}*))*
 * | 3   |    {?keys}    ?keys=semi,%3B,dot,.,comma,%2C   | (same as 1)
 * | 4   |    {?keys*}   ?semi=%3B&dot=.&comma=%2C        | (same as 2)
 */
class Named extends Abstraction
{
    public function toRegex(Parser $parser, Node\Variable $var)
    {
        $regex   = null;
        $name    = $var->name;
        $value   = '(?:[\w\.\-]|%[\da-fA-F]{2})';
        $options = $var->options;

        if ($options['modifier']) {
            switch($options['modifier']) {
                case '*':
                    # 2 | 4
                    $regex = "{$value}+=(?:{$value}+(?:{$this->sep}{$value}+={$value}*)*)";
                    break;
                case ':':
                    $regex = "{$value}\{0,{$options['value']}\}";
                    break;
                default:
                    throw new \Exception("Unknown modifier `{$options['modifier']}`");
            }
        }

        else {
            # 1, 3
            $regex = "{$name}=(?:{$value}+(?:,{$value}+)*)*";
        }

        return $regex;
    }

    public function expandString(Parser $parser, Node\Variable $var, $val)
    {
        $val     = (string)$val;
        $options = $var->options;
        $result  = $this->encode($parser, $var, $var->name);

        # handle empty value
        if ($val === '') {
            return $result . $this->empty;
        }

        else {
            $result .= '=';
        }

        if ($options['modifier'] === ':') {
            $val = mb_substr($val, 0, (int)$options['value']);
        }

        return $result.$this->encode($parser, $var, $val);
    }

    public function expandNonExplode(Parser $parser, Node\Variable $var, array $val)
    {
        $result  = $this->encode($parser, $var, $var->name);

        if (empty($val)) {
            return $result . $this->empty;
        }

        else {
            $result .= '=';
        }

        return $result.$this->encode($parser, $var, $val);
    }

    public function expandExplode(Parser $parser, Node\Variable $var, array $val)
    {
        $result  = $this->encode($parser, $var, $var->name);

        # RFC6570 doesn't specify how to handle empty list/assoc array
        # for explode modifier
        if (empty($val)) {
            return $result . $this->empty;
        }

        $list = isset($val[0]);
        $tmp  = array();

        foreach($val as $k => $v) {

            # if value it's a list use `varname` as keyname, otherwise use `key` name
            $name  = $this->encode($parser, $var, $list ? $var->name : $k);
            $v     = $this->encode($parser, $var, $v);
            $tmp[] = "{$name}={$v}";
        }

        if (!$tmp) {
            return;
        }

        return implode($this->sep, $tmp);
    }

    public function extract(Parser $parser, Node\Variable $var, $data)
    {
        $value   = $data;
        $vals    = explode($this->sep, $data);
        $options = $var->options;

        switch ($options['modifier']) {
            case '*':
                $data = array();
                foreach($vals as $val) {
                    list($k, $v) = explode('=', $val);

                    # 2
                    if ($k === $var->token) {
                        $data[] = $v;
                    }

                    # 4
                    else {
                        $data[$k] = $v;
                    }
                }

                break;
            case ':':
                break;
            default:
                # 1, 3
                # remove key from value e.g. 'lang=en,th' becomes 'en,th'
                $value = str_replace($var->token.'=', '', $value);
                $data  = explode(',', $value);

                if (sizeof($data) === 1) {
                    $data = current($data);
                }
        }

        return $data;
    }
}