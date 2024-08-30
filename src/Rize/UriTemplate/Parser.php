<?php

namespace Rize\UriTemplate;

use Exception;
use Rize\UriTemplate\Node\Abstraction;
use Rize\UriTemplate\Node\Expression;
use Rize\UriTemplate\Node\Variable;
use Rize\UriTemplate\Operator\UnNamed;

class Parser
{
    private const REGEX_VARNAME = '(?:[A-z0-9_\.]|%[0-9a-fA-F]{2})';

    /**
     * Parses URI Template and returns nodes
     *
     * @param string $template
     * @return Node\Abstraction[]
     */
    public function parse($template)
    {
        $parts   = preg_split('#(\{[^\}]+\})#', $template, -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY);
        $nodes   = [];

        foreach($parts as $part) {
            $node = $this->createNode($part);

            // if current node has dot separator that requires a forward lookup
            // for the previous node iff previous node's operator is UnNamed
            if ($node instanceof Expression && $node->getOperator()->id === '.') {
                if (count($nodes) > 0) {
                    $previousNode = $nodes[count($nodes) - 1];
                    if ($previousNode instanceof Expression && $previousNode->getOperator() instanceof UnNamed) {
                        $previousNode->setForwardLookupSeparator($node->getOperator()->id);
                    }
                }
            }

            $nodes[] = $node;
        }

        return $nodes;
    }

    protected function createNode(string $token): Abstraction
    {
        // literal string
        if ($token[0] !== '{') {
            $node = $this->createLiteralNode($token);
        } else {
            // remove `{}` from expression and parse it
            $node = $this->parseExpression(substr($token, 1, -1));
        }

        return $node;
    }

    protected function parseExpression(string $expression): Expression
    {
        $token  = $expression;
        $prefix = $token[0];

        // not a valid operator?
        if (!Operator\Abstraction::isValid($prefix)) {

            // not valid chars?
            if (!preg_match('#' . self::REGEX_VARNAME . '#', $token)) {
                throw new Exception("Invalid operator [$prefix] found at {$token}");
            }

            // default operator
            $prefix = null;
        }

        // remove operator prefix if exists e.g. '?'
        if ($prefix) {
            $token = substr($token, 1);
        }

        // parse variables
        $vars = [];
        foreach(explode(',', $token) as $var) {
            $vars[] = $this->parseVariable($var);
        }

        return $this->createExpressionNode(
            $token,
            $this->createOperatorNode($prefix),
            $vars,
        );
    }

    protected function parseVariable(string $var): Variable
    {
        $var      = trim($var);
        $val      = null;
        $modifier = null;

        // check for prefix (:) / explode (*) / array (%) modifier
        if (str_contains($var, ':')) {
            $modifier = ':';
            [$varname, $val] = explode(':', $var);

            // error checking
            if (!is_numeric($val)) {
                throw new Exception("Value for `:` modifier must be numeric value [$varname:$val]");
            }
        }

        switch($last = substr($var, -1)) {
            case '*':
            case '%':

                // there can be only 1 modifier per var
                if ($modifier) {
                    throw new Exception("Multiple modifiers per variable are not allowed [$var]");
                }

                $modifier = $last;
                $var      = substr($var, 0, -1);
                break;
        }

        return $this->createVariableNode(
            $var,
            ['modifier' => $modifier, 'value'    => $val],
        );
    }

    protected function createVariableNode($token, $options = []): Variable
    {
        return new Node\Variable($token, $options);
    }

    protected function createExpressionNode($token, Operator\Abstraction $operator = null, array $vars = []): Node\Expression
    {
        return new Node\Expression($token, $operator, $vars);
    }

    protected function createLiteralNode(string $token): Node\Literal
    {
        return new Node\Literal($token);
    }

    protected function createOperatorNode($token): Operator\Abstraction
    {
        return Operator\Abstraction::createById($token);
    }
}
