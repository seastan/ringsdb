<?php

declare(strict_types=1);

namespace App\DQL;

use Doctrine\ORM\Query\AST\Functions\FunctionNode;
use Doctrine\ORM\Query\AST\Node;
use Doctrine\ORM\Query\Lexer;
use Doctrine\ORM\Query\Parser;
use Doctrine\ORM\Query\SqlWalker;

/**
 * "POWER" "(" IntegerPrimary "," IntegerPrimary ")".
 */
class PowerFunction extends FunctionNode
{
    /**
     * @var Node
     */
    public $basePrimary;
    /**
     * @var Node
     */
    public $exponentPrimary;

    /**
     * @override
     */
    public function getSql(SqlWalker $sqlWalker)
    {
        return sprintf('POW(%s,%d)', $this->basePrimary->dispatch($sqlWalker), $this->exponentPrimary->dispatch($sqlWalker));
    }

    /**
     * @override
     */
    public function parse(Parser $parser): void
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
