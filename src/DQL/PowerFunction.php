<?php

namespace App\DQL;

use Doctrine\ORM\Query\AST\Functions\FunctionNode;
use Doctrine\ORM\Query\Lexer;

/**
 * "POWER" "(" IntegerPrimary "," IntegerPrimary ")".
 */
class PowerFunction extends FunctionNode
{
    /**
     * @var \Doctrine\ORM\Query\AST\Node
     */
    public $basePrimary;
    /**
     * @var \Doctrine\ORM\Query\AST\Node
     */
    public $exponentPrimary;

    /**
     * @override
     */
    public function getSql(\Doctrine\ORM\Query\SqlWalker $sqlWalker)
    {
        return sprintf('POW(%s,%d)', $this->basePrimary->dispatch($sqlWalker), $this->exponentPrimary->dispatch($sqlWalker));
    }

    /**
     * @override
     */
    public function parse(\Doctrine\ORM\Query\Parser $parser)
    {
        $parser->match(Lexer::T_IDENTIFIER);
        $parser->match(Lexer::T_OPEN_PARENTHESIS);
        // Parser::StringExpression() is documented as returning a string too: a Node here
        $this->basePrimary = $parser->StringExpression();
        $parser->match(Lexer::T_COMMA);
        $this->exponentPrimary = $parser->ArithmeticPrimary();
        $parser->match(Lexer::T_CLOSE_PARENTHESIS);
    }
}
