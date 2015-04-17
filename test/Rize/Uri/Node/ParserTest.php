<?php

use Rize\UriTemplate\Node;
use Rize\UriTemplate\Operator;
use Rize\UriTemplate\Parser;

class ParserTest extends PHPUnit_Framework_TestCase
{
    protected function service()
    {
        return new Parser;
    }

    public function testParseTemplate()
    {
        $input = 'http://www.example.com/{term:1}/{term}/{test*}/foo{?query,number}';
        $expected = array(
            new Node\Literal('http://www.example.com/'),
            new Node\Expression(
                'term:1',
                Operator\Abstraction::createById(''),
                array(
                    new Node\Variable(
                        'term:1',
                        array(
                            'modifier' => ':',
                            'value'    => 1,
                        )
                    ),
                )
            ),
            new Node\Literal('/'),
            new Node\Expression(
                'term',
                Operator\Abstraction::createById(''),
                array(
                    new Node\Variable(
                        'term',
                        array(
                            'modifier' => null,
                            'value'    => null,
                        )
                    ),
                )
            ),
            new Node\Literal('/'),
            new Node\Expression(
                'test*',
                Operator\Abstraction::createById(''),
                array(
                    new Node\Variable(
                        'test',
                        array(
                            'modifier' => '*',
                            'value'    => null,
                        )
                    ),
                )
            ),
            new Node\Literal('/foo'),
            new Node\Expression(
                'query,number',
                Operator\Abstraction::createById('?'),
                array(
                    new Node\Variable(
                        'query',
                        array(
                            'modifier' => null,
                            'value'    => null,
                        )
                    ),
                    new Node\Variable(
                        'number',
                        array(
                            'modifier' => null,
                            'value'    => null,
                        )
                    ),
                )
            ),
        );

        $service = $this->service();
        $actual  = $service->parse($input);

        $this->assertEquals($expected, $actual);
    }
}