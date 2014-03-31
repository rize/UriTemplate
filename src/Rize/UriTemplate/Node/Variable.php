<?php 

namespace Rize\UriTemplate\Node;

use Rize\UriTemplate\Parser;

/**
 * Description
 */
class Variable extends Abstraction
{
           /**
            * Raw variable name e.g. 'term:1'
            */
    public $token,

           /**
            * Variable name without modifier 
            * e.g. 'term:1' becomes 'term'
            */
           $name,
           $options = array(
              'modifier' => null,
              'value'    => null,
           );

    public function __construct($token, array $options = array())
    {
        $this->token   = $token;
        $this->options = $options + $this->options;

        # normalize var name e.g. from 'term:1' becomes 'term'
        $name = $token;
        if ($options['modifier'] === ':') {
            $name = substr($name, 0, strpos($name, $options['modifier']));
        }

        $this->name = $name;
    }
}