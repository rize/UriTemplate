<?php

use Rize\UriTemplate;

class ParserTest extends PHPUnit_Framework_TestCase
{
    public function testParseTemplateWithLiteral()
    {
        // will pass
        $uri = new UriTemplate('http://www.example.com/v1/company/', []);
        $params = $uri->extract('/{countryCode}/{registrationNumber}/test{.format}', '/gb/0123456/test.json');
        static::assertEquals(array('countryCode' => 'gb', 'registrationNumber' => '0123456', 'format' => 'json'), $params);
    }

    /**
     * @depends testParseTemplateWithLiteral
     */
    public function testParseTemplateWithTwoVariablesAndDotBetween()
    {
        // will fail
        $uri = new UriTemplate('http://www.example.com/v1/company/', []);
        $params = $uri->extract('/{countryCode}/{registrationNumber}{.format}', '/gb/0123456.json');
        static::assertEquals(array('countryCode' => 'gb', 'registrationNumber' => '0123456', 'format' => 'json'), $params);
    }

    /**
     * @ depends testParseTemplateWithLiteral
     */
    public function testParseTemplateWithTwoVariablesAndDotBetweenStrict()
    {
        // will fail
        $uri = new UriTemplate('http://www.example.com/v1/company/', []);
        $params = $uri->extract('/{countryCode}/{registrationNumber}{.format}', '/gb/0123456.json', true);
        static::assertEquals(array('countryCode' => 'gb', 'registrationNumber' => '0123456', 'format' => 'json'), $params);
    }

    /**
     * @ depends testParseTemplateWithLiteral
     */
    public function testParseTemplateWithThreeVariablesAndDotBetweenStrict()
    {
        // will fail
        $uri = new UriTemplate('http://www.example.com/v1/company/', []);
        $params = $uri->extract('/{countryCode}/{registrationNumber}{.namespace}{.format}', '/gb/0123456.company.json');
        static::assertEquals(array('countryCode' => 'gb', 'registrationNumber' => '0123456', 'namespace' => 'company', 'format' => 'json'), $params);
    }
}
